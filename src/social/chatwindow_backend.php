<?php
if(!empty($_POST['leave_conversation'])){
	$conversationID = intval($_POST['leave_conversation']);
	// insert user relationship so other team members don't leave
	$conn->query("INSERT INTO relationship_conversation_participant(partType, partID, conversationID, status) VALUES ('USER', '$userID', $conversationID, 'exited') ON DUPLICATE KEY UPDATE status = 'exited'");
	if($conn->error) showError($conn->error.__LINE__);
	//delete conversations which have no participants
	$conn->query("DELETE FROM messenger_conversations WHERE NOT EXISTS(SELECT id FROM relationship_conversation_participant WHERE conversationID = messenger_conversations.id)");
	if($conn->error){
		showError($conn->error);
	} else {
		showSuccess("Sie haben diese Konversation verlassen.");
	}
}
if(!empty($_POST['openChat'])){
	$openChatID = intval($_POST['openChat']);
	if(isset($_POST['chat_join_conversation'])){
		$conn->query("INSERT INTO relationship_conversation_participant(partType, partID, conversationID, status) VALUES ('USER', '$userID', $openChatID, 'open')");
	}
	if(isset($_POST['chat_send'])){
		$v2Key = $publicKey;
		if(!empty($_POST['chat_message'])){
			$message = test_input($_POST['chat_message']);
			$message_encrypt = asymmetric_encryption('CHAT', $message, $userID, $privateKey);
			if($message == $message_encrypt) $v2Key = '';
			$conn->query("INSERT INTO messenger_messages(message, participantID, vKey) SELECT '$message_encrypt', id, '$v2Key'
			FROM relationship_conversation_participant WHERE partType = 'USER' AND partID = '$userID' AND conversationID = $openChatID");

			if(!$conn->error) { //5b6a830d05c7e
				$conn->query("UPDATE relationship_conversation_participant SET archive = NULL WHERE conversationID = $openChatID");
			}
			//TODO: how do i reply from team, when sent from team and not as user?
			// $result = $conn->query("SELECT partID FROM relationship_conversation_participant WHERE status != 'exited' AND conversationID = $openChatID AND partType = 'contact' OR partType = 'client'");
			// if($result && $result->num_rows){
			// 	$result_temp = $conn->query("SELECT subject, identifier FROM messenger_conversations WHERE id = $openChatID LIMIT 1");
			// 	$row = $result_temp->fetch_assoc();
			// 	$subject = $row['subject'];
			// 	$identifier = $row['identifier'];
			// 	while($row_part = $result->fetch_assoc()){
			// 		//TODO: check the recipients for email notifications (users, clients, contacts, teams..)
			// 		//$options = ['subject' => "$subject - [CON - $identifier]", 'userid' => $userID];
			// 	}
			// }
		}
		if(file_exists($_FILES['chat_newfile']['tmp_name']) && is_uploaded_file($_FILES['chat_newfile']['tmp_name'])){
			$s3 = getS3Object($bucket);
			$file_info = pathinfo($_FILES['chat_newfile']['name']);
			$ext = strtolower($file_info['extension']);
			if (!validate_file($err, $ext, $_FILES['chat_newfile']['size'])){
				showError($err);
			} elseif (empty($s3)) {
				showError("Es konnte keine S3 Verbindung hergestellt werden. Stellen Sie sicher, dass unter den Archiv Optionen eine gültige Verbindung gespeichert wurde.");
			} else {
				try{
					$hashkey = uniqid('', true); //23 chars
					$conn->query("INSERT INTO messenger_messages(message, type, participantID, vKey) SELECT '$hashkey', 'file', id, '$v2Key'
					FROM relationship_conversation_participant WHERE partType = 'USER' AND partID = '$userID' AND conversationID = $openChatID");
					if(!$conn->error){ //5b642461d1cb4
						$file_encrypt = asymmetric_encryption('CHAT', file_get_contents($_FILES['chat_newfile']['tmp_name']), $userID, $privateKey);
						$s3->putObject(array(
							'Bucket' => $bucket,
							'Key' => $hashkey,
							'Body' => $file_encrypt
						));
						$filename = test_input($file_info['filename']);
						$conn->query("INSERT INTO archive (category, categoryID, name, parent_directory, type, uniqID, uploadUser)
						VALUES ('CHAT', '$openChatID', '$filename', 'ROOT', '$ext', '$hashkey', $userID)");
						if($conn->error){ showError($conn->error.__LINE__); } else { showSuccess($lang['OK_UPLOAD']); }
					} else {
						showError($conn->error.__LINE__);
					}
				} catch(Exception $e){
					echo $e->getTraceAsString();
					echo '<br><hr><br>';
					echo $e->getMessage();
				}
			}
		}
	}
}
if(!empty($_POST['chat_archive'])){ //5b680295a634f
	$val = intval($_POST['chat_archive']);
	// insert user relationship so other team members don't see the chat as archived
	$conn->query("INSERT INTO relationship_conversation_participant(partType, partID, conversationID, status, archive) VALUES ('USER', '$userID', $val, 'open', '".getCurrentTimestamp()."') ON DUPLICATE KEY UPDATE archive = '".getCurrentTimestamp()."'");
	if ($conn->error) {
		showError($conn->error);
	} else {
		$openChatID = 0;
		showSuccess($lang['OK_ARCHIVE']);
	}
}
 ?>
