<pre>
<?php //5b0b943ebb59d
require_once dirname(__DIR__)."/connection.php";
require_once dirname(__DIR__)."/utilities.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$result = $conn->query("SELECT id FROM identification LIMIT 1");
if($row = $result->fetch_assoc()){
	$identifier = $row['id'];
} else {
	$identifier = uniqid('');
	$conn->query("INSERT INTO identification (id) VALUES ('$identifier')");
}

$s3 = getS3Object();
$archive = 'Connect_Tasks';

$stmt_insertarchive = $conn->prepare("INSERT INTO archive(uniqID, category, categoryID, name, type) VALUES(?, ?, ?, ?, ?)");
$stmt_insertarchive->bind_param("sssss", $archiveID, $archiveCat, $projectid, $filename, $filetype);

$result_serv = $conn->query("SELECT id, server, smtpSecure, port, service, username, password FROM emailprojects");
if($conn->error) echo $conn->error.__LINE__;
while($result_serv && $row = $result_serv->fetch_assoc()){
    $security = empty($row['smtpSecure']) ? '' : '/'.$row['smtpSecure'];
	//$mailbox = '{'.$row['server'] .':'. $row['port']. '/'.$row['service'] . $security.'/novalidate-cert}'.'INBOX'; //{imap.gmail.com:993/imap/ssl}INBOX ; {localhost:993/imap/ssl/novalidate-cert}
    $mailbox = '{'.$row['server'] .':'. $row['port']. '/'.$row['service'] . $security.'/novalidate-cert}';
	$imap = imap_open($mailbox, $row['username'], $row['password'], CL_EXPUNGE);

    @imap_createmailbox($imap, imap_utf7_encode($mailbox.$archive));
	imap_reopen($imap, $mailbox.'INBOX');

    $result_rul = $conn->query("SELECT fromAddress, toAddress, subject, templateID, workflowID FROM workflowRules
		WHERE isActive = 'TRUE' AND workflowID = ".$row['id']." ORDER BY templateID, position ASC");
	if($conn->error) echo $conn->error.__LINE__;
    while(($rule = $result_rul->fetch_assoc())){
		$move_sequence = array();
		if($rule['templateID']){
			$archiveCat = 'TASK';
			$bucket = $identifier.'-tasks';
		} else {
			$archiveCat = 'CHAT';
			$bucket = $identifier.'-uploads';
		}
        foreach(imap_search($imap, 'ALL') as $mail_number){
            $header = imap_headerinfo($imap, $mail_number);
			$match = true;
			$pos = $rule['subject'] ? strpos($header->subject, $rule['subject']) : false;
			$sender = $header->from[0]->mailbox.'@'.$header->from[0]->host;
			if($rule['fromAddress'] && strpos($sender, $rule['fromAddress']) === false ) $match = false;
			if($rule['toAddress'] && $header->to[0]->mailbox.'@'.$header->to[0]->host != $rule['toAddress']) $match = false;
			if($rule['subject'] && $pos === false) $match = false;
			if(!$rule['workflowID'] && !preg_match("/\[CON - [0-9a-z]{13}\]$/", $rule['subject'], $out)) $match = false;
			if($match){
				$keypair = sodium_crypto_box_keypair();
				$v2 = base64_encode(sodium_crypto_box_publickey($keypair));
				$secret = base64_encode(sodium_crypto_box_secretkey($keypair));
				$encrypted_header = asymmetric_encryption('TASK', imap_fetchheader($imap, $mail_number), 0, $secret);

				$html = '';
				$projectid = uniqid();
				if(!$rule['templateID']){
					$projectid = substr($header->subject, -14, -1); echo "Messenger ID : $projectid";
				}
				foreach(create_part_array(imap_fetchstructure($imap, $mail_number)) as $partoverview){
					$part = $partoverview['part_object'];
					$content = imap_fetchbody($imap, $mail_number, $partoverview['part_number']);
					if($part->encoding == 3){
						$content = base64_decode($content);
					} elseif($part->encoding == 4){
						$content = quoted_printable_decode($content);
					}
					if($part->ifparameters) $params = $part->parameters;
					if($part->ifdparameters) $params = $part->dparameters;
					foreach($params as $object){
						if($part->ifdisposition && ($part->disposition == 'attachment' || $part->disposition == 'inline')){
							if($object->attribute == 'filename' || $object->attribute == 'name'){
								$filename = pathinfo($object->value, PATHINFO_FILENAME);
								$filetype = strtolower($part->subtype);
								$archiveID = uniqid('', true);
								if(in_array($filetype, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'txt', 'zip', 'msg', 'jpg', 'jpeg', 'png', 'gif'])){
									$stmt_insertarchive->execute();
									$s3->putObject(array(
										'Bucket' => $bucket,
										'Key' => $archiveID,
										'Body' => asymmetric_encryption('TASK', $content, 0, $secret)
									));
									if($part->ifid && $part->disposition == 'inline'){
										$attachmentId = trim($part->id, " <>");
										$html = str_replace("cid:$attachmentId", "cid:$archiveID", $html); //replace the image with the archive id. makes it easier.
									}
								}
							}
						} elseif($object->attribute == 'charset' && !empty($params)){
							$html = iconv($object->value, 'UTF-8//TRANSLIT', trim($content));
						}
					}
				}

				if($rule['templateID']){ //dynamicproject
					$conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$projectid', 'CREATED', 1)");
					if($conn->error) echo $conn->error.__LINE__;
					$html = asymmetric_encryption('TASK', $html, 0, $secret);
					$name = asymmetric_encryption('TASK', substr_replace($header->subject, '', $pos, strlen($rule['subject'])), 0, $secret);
					$conn->query("INSERT INTO dynamicprojects(
					projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
					projectpriority, projectparent, projectowner, projectleader, projectpercentage, estimatedHours, level, projecttags, isTemplate, v2, projectmailheader)
					SELECT '$projectid', '$name', '$html', companyid, clientid, clientprojectid, projectcolor, IF(projectstart='0000-00-00', UTC_TIMESTAMP , projectstart),
					projectend, projectstatus, projectpriority, projectparent, projectowner, projectleader, projectpercentage, estimatedHours, level, projecttags, 'FALSE',
					'$v2', '$encrypted_header' FROM dynamicprojects WHERE projectid = '{$rule['templateID']}'");
					if($conn->error) echo $conn->error.__LINE__;
					if($rule['autoResponse']) send_standard_email($sender, $rule['autoResponse'], ['subject' => "Connect - Ticket Nr. [$projectid]"]); //5b20ad39615f9
					$conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid, position) SELECT '$projectid', userid, position FROM dynamicprojectsemployees WHERE projectid = '{$rule['templateID']}'");
					if($conn->error) echo $conn->error.__LINE__;
					$conn->query("INSERT INTO dynamicprojectsteams (projectid, teamid) SELECT '$projectid', teamid FROM dynamicprojectsteams WHERE projectid = '{$rule['templateID']}'");
					if($conn->error) echo $conn->error.__LINE__;
				} else { //message
					echo 'Message Detected';
					$result = $conn->query("SELECT id FROM messenger_conversations WHERE identifier = '$projectid'");
					if($row = $result->fetch_assoc()){
						$conversationID = $row['id'];
						$result = $conn->query("SELECT id FROM relationship_conversation_participant WHERE conversationID = $conversationID AND partID = '$sender' ");
						if($row = $result->fetch_assoc()){
							$participantID = $row['id'];
						} else {
							echo "Adding new participant... ";
							$conn->query("INSERT INTO relationship_conversation_participant(conversationID, partType, partID, status)
								VALUES ($conversationID, 'unknown', '$sender', 'normal')");
							$participantID = $conn->insert_id;
							if($conn->error) echo $conn->error.__LINE__;
						}
						$result->free();
						$message = asymmetric_encryption('CHAT', $html, 0, $secret);
						$conn->query("INSERT INTO messenger_messages(message, participantID, vKey) VALUES ('$message', $participantID, '$v2')");
						if($conn->error) echo $conn->error.__LINE__;
					} else {
						echo "Error: No Message found with identifier $projectid";
					}
				}
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

//http://php.net/manual/en/function.imap-fetchbody.php
function create_part_array($structure, $prefix="") {
	//print_r($structure);
	if (!empty($structure->parts) && sizeof($structure->parts) > 0) {    // There some sub parts
		foreach ($structure->parts as $count => $part) {
			add_part_to_array($part, $prefix.($count+1), $part_array);
		}
	} else {    // Email does not have a seperate mime attachment for text
		$part_array[] = array('part_number' => $prefix.'1', 'part_object' => $structure);
	}
	//print_r($part_array);
	return $part_array;
}
function add_part_to_array($obj, $partno, & $part_array) {
	$part_array[] = array('part_number' => $partno, 'part_object' => $obj);
	if ($obj->type == 2) { // Check to see if the part is an attached email message, as in the RFC-822 type
		if (sizeof($obj->parts) > 0) {    // Check to see if the email has parts
			foreach ($obj->parts as $count => $part) {
				// Iterate here again to compensate for the broken way that imap_fetchbody() handles attachments
				if (sizeof($part->parts) > 0) {
					foreach ($part->parts as $count2 => $part2) {
						add_part_to_array($part2, $partno.".".($count2+1), $part_array);
					}
				} else {    // Attached email does not have a seperate mime attachment for text
					$part_array[] = array('part_number' => $partno.'.'.($count+1), 'part_object' => $obj);
				}
			}
		} else {    // Not sure if this is possible
			$part_array[] = array('part_number' => $prefix.'.1', 'part_object' => $obj);
		}
	} else {    // If there are more sub-parts, expand them out.
		if (!empty($obj->parts) && sizeof($obj->parts) > 0) {
			foreach ($obj->parts as $count => $p) {
				add_part_to_array($p, $partno.".".($count+1), $part_array);
			}
		}
	}
}
?>
</pre>
