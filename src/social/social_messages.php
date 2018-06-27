<?php
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
$openChatID = 0;
$bucket = $identifier .'-uploads'; //no uppercase, no underscores, no ending dashes, no adjacent special chars
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if(!empty($_POST['openChat'])) $openChatID = intval($_POST['openChat']);
	if(!empty($_POST['chat_send'])){
		$sendingUser = intval($_POST['chat_send']);
		$v2Key = $publicKey;
		if(!empty($_POST['chat_message'])){
			$message = asymmetric_encryption('CHAT', test_input($_POST['chat_message']), $userID, $privateKey);
			if($message == test_input($_POST['chat_message'])) $v2Key = $publicKey;
			$conn->query("INSERT INTO messenger_messages(message, participantID, vKey)
			SELECT '$message', id, '$v2Key' FROM relationship_conversation_participant WHERE partType = 'USER' AND partID = '$sendingUser' AND conversationID = $openChatID");
			if($conn->error) showError($conn->error.__LINE__);
		}
		if(isset($_FILES['chat_newfile'])){
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
					$file_encrypt = asymmetric_encryption('TASK', file_get_contents($_FILES['newTaskFiles']['tmp_name'][$i]), $userID, $privateKey);
					$s3->putObject(array(
						'Bucket' => $bucket,
						'Key' => $hashkey,
						'Body' => $file_encrypt
					));

					$filename = test_input($file_info['filename']);
					$conn->query("INSERT INTO archive (category, categoryID, name, parent_directory, type, uniqID, uploadUser)
					VALUES ('CHAT', '$openChatID', '$filename', '$parent', '$ext', '$hashkey', $userID)");
					if($conn->error){ showError($conn->error.__LINE__); }
					$conn->query("INSERT INTO messenger_messages(message, type, participantID, vKey)
					SELECT '$message', 'file', id, '$v2Key' FROM relationship_conversation_participant WHERE partType = 'USER' AND partID = '$sendingUser' AND conversationID = $openChatID");

					if($conn->error){ showError($conn->error.__LINE__); } else { showSuccess($lang['OK_UPLOAD']); }
				} catch(Exception $e){
					echo $e->getTraceAsString();
					echo '<br><hr><br>';
					echo $e->getMessage();
				}
			}
		}

	}
	if(!empty($_POST['leave_conversation'])){
		$conversationID = intval($_POST['leave_conversation']);
		$conn->query("UPDATE relationship_conversation_participant SET status = 'exited' WHERE partType='USER' AND partID = $userID AND conversationID = $conversationID");
		if($conn->error){
			showError($conn->error);
		} else {
			showSuccess("Sie haben diese Konversation verlassen.");
		}
	}
	if(isset($_POST['send_new_message']) && !empty($_POST['new_message_subject']) && !empty($_POST['new_message_body'])){
		$subject = test_input($_POST['new_message_subject']);
		$conn->query("INSERT INTO messenger_conversations(subject, category) VALUES('$subject', 'direct')");
		if($conn->error) showError($conn->error.__LINE__);
		$conversationID = $openChatID = $conn->insert_id;

		$conn->query("INSERT INTO relationship_conversation_participant(conversationID, partType, partID, status) VALUES($conversationID, 'USER', '$userID', 'creator')");
		if($conn->error) showError($conn->error.__LINE__);
		$participantID = $conn->insert_id;

		$message = asymmetric_encryption('CHAT', test_input($_POST['new_message_body']), $userID, $privateKey, 'encrypt', $err);
		$v2Key = $message == test_input($_POST['new_message_body']) ? '' : $publicKey;
		$conn->query("INSERT INTO messenger_messages(message, participantID, vKey) VALUES('$message', $participantID, '$v2Key')");
		if($conn->error) showError($conn->error.__LINE__);

		foreach($_POST['new_message_user'] as $val){
			$val = intval($val);
			$conn->query("INSERT INTO relationship_conversation_participant(conversationID, partType, partID, status) VALUES($conversationID, 'USER', '$val', 'normal')");
		}
	} elseif(isset($_POST['send_new_message'])) {
		showError("Missing Subject or Message");
	}
}
?>
<div class="page-header h3">Nachrichten
    <div class="page-header-button-group"><a data-toggle="modal" href="#new-message-modal" class="btn btn-default"><i class="fa fa-plus"></i></a></div>
