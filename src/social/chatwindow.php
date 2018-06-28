<?php if($openChatID): ?>
	<div class="panel panel-default">
		<?php
		$result = $conn->query("SELECT subject FROM messenger_conversations c WHERE c.id = $openChatID ");
		$messenger_row = $result->fetch_assoc();

		$conn->query("UPDATE relationship_conversation_participant SET lastCheck = UTC_TIMESTAMP WHERE conversationID = $openChatID AND partType='USER' AND partID = $userID");
		?>
		<div class="panel-heading"><?php echo $messenger_row['subject']; ?></div>
		<div class="panel-body scrollDown" style="height:50vh; overflow-y:auto;">
			<?php
			$date = '';
			$result = $conn->query("SELECT message, vKey, sentTime, type, rcp.partType, rcp.partID, rcp.status, rcp.lastCheck
				FROM messenger_messages m LEFT JOIN relationship_conversation_participant rcp ON rcp.id = m.participantID
				WHERE rcp.conversationID = $openChatID ORDER BY m.sentTime ASC LIMIT 20");
			echo $conn->error;
			while($result && ($row = $result->fetch_assoc())){
				if($date != substr($row['sentTime'],0, 10)){
					$date = substr($row['sentTime'],0, 10);
					echo '<p class="text-center" style="color:grey;">- ',$date,' -</p>';
				}
				echo '<div style="display:table;width:100%;">';
				if($row['partType'] == 'USER' && $row['partID'] == $userID){
					$style = 'background-color:#caf9b3; float:right;';
				} elseif($row['partType'] == 'USER'){
					echo '<p style="font-size:75%;">',$userID_toName[$row['partID']],' - ', substr(carryOverAdder_Hours($row['sentTime'], $timeToUTC),11,5), '</p>';
					$style = 'float:left;';
				}

				echo '<div class="well" style="width:70%;',$style,'" >';
				if($row['type'] == 'text') echo asymmetric_encryption('CHAT', $row['message'], $userID, $privateKey, $row['vKey']);
				echo '</div>';
				echo '</div>';
			}
			?>
		</div>
	</div>
	<form method="POST" enctype="multipart/form-data">
		<input type="hidden" readonly value="<?php echo $openChatID; ?>" name="openChat" />
		<textarea name="chat_message" rows="3" class="form-control"  placeholder="Deine Nachricht... " style="resize:none"></textarea>
		<div style="border:1px solid #cccccc;background-color: #eaeaea">
			<label class="btn btn-empty">
				<i class="fa fa-paperclip"></i>
				<input type="file" name="chat_newfile" style="display:none" >
			</label>
			<span style="float:right">
				<button type="submit" class="btn btn-link" value="<?php echo $userID; ?>" name="chat_send">Senden <i class="fa fa-paper-plane-o"></i></button>
			</span>
		</div>
	</form>
<?php endif; ?>
