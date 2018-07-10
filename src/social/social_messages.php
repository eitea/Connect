<?php
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
$openChatID = 0;
$bucket = $identifier .'-uploads'; //no uppercase, no underscores, no ending dashes, no adjacent special chars
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	require __DIR__.'/chatwindow_backend.php'; //sets openChatID and takes care of chatwindow.php default operations

	if(!empty($_POST['leave_conversation'])){
		$conversationID = intval($_POST['leave_conversation']);
		$conn->query("UPDATE relationship_conversation_participant SET status = 'exited' WHERE partType='USER' AND partID = $userID AND conversationID = $conversationID");
		if($conn->error) showError($conn->error.__LINE__);
		//delete conversations which have no participants
		$conn->query("DELETE FROM messenger_conversations WHERE NOT EXISTS(SELECT id FROM relationship_conversation_participant WHERE conversationID = messenger_conversations.id)");
		if($conn->error){
			showError($conn->error);
		} else {
			showSuccess("Sie haben diese Konversation verlassen.");
		}
	}
	if(isset($_POST['send_new_message']) && !empty($_POST['new_message_subject']) && !empty($_POST['new_message_body'])){
		$subject = test_input($_POST['new_message_subject']);
		$message =  test_input($_POST['new_message_body']);
		$message_encrypt = asymmetric_encryption('CHAT', $message, $userID, $privateKey, 'encrypt', $err);
		$v2Key = $message_encrypt == $message ? '' : $publicKey;

		$stmt_participant = $conn->prepare("INSERT INTO relationship_conversation_participant(conversationID, partType, partID, status) VALUES (?, ?, ?, ?)");
		$stmt_participant->bind_param('isss', $conversationID, $partType, $partID, $status);
		$count = 1;
		if(!empty($_POST['new_message_contacts'])){ $count = count($_POST['new_message_contacts']); }
		for($i = 0; $i < $count; $i++){
			$identifier = uniqid();
			$conn->query("INSERT INTO messenger_conversations(identifier, subject, category) VALUES('$identifier', '$subject', 'direct')");
			if($conn->error) showError($conn->error.__LINE__);
			$conversationID = $openChatID = $conn->insert_id;
			$partType = 'USER';
			$partID = $userID;
			$status = 'creator';
			$stmt_participant->execute();
			$options = ['subject' => "Connect - [CON - $identifier]"];

			if($conn->error) showError($conn->error.__LINE__);
			$participantID = $conn->insert_id;

			$conn->query("INSERT INTO messenger_messages(message, participantID, vKey) VALUES('$message_encrypt', $participantID, '$v2Key')");
			if($conn->error) showError($conn->error.__LINE__);
			//participants
			if(!empty($_POST['new_message_user'])){
				foreach($_POST['new_message_user'] as $val){
					$partType = 'USER';
					$partID = intval($val);
					$status = 'normal';
					$stmt_participant->execute();
				}
			}
			if($_POST['new_message_sender'] == 2 && !empty($_POST['new_message_sender_team'])){
				$partType = 'team';
				$partID = intval($_POST['new_message_sender_team']);
				$status = 'sender';
				$stmt_participant->execute();
				$options['teamid'] = $partID;
			}
			if(!empty($_POST['new_message_cc_contacts'])){
				foreach($_POST['new_message_cc_contacts'] as $val){
					$arr = explode('_', $val);
					$val = intval($arr[1]);
					if($arr[0] == 'client'){
						$partType = 'client';
						$result = $conn->query("SELECT name AS lastname, firstname, mail AS email FROM clientInfoData WHERE clientID = $val LIMIT 1");
					} else {
						$partType = 'contact';
						$result = $conn->query("SELECT email, firstname, lastname FROM contactPersons WHERE id = $val LIMIT 1");
					}
					if($result && ($row = $result->fetch_assoc())){
						$options['cc'][$row['email']] = $row['firstname'].' '.$row['lastname'];
					}
				}
			}
			if(!empty($_POST['new_message_bcc_contacts'])){
				foreach($_POST['new_message_bcc_contacts'] as $val){
					$arr = explode('_', $val);
					$val = intval($arr[1]);
					if($arr[0] == 'client'){
						$partType = 'client';
						$result = $conn->query("SELECT name AS lastname, firstname, mail AS email FROM clientInfoData WHERE clientID = $val LIMIT 1");
					} else {
						$partType = 'contact';
						$result = $conn->query("SELECT email, firstname, lastname FROM contactPersons WHERE id = $val LIMIT 1");
					}
					if($result && ($row = $result->fetch_assoc())){
						$options['bcc'][$row['email']] = $row['firstname'].' '.$row['lastname'];
					}
				}
			}
			if(!empty($_POST['new_message_contacts'])){
				$val = $_POST['new_message_contacts'][$i];
				$arr = explode('_', $val);
				$val = intval($arr[1]);
				$status = 'normal';
				if($arr[0] == 'client'){
					$partType = 'client';
					$result = $conn->query("SELECT mail AS email FROM clientInfoData WHERE clientID = $val LIMIT 1");
				} else {
					$partType = 'contact';
					$result = $conn->query("SELECT email FROM contactPersons WHERE id = $val LIMIT 1");
				}
				if($result && ($row = $result->fetch_assoc())){
					$partID = $row['email'];
					$stmt_participant->execute();
					echo send_standard_email($partID, $message_encrypt, $options);
				}
			}
		}
		$stmt_participant->close();
		if($conn->error){
			showError($conn->error);
		} else {
			showSuccess($lang['OK_SEND']);
		}
	} elseif(isset($_POST['send_new_message'])) {
		showError("Missing Subject or Message");
	}
}