</div>

<div class="row">
	<div class="col-md-5">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>Betreff</th>
					<th></th>
					<th>Teilnehmer</th>
					<th></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$stmt = $conn->prepare("SELECT partType, partID FROM relationship_conversation_participant WHERE conversationID = ?"); echo $conn->error;
				$stmt->bind_param('i', $x);
				$result = $conn->query("SELECT c.id, subject, rcp.id AS participantID, tbl.unreadMessages FROM messenger_conversations c
					INNER JOIN relationship_conversation_participant rcp
					ON (rcp.status != 'exited' AND rcp.conversationID = c.id AND rcp.partType = 'USER' AND rcp.partID = '$userID')
					LEFT JOIN (SELECT COUNT(m.id) AS unreadMessages, rcp2.conversationID FROM messenger_messages m
						INNER JOIN relationship_conversation_participant rcp2
						ON (rcp2.id = m.participantID) WHERE m.sentTime <= rcp2.lastCheck AND (rcp2.partType != 'USER' OR rcp2.partID != $userID)
						GROUP BY conversationID ) tbl
					ON tbl.conversationID = c.id ");
				echo $conn->error;
				while($result && ($row = $result->fetch_assoc())){
					echo '<tr>';
					echo '<td>', $row['subject'], '</td>';
					echo '<td>', $row['unreadMessages'] ? '<span class="badge" title="Ungelesene Nachrichten">'.$row['unreadMessages'] .'</span>' : '', '</td>';
					echo '<td>';
					$x = $row['id'];
					$participantID = $row['participantID'];
					$stmt->execute();
					$partres = $stmt->get_result();
					while($partrow = $partres->fetch_assoc()){
						if($partrow['partType'] == 'USER'){
							echo $userID_toName[$partrow['partID']];
						} elseif($partrow['partType'] == 'CONTACT') {

						} else {
							echo $partrow['partID'];
						}
						echo '<br>';
					}
					echo '</td>';
					echo '<td><div class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown"> <i class="fa fa-ellipsis-v"></i></a>
					<ul class="dropdown-menu">
					<button type="submit" name="leave_conversation" class="btn btn-link" value="', $row['id'], '">Konversation Verlassen</button>
					</ul></div> </td>';
					echo '<td><form method="POST"><button type="submit" name="openChat" value="', $row['id'], '" class="btn btn-default"><i class="fa fa-arrow-right"></i></button></form></td>';
					echo '</tr>';
				}
				$stmt->close();
				?>
			</tbody>
		</table>
	</div>
	<div class="col-md-7"><br><br>
		<?php require __DIR__.'/chatwindow.php'; ?>
	</div>
</div>


<form method="post">
	<div id="new-message-modal" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-content modal-md">
			<div class="modal-header h4">Neue Nachricht</div>
			<div class="modal-body">
				<div class="col-md-12">
					<label>Betreff</label>
					<input type="text" maxlength="50" name="new_message_subject" class="form-control" required>
					<br>
					<label><?php echo mc_status(); ?> Nachricht</label>
					<textarea name="new_message_body" rows="8" style="resize:none" class="form-control"></textarea>
					<hr><h4>Empfänger</h4><hr>
					<ul class="nav nav-tabs">
						<li class="active"><a data-toggle="tab" href="#tab_recipient_user">Benutzer</a></li>
						<li><a data-toggle="tab" href="#tab_recipient_contact">Adressbuch</a></li>
					</ul>
					<div class="tab-content">
						<div id="tab_recipient_user" class="tab-pane fade in active">
							<br><label>Benutzer Auswählen</label>
							<select class="js-example-basic-single" name="new_message_user[]" multiple>
								<?php
								foreach($available_users AS $user){
									if($user > 0) echo '<option value="',$user,'">', $userID_toName[$user], '</option>';
								}
								?>
							</select>
						</div>
						<div id="tab_recipient_contact" class="tab-pane fade"><br>Inhalt noch im Aufbau</div>
					</div>
				</div>
				<br>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning" name="send_new_message">Senden</button>
			</div>
		</div>
	</div>
</form>
<?php require dirname(__DIR__) . '/footer.php'; ?>
