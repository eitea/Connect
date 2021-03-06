<?php
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
$openChatID = 0;
$bucket = $identifier .'-uploads'; //no uppercase, no underscores, no ending dashes, no adjacent special chars
$archiveToggle = 'set2';
if(isset($_GET['toggleArchive'])){
	if($_GET['toggleArchive'] == 'set2') $archiveToggle = 'set1';
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require __DIR__.'/chatwindow_backend.php'; //sets openChatID and takes care of chatwindow.php default operations
    if (isset($_POST['send_new_message']) && !empty($_POST['new_message_subject']) && !empty($_POST['new_message_body'])) {
        $subject = test_input($_POST['new_message_subject']);
        $message =  test_input($_POST['new_message_body']);

        $stmt_participant = $conn->prepare("INSERT INTO relationship_conversation_participant(conversationID, partType, partID, status) VALUES (?, ?, ?, ?)");
        $stmt_participant->bind_param('isss', $conversationID, $partType, $partID, $status);
        $count = 1;
        if (!empty($_POST['new_message_contacts'])) {
            $count = count($_POST['new_message_contacts']);
        }
        for ($i = 0; $i < $count; $i++) {
            $identifier = uniqid();
            $conn->query("INSERT INTO messenger_conversations(identifier, subject, category) VALUES('$identifier', '$subject', 'direct')");
            if ($conn->error) {
                showError($conn->error.__LINE__);
            }
            $conversationID = $openChatID = $conn->insert_id;
            $partType = 'USER';
            $partID = $userID;
            $status = 'creator';
            $stmt_participant->execute();
            $options = ['subject' => "$subject - [CON - $identifier]"];
            if ($conn->error) {
                showError($conn->error.__LINE__);
            }
            $participantID = $conn->insert_id;
            //participants
            if (!empty($_POST['new_message_user'])) {
                foreach ($_POST['new_message_user'] as $val) {
                    $partType = 'USER';
                    $partID = intval($val);
                    $status = 'normal';
                    $stmt_participant->execute();
                }
            }
            if ($_POST['new_message_sender'] == 2 && !empty($_POST['new_message_sender_team'])) {
                $partType = 'team';
                $partID = intval($_POST['new_message_sender_team']);
                $status = 'sender';
                $stmt_participant->execute();
                $options['teamid'] = $partID;
            } else {
                $options['senderid'] = $userID;
            }
			if(isset($_FILES['new_message_files'])){ //5b45f089288db
				$s3 = getS3Object($bucket);
				for ($i = 0; $i < count($_FILES['new_message_files']['name']); $i++) {
					if($s3 && file_exists($_FILES['new_message_files']['tmp_name'][$i]) && is_uploaded_file($_FILES['new_message_files']['tmp_name'][$i])){
						$file_info = pathinfo($_FILES['new_message_files']['name'][$i]);
						$ext = test_input(strtolower($file_info['extension']));
						if (!validate_file($err, $ext, $_FILES['new_message_files']['size'][$i])){
							showError($err);
						} else {
							try{
								$hashkey = uniqid('', true); //23 chars
								$file = file_get_contents($_FILES['new_message_files']['tmp_name'][$i]);
								$file_encrypt = asymmetric_encryption('CHAT', $file, $userID, $privateKey);
								$s3->putObject(array(
									'Bucket' => $bucket,
									'Key' => $hashkey,
									'Body' => $file_encrypt
								));
								$filename = test_input($file_info['filename']);
								$options['attachments'] = [$filename => $file];
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
			} else {
				echo "NO EXISTING FILES  TES T";
				print_r($_FILES);
			}
            if (Permissions::has("POST.EXTERN")) {
                if (!empty($_POST['new_message_cc_contacts'])) {
                    foreach ($_POST['new_message_cc_contacts'] as $val) {
                        $arr = explode('_', $val);
                        $val = intval($arr[1]);
                        if ($arr[0] == 'client') {
                            $partType = 'client';
                            $result = $conn->query("SELECT name AS lastname, firstname, mail AS email FROM clientInfoData WHERE clientID = $val LIMIT 1");
                        } else {
                            $partType = 'contact';
                            $result = $conn->query("SELECT email, firstname, lastname FROM contactPersons WHERE id = $val LIMIT 1");
                        }
                        echo $conn->error;
                        if ($result && ($row = $result->fetch_assoc())) {
                            $options['cc'][$row['email']] = $row['firstname'].' '.$row['lastname'];
                        }
                    }
                }
                if (!empty($_POST['new_message_bcc_contacts'])) {
                    foreach ($_POST['new_message_bcc_contacts'] as $val) {
                        $arr = explode('_', $val);
                        $val = intval($arr[1]);
                        if ($arr[0] == 'client') {
                            $partType = 'client';
                            $result = $conn->query("SELECT name AS lastname, firstname, mail AS email FROM clientInfoData WHERE clientID = $val LIMIT 1");
                        } else {
                            $partType = 'contact';
                            $result = $conn->query("SELECT email, firstname, lastname FROM contactPersons WHERE id = $val LIMIT 1");
                        }
                        if ($result && ($row = $result->fetch_assoc())) {
                            $options['bcc'][$row['email']] = $row['firstname'].' '.$row['lastname'];
                        } else {
                            echo $conn->error.'SOC-ME-ln79';
                        }
                    }
                }
                if (!empty($_POST['new_message_contacts'])) {
                    $val = $_POST['new_message_contacts'][$i];
                    $arr = explode('_', $val);
                    $val = intval($arr[1]);
                    $status = 'normal';

                    if ($arr[0] == 'client') {
                        $partType = 'client';
                        $result = $conn->query("SELECT mail AS email, name AS lastname, firstname, title, gender FROM clientInfoData WHERE clientID = $val LIMIT 1");
                    } else {
                        $partType = 'contact';
                        $result = $conn->query("SELECT email, firstname, lastname, gender, title FROM contactPersons WHERE id = $val LIMIT 1");
                    }
                    if ($result && ($row = $result->fetch_assoc())) {
                        $partID = $row['email'];
                        $stmt_participant->execute();

                        if (strpos($message, '[ANREDE]') !== false) {
                            $intro = 'Sehr geehrter ';
                            if ($row['gender'] == 'male') {
                                $intro .= 'Herr ';
                            } else {
                                $intro .= 'Frau ';
                            }
                            $intro .= $row['title'].' '.$row['firstname'].' '.$row['lastname'];
                            $message = str_replace('[ANREDE]', $intro, $message);
                        }
                        echo send_standard_email($partID, $message, $options);
                    }
                }
            }
            $message_encrypt = asymmetric_encryption('CHAT', $message, $userID, $privateKey, 'encrypt', $err);
            $v2Key = $message_encrypt == $message ? '' : $publicKey;

            $conn->query("INSERT INTO messenger_messages(message, participantID, vKey) VALUES('$message_encrypt', $participantID, '$v2Key')
			ON DUPLICATE KEY UPDATE sentTime = DATE_ADD(UTC_TIMESTAMP, INTERVAL 1 second)");
            if ($conn->error) {
                showError($conn->error.__LINE__);
            }
        }
        $stmt_participant->close();
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang['OK_SEND']);
        }
    } elseif (isset($_POST['send_new_message'])) {
        showError("Missing Subject or Message");
    }
}
$teamID_toName = [];
$result = $conn->query("SELECT teamData.name, teamID FROM relationship_team_user INNER JOIN teamData ON teamData.id = teamID WHERE userID = $userID");
while ($row = $result->fetch_assoc()) {
    $teamID_toName[$row['teamID']] = $row['name'];
}
?>
<form>
	<div class="page-header h3">Nachrichten
	    <div class="page-header-button-group">
			<a data-toggle="modal" href="#new-message-modal" class="btn btn-default"><i class="fa fa-plus"></i></a>
			<button type="submit" name="toggleArchive" style="background-color:<?php if($archiveToggle == 'set1') {echo 'violet'; } //5b6a9acc84186 ?>"
				 value="<?php echo $archiveToggle; ?>" class="btn btn-default" title="Archivierte Nachrichten anzeigen/ ausblenden"><i class="fa fa-archive"></i>
			 </button>
		</div>
	</div>
</form>
<div class="col-md-12">
	<div class="pull-right">
		<input type="search" class="form-control" placeholder="Suchen..." id="social_message_search">
	</div>
	<table id="social_message_table" class="table table-hover">
		<thead>
			<tr>
				<th>Betreff</th>
				<th></th>
				<th>Teilnehmer</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$showArchive = "WHERE archive IS NULL";
			if($archiveToggle == 'set1') $showArchive = '';
            $stmt = $conn->prepare("SELECT partType, partID FROM relationship_conversation_participant
				WHERE conversationID = ? AND status != 'exited' AND (partType != 'USER' OR partID != '$userID')"); echo $conn->error;
            $stmt->bind_param('i', $conversationID);
            $result = $conn->query("SELECT c.id, c.category, subject, rcp.lastCheck, rcp.id AS participantID, rcp.archive, tbl.unreadMessages, mm.sentTime FROM messenger_conversations c
				INNER JOIN relationship_conversation_participant rcp
				ON (rcp.status != 'exited' AND rcp.conversationID = c.id AND rcp.partType = 'USER' AND rcp.partID = '$userID')
				LEFT JOIN (SELECT COUNT(*) AS unreadMessages, rcp1.conversationID FROM messenger_messages m
					INNER JOIN relationship_conversation_participant rcp1 ON (rcp1.id = m.participantID AND (partType != 'USER' OR partID != '$userID'))
					WHERE m.sentTime >= (SELECT lastCheck FROM relationship_conversation_participant rcp2 WHERE rcp2.conversationID = rcp1.conversationID
					AND rcp2.status != 'exited' AND rcp2.partType = 'USER' AND rcp2.partID = '$userID')
					GROUP BY conversationID) tbl
				ON tbl.conversationID = c.id
				LEFT JOIN messenger_messages mm ON mm.id = (SELECT mm2.id FROM messenger_messages mm2
					INNER JOIN relationship_conversation_participant rcp1 ON mm2.participantID = rcp1.id
					WHERE rcp1.conversationID = c.id ORDER BY mm2.id DESC LIMIT 1)
				$showArchive
				ORDER BY mm.sentTime DESC");
            echo $conn->error;
            while ($result && ($row = $result->fetch_assoc())) {
				$dataRefID = $openChatID == $row['id'] ? '' : $row['id']; //5b6a9bbe461cb
                echo '<tr class="clicker" data-val="'.$dataRefID.'">';
                echo '<td>', $row['subject'], '</td>';
                echo '<td><small>', date('d.m.Y H:i', strtotime(carryOverAdder_Hours($row['sentTime'], $timeToUTC))),'</small>',
					$row['unreadMessages'] ? '<span class="badge badge-alert" title="Ungelesene Nachrichten">'.$row['unreadMessages'] .'</span>' : '', '</td>';
                echo '<td>';
				echo '<form method="POST" id="form_openChat_',$dataRefID,'"><input type="hidden" name="openChat" value="', $dataRefID, '" ></form>';
				if($row['category'] == 'notification'){
					echo '<b style="color:blue">- System -</b>';
				} else {
	                $participantID = $row['participantID'];
					$conversationID = $row['id'];
	                $stmt->execute();
	                $partres = $stmt->get_result();
	                if ($partres->num_rows < 1) {
	                    echo '<b style="color:red">- Niemand mehr da -</b>';
	                }
	                while ($partrow = $partres->fetch_assoc()) {
	                    if ($partrow['partType'] == 'USER') {
	                        echo $userID_toName[$partrow['partID']];
	                    } elseif ($partrow['partType'] == 'team') {
	                        echo $teamID_toName[$partrow['partID']];
	                    } else { //show client and contact with email
	                        echo $partrow['partID'];
	                    }
	                    echo '<br>';
	                }
				}
                echo '</td>';
				echo '<td>';
				if($row['archive']) {
					echo '<b style="color:violet">- Archiviert -</b>';
				} else {
					echo '<form method="POST"><button type="submit" name="chat_archive" value="', $row['id']
						, '" title="Konversation Archivieren" class="btn btn-default btn-sm"><i class="fa fa-archive"></i></button></form>';
				}
				echo '</td>';
                echo '</tr> ';
                if (!$dataRefID) {
                    echo '<td colspan="4" style="padding:0">';
                    require __DIR__.'/chatwindow.php';
                    echo '</td>';
                }
                echo '</tr>';
            }

            $stmt->close();
            ?>
		</tbody>
	</table>
</div>
<form method="post" enctype="multipart/form-data">
	<div id="new-message-modal" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-content modal-md">
			<div class="modal-header"><h4>Neue Nachricht</h4></div>
			<div class="modal-body">
				<div class="row">
					<div class="col-sm-6"><label><input type="radio" name="new_message_sender" value="2" id="sender_team_radio" checked> Von Team
						<a title="Die Absender Einstellungen werden unter den Team Einstellungen gesetzt"><i class="fa fa-info-circle"></i></a>
					</label></div>

					<div class="col-sm-6"><label><input type="radio" name="new_message_sender" value="1" id="sender_personal_radio"> Von Persönlich
						<a title="Die Absender Einstellungen werden unter Allgemein/ E-mail Optionen gesetzt.
						Diese Nachrichten sind nur für einen selbst ersichtlich."><i class="fa fa-info-circle"></i>
					</a></label>
				</div>

				<div id="sender_team_box" class="col-sm-12">
					<select class="js-example-basic-single" name="new_message_sender_team">
						<?php foreach ($teamID_toName as $id => $name) {
                echo '<option value="',$id,'">', $name, '</option>';
            } ?>
					</select>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<label>An Mitarbeiter</label>
					<select class="js-example-basic-single" name="new_message_user[]" multiple>
						<?php
                        $options_message_user = '';
                        foreach ($available_users as $val) {
                            if ($val > 0) {
                                $options_message_user .= '<option value="'.$val.'">'.$userID_toName[$val].'</option>';
                            }
                        }
                        echo $options_message_user;
                        ?>
					</select>
				</div>
				<div class="col-md-6 enable-personal-role">
					<label>An externen Kontakt <a title="Öffnet für jeden Kontakt eine eigene Konversation"><i class="fa fa-info-circle"></i></a></label>
					<select class="js-example-basic-single" name="new_message_contacts[]" multiple>
						<?php
                        $options_message_contacts = '';
                        $result = $conn->query("SELECT cp.id, cp.firstname, cp.lastname, cp.email, clientData.name AS clientName, companyData.name AS companyName
							FROM contactPersons cp INNER JOIN clientData ON clientData.id = clientID INNER JOIN companyData ON companyData.id = companyID
							WHERE cp.email IS NOT NULL AND clientData.companyID IN (".implode(', ', $available_companies).")");
                        while ($result && ($row = $result->fetch_assoc())) {
                            $options_message_contacts .= '<option value="contact_'.$row['id'].'">'. $row['companyName'].' - '.$row ['clientName'].' - '.
                            $row['firstname'].' '.$row['lastname']. ' ('.$row['email']. ')</option>';
                        }
                        $result = $conn->query("SELECT clientID, clientInfoData.mail, clientData.name AS clientName, companyData.name AS companyName
							FROM clientInfoData INNER JOIN clientData ON clientData.id = clientID INNER JOIN companyData ON companyData.id = companyID
							WHERE clientInfoData.mail IS NOT NULL AND clientData.companyID IN (".implode(', ', $available_companies).")");
                            while ($result && ($row = $result->fetch_assoc())) {
                                $options_message_contacts .=  '<option value="client_'.$row['clientID'].'">'.$row['companyName'].' - '.$row ['clientName'].' ('.$row['mail'].')</option>';
                            }
                            echo $options_message_contacts;
                            ?>
						</select>
					</div>
					</div>
					<div class="row">
						<div class="col-md-12">
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
						<div class="col-md-12">
							<br>
							<label class="btn btn-default"><input type="file" name="new_message_files[]" style="display:none" multiple >Dateien Hochladen</label>
							<br>
						</div>
					</div>
					<div class="row enable-personal-role">
						<div class="col-md-6">
							<label>CC externen Kontakt</label>
							<select class="js-example-basic-single" name="new_message_cc_contacts[]" multiple>
								<?php echo $options_message_contacts; ?>
							</select>
						</div>
						<div class="col-md-6">
							<label>BCC externen Kontakt</label>
							<select class="js-example-basic-single" name="new_message_bcc_contacts[]" multiple>
								<?php echo $options_message_contacts; ?>
							</select>
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
<?php if (!Permissions::has("POST.EXTERN")): ?>
	$("input[name='new_message_sender']").change(function(){
		if($('#sender_personal_radio').is(':checked')){
			$('.enable-personal-role').hide();
		} else {
			$('.enable-personal-role').show();
		}
	});
<?php endif; ?>
	$('input[name="new_message_sender"]').on('click', function(){
		if($('#sender_team_radio').is(':checked')){
			$('#sender_team_box').show();
		} else {
			$('#sender_team_box').hide();
		}
	});

  $('.clicker').click(function(){
	  var index = $(this).data('val');
	  $("#form_openChat_"+index).submit();
  });

  <?php if ($openChatID): ?>
  var index = <?php echo $openChatID; ?>;
  var lastmessage = 0;
  setInterval(function(){
	  $.ajax({
		url:'ajaxQuery/AJAX_db_utility.php',
		data:{function: "getNextMessage", conversationID: index},
		type: 'post',
		success : function(resp){
			values = resp.split('#DIVIDE#');
			if(lastmessage != values[0]){
				lastmessage = values[0];
				$("#panel_openChat_"+index).append(values[1]);
				$('.scrollDown').each(function(){$(this).scrollTop($(this)[0].scrollHeight) });
			}
		},
		error : function(resp){alert(resp);}
	  });
  }, 25000);
  <?php endif; ?>
  //5b69879c81ea8
  $("#social_message_search").on('keyup', function(){
	  var value = $(this).val().toLowerCase();
       $("#social_message_table tr").filter(function() {
         $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
       });
  });
</script>
<?php require dirname(__DIR__) . '/footer.php'; ?>
