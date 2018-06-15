<pre>
<?php //5b0b943ebb59d
require_once dirname(__DIR__)."/connection.php";
require_once dirname(__DIR__)."/utilities.php";

$result = $conn->query("SELECT id FROM identification LIMIT 1");
if($row = $result->fetch_assoc()){
	$identifier = $row['id'];
} else {
	$identifier = uniqid('');
	$conn->query("INSERT INTO identification (id) VALUES ('$identifier')");
}

$bucket = $identifier.'-tasks';
$s3 = getS3Object($bucket);

$archive = 'Connect_Tasks';
$stmt_insertarchive = $conn->prepare("INSERT INTO archive(uniqID, category, categoryID, name, type) VALUES(?, 'TASK', ?, ?, ?)");
$stmt_insertarchive->bind_param("ssss", $archiveID, $projectid, $filename, $filetype);

$result = $conn->query("SELECT id, server, smtpSecure, port, service, username, password FROM emailprojects");

while($result && $row = $result->fetch_assoc()){
    $security = empty($row['smtpSecure']) ? '' : '/'.$row['smtpSecure'];
	//$mailbox = '{'.$row['server'] .':'. $row['port']. '/'.$row['service'] . $security.'/novalidate-cert}'.'INBOX'; //{imap.gmail.com:993/imap/ssl}INBOX ; {localhost:993/imap/ssl/novalidate-cert}
    $mailbox = '{'.$row['server'] .':'. $row['port']. '/'.$row['service'] . $security.'/novalidate-cert}';
	$imap = imap_open($mailbox, $row['username'], $row['password'], CL_EXPUNGE);

    @imap_createmailbox($imap, imap_utf7_encode($mailbox.$archive));
	imap_reopen($imap, $mailbox.'INBOX');

    $result = $conn->query("SELECT fromAddress, toAddress, subject, templateID FROM workflowRules WHERE workflowID = ".$row['id']." ORDER BY position ASC"); echo $conn->error;
    while(($rule = $result->fetch_assoc()) && $rule['templateID']){
		$move_sequence = array();
        foreach(imap_search($imap, 'ALL') as $mail_number){
            $header = imap_headerinfo($imap, $mail_number);
			$match = true;
			$pos = strpos($header->subject, $rule['subject']);
			if($rule['fromAddress'] && strpos($header->from[0]->mailbox.'@'.$header->from[0]->host, $rule['fromAddress']) === false ) $match = false;
			if($rule['toAddress'] && $header->to[0]->mailbox.'@'.$header->to[0]->host != $rule['toAddress']) $match = false;
			if($rule['subject'] && $pos === false) $match = false;

			if($match){
				$keypair = sodium_crypto_box_keypair();
				$v2 = base64_encode(sodium_crypto_box_publickey($keypair));
				$secret = base64_encode(sodium_crypto_box_secretkey($keypair));
				$encrypted_header = asymmetric_encryption('TASK', imap_fetchheader($imap, $mail_number), 0, $secret);
				$structure = imap_fetchstructure($imap, $mail_number);

				print_r($structure);

				$html = '';
				$projectid = uniqid();
				if(isset($structure->parts[0])){
					for($i = 0; $i < count($structure->parts); $i++){
						$filename = $filetype = '';
						if($structure->parts[$i]->ifparameters){
							foreach($structure->parts[$i]->parameters as $object){ //loops through filename, size, dates of attachments or mails.
								if(strtolower($object->attribute) == 'name'){
									$filename = $object->value;
									$filetype = strtolower($structure->parts[$i]->subtype);
								} elseif(strtolower($object->attribute) =='charset'){
									$html = imap_fetchbody($imap, $mail_number, $i+1);
									if($structure->parts[$i]->encoding == 3) { /* 3 = BASE64 encoding */
										$html = base64_decode($html);
									} elseif($structure->parts[$i]->encoding == 4) { /* 4 = QUOTED-PRINTABLE encoding */
										$html = quoted_printable_decode($html);
									}
									$html = iconv($object->value, 'UTF-8', $html);
								}
							}
						}
						if(!$filename && $structure->parts[$i]->ifdparameters){
							foreach($structure->parts[$i]->dparameters as $object){
								if(strtolower($object->attribute) == 'filename'){
									$filename = $object->value;
									$filetype = strtolower($structure->parts[$i]->subtype);
								}
							}
						}
						if($filename){
							$filename = pathinfo($filename, PATHINFO_FILENAME);
							$attachment_body = imap_fetchbody($imap, $mail_number, $i+1);
							if($structure->parts[$i]->encoding == 3) {
								$attachment_body = base64_decode($attachment_body);
							} elseif($structure->parts[$i]->encoding == 4) {
								$attachment_body = quoted_printable_decode($attachment_body);
							}
							if(in_array($filetype, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'txt', 'zip', 'msg', 'jpg', 'jpeg', 'png', 'gif'])){
								$archiveID = uniqid('', true);
								$stmt_insertarchive->execute();
								$s3->putObject(array(
									'Bucket' => $bucket,
									'Key' => $archiveID,
									'Body' => asymmetric_encryption('TASK', $attachment_body, 0, $secret)
								));
								if($structure->parts[$i]->ifid){
									$attachmentId = trim($structure->parts[$i]->id, " <>");
									$html = str_replace("cid:$attachmentId", "cid:$archiveID", $html); //replace the image with the archive id. makes it easier.
								}
							}
						}
					} //endwhile(parts)
				} else {
					if($structure->ifparameters){
						foreach($structure->parameters as $object){ //loops through filename, size, dates of attachments or mails.
							if(strtolower($object->attribute) =='charset'){
								$html = imap_fetchbody($imap, $mail_number, 1);
								if(!$html) imap_fetchbody($imap, $mail_number, 1.2);
								if($structure->parts[$i]->encoding == 3) { /* 3 = BASE64 encoding */
									$html = base64_decode($html);
								} elseif($structure->parts[$i]->encoding == 4) { /* 4 = QUOTED-PRINTABLE encoding */
									$html = quoted_printable_decode($html);
								}
								$html = iconv($object->value, 'UTF-8', $html);
							}
						}
					}
				}

				echo $html;

				//dynamicproject
				$conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$projectid', 'CREATED', 1)");
				$html = asymmetric_encryption('TASK', $html, 0, $secret);
				$name = asymmetric_encryption('TASK', substr_replace($header->subject, '', $pos, strlen($rule['subject'])), 0, $secret);
				$conn->query("INSERT INTO dynamicprojects(
				projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
				projectpriority, projectparent, projectowner, projectleader, projectpercentage, estimatedHours, level, projecttags, isTemplate, v2, projectmailheader)
				SELECT '$projectid', '$name', '$html', companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
				projectpriority, projectparent, projectowner, projectleader, projectpercentage, estimatedHours, level, projecttags, 'FALSE', '$v2', '$encrypted_header'
				FROM dynamicprojects WHERE projectid = '{$rule['templateID']}'"); echo $conn->error;

				$conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid, position) SELECT '$projectid', userid, position FROM dynamicprojectsemployees WHERE projectid = '{$rule['templateID']}'");
				echo $conn->error;
				$conn->query("INSERT INTO dynamicprojectsteams (projectid, teamid) SELECT '$projectid', teamid FROM dynamicprojectsteams WHERE projectid = '{$rule['templateID']}'");
				echo $conn->error;
				$move_sequence[] = $mail_number;
				imap_delete($imap, $mail_number);
			}

        } //end foreach mail
		if(!imap_mail_move($imap, implode(',', $move_sequence), $archive)) imap_expunge($imap);
    }
	imap_close($imap);
}
$stmt_insertarchive->close();
echo $conn->error;
?>
</pre>