$teamID_toName = [];
$result = $conn->query("SELECT teamData.name, teamID FROM relationship_team_user INNER JOIN teamData ON teamData.id = teamID WHERE userID = $userID");
while($row = $result->fetch_assoc()){
	$teamID_toName[$row['teamID']] = $row['name'];
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
				$stmt = $conn->prepare("SELECT partType, partID FROM relationship_conversation_participant
					WHERE conversationID = ? AND status != 'exited' AND (partType != 'USER' OR partID != '$userID')"); echo $conn->error;
				$stmt->bind_param('i', $conversationID);
				$result = $conn->query("SELECT c.id, subject, rcp.lastCheck, rcp.id AS participantID, tbl.unreadMessages FROM messenger_conversations c
					INNER JOIN relationship_conversation_participant rcp
					ON (rcp.status != 'exited' AND rcp.conversationID = c.id AND rcp.partType = 'USER' AND rcp.partID = '$userID')
					LEFT JOIN (SELECT COUNT(*) AS unreadMessages, rcp1.conversationID FROM messenger_messages m
						INNER JOIN relationship_conversation_participant rcp1 ON (rcp1.id = m.participantID AND (partType != 'USER' OR partID != '$userID'))
						WHERE m.sentTime >= (SELECT lastCheck FROM relationship_conversation_participant rcp2 WHERE rcp2.conversationID = rcp1.conversationID
						AND rcp2.status != 'exited' AND rcp2.partType = 'USER' AND rcp2.partID = '$userID')
						GROUP BY conversationID) tbl
					ON tbl.conversationID = c.id");
				echo $conn->error;
				while($result && ($row = $result->fetch_assoc())){
					$conversationID = $row['id'];
					echo '<tr>';
					echo '<td>', $row['subject'], '</td>';
					echo '<td>', $row['unreadMessages'] ? '<span class="badge badge-alert" title="Ungelesene Nachrichten">'.$row['unreadMessages'] .'</span>' : '', '</td>';
					echo '<td>';
					$participantID = $row['participantID'];
					$stmt->execute();
					$partres = $stmt->get_result();
					if($partres->num_rows < 1) echo '<b style="color:red">- Niemand mehr da -</b>';
					while($partrow = $partres->fetch_assoc()){
						if($partrow['partType'] == 'USER'){
							echo $userID_toName[$partrow['partID']];
						} elseif($partrow['partType'] == 'team') {
							echo $teamID_toName[$partrow['partID']];
						} else { //show client and contact with email
							echo $partrow['partID'];
						}
						echo '<br>';
					}
					echo '</td>';
					echo '<td><div class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown"> <i class="fa fa-ellipsis-v"></i></a>
					<ul class="dropdown-menu">
					<form method="POST"><button type="submit" name="leave_conversation" class="btn btn-link" value="', $row['id'], '">Konversation Verlassen</button></form>
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
			<div class="modal-header"><h4>Neue Nachricht</h4></div>
			<div class="modal-body">
				<ul class="nav nav-tabs">
					<li class="active"><a data-toggle="tab" href="#message_message_tab">Nachricht</a></li>
					<li><a data-toggle="tab" href="#message_from_to_tab">Von - An</a></li>
					<li><a data-toggle="tab" href="#message_cc_bcc_tab">CC/ BCC</a></li>
				</ul>
				<div class="tab-content">
					<div id="message_from_to_tab" class="tab-pane fade in">
						<div class="row">
							<div class="col-sm-6"><label><input type="radio" name="new_message_sender" value="2" id="sender_team_radio" checked> Von Team
								<a title="Die Absender Einstellungen werden unter den Team Einstellungen gesetzt"><i class="fa fa-info-circle"></i></a>
							</label></div>

							<div class="col-sm-6"><label><input type="radio" name="new_message_sender" value="1"> Von Persönlich
								<a title="Die Absender Einstellungen werden unter Allgemein/ E-mail Optionen gesetzt.
									Diese Nachrichten sind nur für einen selbst ersichtlich."><i class="fa fa-info-circle"></i>
								</a></label>
							</div>

							<div id="sender_team_box" class="col-sm-12">
								<select class="js-example-basic-single" name="new_message_sender_team">
									<?php foreach($teamID_toName as $id => $name) echo '<option value="',$id,'">', $name, '</option>'; ?>
								</select>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<br><label>An Mitarbeiter</label>
								<select class="js-example-basic-single" name="new_message_user[]" multiple>
									<?php
									$options_message_user = '';
									foreach($available_users as $val){
										if($val > 0) $options_message_user .= '<option value="'.$val.'">'.$userID_toName[$val].'</option>';
									}
									echo $options_message_user;
									?>
								</select>
								<br><br>
								<label>An externen Kontakt *</label>
								<select class="js-example-basic-single" name="new_message_contacts[]" multiple>
									<?php
									$options_message_contacts = '';
									$result = $conn->query("SELECT cp.id, cp.firstname, cp.lastname, cp.email, clientData.name AS clientName, companyData.name AS companyName
										FROM contactPersons cp INNER JOIN clientData ON clientData.id = clientID INNER JOIN companyData ON companyData.id = companyID
										WHERE cp.email IS NOT NULL AND clientData.companyID IN (".implode(', ', $available_companies).")");
									while($result && ( $row = $result->fetch_assoc())){
										$options_message_contacts .= '<option value="contact_'.$row['id'].'">'. $row['companyName'].' - '.$row ['clientName'].' - '.
										$row['firstname'].' '.$row['lastname']. ' ('.$row['email']. ')</option>';
									}

									$result = $conn->query("SELECT clientID, clientInfoData.mail, clientData.name AS clientName, companyData.name AS companyName
										FROM clientInfoData INNER JOIN clientData ON clientData.id = clientID INNER JOIN companyData ON companyData.id = companyID
										WHERE clientInfoData.mail IS NOT NULL AND clientData.companyID IN (".implode(', ', $available_companies).")");
									while($result && ( $row = $result->fetch_assoc())){
										$options_message_contacts .=  '<option value="client_'.$row['clientID'].'">'.$row['companyName'].' - '.$row ['clientName'].' ('.$row['mail'].')</option>';
									}
									echo $options_message_contacts;
									?>
								</select>
								<small>* Öffnet für jedenen externen Kontakt eine eigene Konversation</small>
							</div>
						</div>
					</div>
					<div id="message_cc_bcc_tab" class="tab-pane fade">
						<h4>CC</h4>
						<div class="row">
							<div class="col-md-12">
								<label>CC Mitarbeiter</label>
								<select class="js-example-basic-single" name="new_message_cc_user[]" multiple>
									<?php echo $options_message_user; ?>
								</select>
								<br><br>
								<label>CC externen Kontakt</label>
								<select class="js-example-basic-single" name="new_message_cc_contacts[]" multiple>
									<?php echo $options_message_contacts; ?>
								</select>
							</div>
						</div>
						<h4>BCC</h4>
						<div class="row">
							<div class="col-md-12">
								<label>BCC Mitarbeiter</label>
								<select class="js-example-basic-single" name="new_message_bcc_user[]" multiple>
									<?php echo $options_message_user; ?>
								</select>
								<br><br>
								<label>BCC externen Kontakt</label>
								<select class="js-example-basic-single" name="new_message_bcc_contacts[]" multiple>
									<?php echo $options_message_contacts; ?>
								</select>
							</div>
						</div>
					</div>
					<div id="message_message_tab" class="tab-pane fade in active">
						<label>Betreff*</label>
						<input type="text" maxlength="50" name="new_message_subject" class="form-control required-field" required>
						<br>
						<label><?php echo $conn->error; echo mc_status(); ?> Nachricht* <a title="Um die automatisierte Anrede hinzuzufügen, verwenden Sie [ANREDE] um den Text
							(Sehr geehrter Herr/ Frau [titel] [vorname] [nachname]) an dessen Stelle einzufügen.
							Achten Sie bitte darauf, dass diese Einstellung vorher im Adressbuch definiert werden muss."><i class="fa fa-info-circle"></i>
						</a></label>
						<textarea name="new_message_body" rows="8" style="resize:none" class="form-control"></textarea>
						<small>*Felder werden benötigt</small>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning" name="send_new_message">Senden</button>
			</div>
		</div>
	</div>
</form>

<script type="text/javascript">
	$('input[name="new_message_sender"]').on('click', function(){
		if($('#sender_team_radio').is(':checked')){
			$('#sender_team_box').show();
		} else {
			$('#sender_team_box').hide();
		}
	});
	$('.table').DataTable({
    ordering: false,
    language: {
      <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    },
    dom: 'ftipr',
    autoWidth: false
  });
</script>
<?php require dirname(__DIR__) . '/footer.php'; ?>
