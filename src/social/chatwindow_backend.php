<?php
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
			if($conn->error) showError($conn->error.__LINE__);

			//TODO: how do i reply from team, when sent from team and not as user?
			// $result = $conn->query("SELECT partID FROM relationship_conversation_participant WHERE conversationID = $openChatID AND partType = 'contact' OR partType = 'client'");
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
					$file_encrypt = asymmetric_encryption('CHAT', file_get_contents($_FILES['chat_newfile']['tmp_name']), $userID, $privateKey);
					$s3->putObject(array(
						'Bucket' => $bucket,
						'Key' => $hashkey,
						'Body' => $file_encrypt
					));
					$filename = test_input($file_info['filename']);
					$conn->query("INSERT INTO archive (category, categoryID, name, parent_directory, type, uniqID, uploadUser)
					VALUES ('CHAT', '$openChatID', '$filename', 'ROOT', '$ext', '$hashkey', $userID)");

					if($conn->error){ showError($conn->error.__LINE__); }
					$conn->query("INSERT INTO messenger_messages(message, type, participantID, vKey) SELECT '$hashkey', 'file', id, '$v2Key'
					FROM relationship_conversation_participant WHERE partType = 'USER' AND partID = '$userID' AND conversationID = $openChatID");

					if($conn->error){ showError($conn->error.__LINE__); } else { showSuccess($lang['OK_UPLOAD']); }
				} catch(Exception $e){
					echo $e->getTraceAsString();
					echo '<br><hr><br>';
					echo $e->getMessage();
				}
			}
		}
	}
}
 ?>
