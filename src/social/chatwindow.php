<?php if($openChatID): ?>
	<div class="panel panel-default" style="margin-bottom:0">
		<?php
		$result_cw = $conn->query("SELECT subject, category  FROM messenger_conversations c WHERE c.id = $openChatID ");
		$messenger_row = $result_cw->fetch_assoc();

		$participantID = false;
		$participants = array();
		$result_cw = $conn->query("SELECT id FROM relationship_conversation_participant WHERE conversationID=$openChatID AND partType='USER' AND partID='$userID'");
		while($result_cw && ($row_cw = $result_cw->fetch_assoc())){
			$participantID = $row_cw['id'];
		}
		if($participantID) $conn->query("UPDATE relationship_conversation_participant SET lastCheck = UTC_TIMESTAMP WHERE id = $participantID");
		?>
		<div class="panel-heading"><?php echo $messenger_row['subject']; ?>
			<div class="dropdown pull-right"><a class="dropdown-toggle" data-toggle="dropdown"> <i class="fa fa-ellipsis-v"></i></a>
				<ul class="dropdown-menu">
				<form method="POST"><button type="submit" name="leave_conversation" class="btn btn-link" value="<?php echo $openChatID; ?>">Konversation Verlassen</button></form>
				</ul>
			</div>
		</div>
		<div id="panel_openChat_<?php echo $openChatID; ?>" class="panel-body scrollDown" style="height:40vh; overflow-y:auto;">
			<?php
			$date = '';
			$result_cw = $conn->query("SELECT * FROM (SELECT message, vKey, sentTime, m.type, rcp.partType, rcp.partID, rcp.status, rcp.lastCheck,
				archive.name AS fileName, archive.type AS fileType, m.id AS messageID
				FROM messenger_messages m LEFT JOIN relationship_conversation_participant rcp ON rcp.id = m.participantID
				LEFT JOIN archive ON m.message = archive.uniqID
				WHERE rcp.conversationID = $openChatID ORDER BY m.sentTime DESC LIMIT 20) AS tbl ORDER BY tbl.sentTime ASC");
			echo $conn->error;
			if($result_cw->num_rows == 20) echo '<a>Ältere Nachricten laden</a>';
			while($result_cw && ($row_cw = $result_cw->fetch_assoc())){
				if($date != substr($row_cw['sentTime'],0, 10)){
					$date = substr($row_cw['sentTime'],0, 10);
					echo '<p class="text-center" style="color:grey;font-size:8pt;">- ',$date,' -</p>';
				}
				echo '<div style="display:table;width:100%;">';
				if($row_cw['partType'] == 'USER' && $row_cw['partID'] == $userID){
					$style = 'background-color:#caf9b3; float:right;';
				} elseif($row_cw['partType'] == 'USER'){
					echo '<p style="font-size:75%;">',$userID_toName[$row_cw['partID']],' - ', substr(carryOverAdder_Hours($row_cw['sentTime'], $timeToUTC),11,5), '</p>';
					$style = 'float:left;';
				}
				echo '<div class="well" style="width:70%;margin-bottom:10px;',$style,'" >';
				if($row_cw['type'] == 'text') echo asymmetric_encryption('CHAT', $row_cw['message'], $userID, $privateKey, $row_cw['vKey']);
				if($row_cw['type'] == 'file' && $row_cw['fileName']) {
					echo '<form method="POST" action="../project/detailDownload" target="_blank">
					<input type="hidden" name="keyReference" value="CHAT_',$row_cw['messageID'],'" />
					<button type="submit" class="btn btn-link" name="download-file" value="',$row_cw['message'],'"><i class="fa fa-file-text-o"></i> ',$row_cw['fileName'],'.',$row_cw['fileType'],'</form>';
				} elseif($row_cw['type'] == 'file'){
					$conn->query("DELETE FROM messenger_messages WHERE id = ".$row_cw['messageID']); //remove this after the update.
				}
				echo '</div>';
				echo '</div>';
			}
			if($row_cw['partType'] == 'USER' && $row_cw['partID'] == $userID){
				$result_cw = $conn->query("SELECT partType, partID FROM relationship_conversation_participant WHERE conversationID = $openChatID
					AND lastCheck > '{$row_cw['sentTime']}' AND (partType != 'USER' OR partID != $userID)");
				if($result_cw && $result_cw->num_rows > 0){
					echo '<p style="font-size:75%;width:100%;text-align:right;">Gesehen: ';
					while($row = $result_cw->fetch_assoc()){
						if($row_cw['partType'] == 'USER')
						echo $userID_toName[$row_cw['partID']], '; ';
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
				<textarea id="chat_message_<?php echo $openChatID; ?>" autofocus name="chat_message" rows="3" class="form-control"  placeholder="Deine Nachricht... " style="resize:none"></textarea>
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
	var index = <?php echo $openChatID; ?>;
	$("#chat_message_"+index).keypress(function (e) {
		if(e.which == 13) {
			e.preventDefault();
			$("#chat_send_"+index).click();
		}
	});
	</script>
<?php endif; ?>
