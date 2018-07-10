<?php if($openChatID): ?>
	<div class="panel panel-default" style="margin-bottom:0">
		<?php
		$result = $conn->query("SELECT subject, category  FROM messenger_conversations c WHERE c.id = $openChatID ");
		$messenger_row = $result->fetch_assoc();

		$participantID = false;
		$result = $conn->query("SELECT id FROM relationship_conversation_participant WHERE conversationID = $openChatID AND partType='USER' AND partID='$userID'");
		if($result && $result->num_rows > 0) $participantID = $result->fetch_assoc()['id'];

		if($participantID) $conn->query("UPDATE relationship_conversation_participant SET lastCheck = UTC_TIMESTAMP WHERE id = $participantID");
		?>
		<div class="panel-heading"><?php echo $messenger_row['subject']; ?></div>
		<div class="panel-body scrollDown" style="height:40vh; overflow-y:auto;">
			<?php
			$date = '';
			$result = $conn->query("SELECT message, vKey, sentTime, m.type, rcp.partType, rcp.partID, rcp.status, rcp.lastCheck,
				archive.name AS fileName, archive.type AS fileType, m.id AS messageID
				FROM messenger_messages m LEFT JOIN relationship_conversation_participant rcp ON rcp.id = m.participantID
				LEFT JOIN archive ON m.message = archive.uniqID
				WHERE rcp.conversationID = $openChatID ORDER BY m.sentTime ASC LIMIT 20");
			echo $conn->error;
			if($result->num_rows == 20) echo '<a>Ältere Nachricten laden</a>';
			while($result && ($row_temp = $result->fetch_assoc())){
				$row = $row_temp; //so we save the last row for read/unread
				if($date != substr($row['sentTime'],0, 10)){
					$date = substr($row['sentTime'],0, 10);
					echo '<p class="text-center" style="color:grey;font-size:8pt;">- ',$date,' -</p>';
				}
				echo '<div style="display:table;width:100%;">';
				if($row['partType'] == 'USER' && $row['partID'] == $userID){
					$style = 'background-color:#caf9b3; float:right;';
				} elseif($row['partType'] == 'USER'){
					echo '<p style="font-size:75%;">',$userID_toName[$row['partID']],' - ', substr(carryOverAdder_Hours($row['sentTime'], $timeToUTC),11,5), '</p>';
					$style = 'float:left;';
				}
				echo '<div class="well" style="width:70%;margin-bottom:10px;',$style,'" >';
				if($row['type'] == 'text') echo asymmetric_encryption('CHAT', $row['message'], $userID, $privateKey, $row['vKey']);
				if($row['type'] == 'file' && $row['fileName']) {
					echo '<form method="POST" action="../project/detailDownload" target="_blank">
					<input type="hidden" name="keyReference" value="CHAT_',$row['messageID'],'" />
					<button type="submit" class="btn btn-link" name="download-file" value="',$row['message'],'"><i class="fa fa-file-text-o"></i> ',$row['fileName'],'.',$row['fileType'],'</form>';
				} elseif($row['type'] == 'file'){
					$conn->query("DELETE FROM messenger_messages WHERE id = ".$row['messageID']); //remove this after the update.
				}
				echo '</div>';
				echo '</div>';
			}
			if($row['partType'] == 'USER' && $row['partID'] == $userID){
				$result = $conn->query("SELECT partType, partID FROM relationship_conversation_participant WHERE conversationID = $openChatID
					AND lastCheck > '{$row['sentTime']}' AND (partType != 'USER' OR partID != $userID)");
				if($result && $result->num_rows > 0){
					echo '<p style="font-size:75%;width:100%;text-align:right;">Gesehen: ';
					while($row = $result->fetch_assoc()){
						if($row['partType'] == 'USER')
						echo $userID_toName[$row['partID']], '; ';
					}
					echo '</p>';
				}
				echo $conn->error;
			}
			?>
		</div>
	</div>
	<form method="POST" enctype="multipart/form-data">
		<input type="hidden" readonly value="<?php echo $openChatID; ?>" name="openChat" />
		<?php if($participantID): ?>
				<textarea id="chat_message_<?php echo $openChatID; ?>" name="chat_message" rows="3" class="form-control"  placeholder="Deine Nachricht... " style="resize:none"></textarea>
				<div style="border:1px solid #cccccc;background-color: #eaeaea">
					<label class="btn btn-empty">
						<i class="fa fa-paperclip"></i>
						<input type="file" name="chat_newfile" style="display:none" >
					</label>
					<span style="float:right">
						<button id="chat_send_<?php echo $openChatID; ?>" type="submit" class="btn btn-link" name="chat_send">Senden <i class="fa fa-paper-plane-o"></i></button>
					</span>
				</div>
		<?php else: ?>
			Sie sind noch kein Teilnehmer dieser Konversation. Wollen Sie an dieser Konversation teilnehmen?
			<button type="submit" name="chat_join_conversation" class="btn btn-warning">Ja, ich möchte teilnehmen.</button>
		<?php endif; ?>
	</form>
	<script type="text/javascript">
	$("#chat_message_<?php echo $openChatID; ?>").keypress(function (e) {
		if(e.which == 13) {
			e.preventDefault();
			$("#chat_send_<?php echo $openChatID; ?>").click();
		}
	});
	</script>
<?php endif; ?>
