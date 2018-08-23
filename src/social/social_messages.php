<?php
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
$openChatID = 0;
$bucket = $identifier .'-uploads'; //no uppercase, no underscores, no ending dashes, no adjacent special chars
$archiveToggle = 'set2';
if(isset($_GET['toggleArchive'])){
	if($_GET['toggleArchive'] == 'set2') $archiveToggle = 'set1';
}

$teamID_toName = []; // TODO: better name
$result = $conn->query("SELECT teamData.name, teamID FROM relationship_team_user INNER JOIN teamData ON teamData.id = teamID WHERE userID = $userID");
while ($row = $result->fetch_assoc()) {
    $teamID_toName[$row['teamID']] = $row['name'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require __DIR__.'/chatwindow_backend.php'; //sets openChatID and takes care of chatwindow.php default operations
    if (isset($_POST['send_new_message']) && !empty($_POST['new_message_subject']) && !empty($_POST['new_message_body']) && Permissions::has("POST.WRITE")) {
		$subject = test_input($_POST['new_message_subject']);
		$message = test_input($_POST['new_message_body']);
		$is_pm = $_POST["message_type"] == "pm";
		$is_email = $_POST["message_type"] == "email";
		$pm_additional_email = !empty($_POST["pm_additional_email"]) && $is_pm;
		$pm_additional_email_sent = []; // to prevent sending the same message to the same recipient multiple times

		$stmt_participant = $conn->prepare("INSERT INTO relationship_conversation_participant(conversationID, partType, partID, status) VALUES (?, ?, ?, ?)");
		$stmt_participant->bind_param('isss', $conversationID, $partType, $partID, $status);
		$count = 1;
		if (!empty($_POST['email_recipients']) && $is_email) {
			$count = count($_POST['email_recipients']);
		}
		for ($i = 0; $i < $count; $i++) {
			$identifier = uniqid();
			$conn->query("INSERT INTO messenger_conversations(identifier, subject, category) VALUES('$identifier', '$subject', 'direct')");
			if ($conn->error) {
				showError($conn->error . __LINE__);
			}
			$conversationID = $openChatID = $conn->insert_id;
			$partType = 'USER';
			$partID = $userID;
			$status = 'creator';
			$stmt_participant->execute();
			$options = ['subject' => "$subject - [CON - $identifier]"];
			if ($conn->error) {
				showError($conn->error . __LINE__);
			}
			$participantID = $conn->insert_id;
            //participants (team(s) or user(s))
			if (!empty($_POST['pm_recipients']) && $is_pm) {
				foreach ($_POST['pm_recipients'] as $val) {
					$arr = explode(";", $val);
					if ($arr[0] == "user") {
						$partType = 'USER';
					} else {
						$partType = 'team';
					}
					// showInfo(str_replace("\n", "<br>", print_r($arr, true)));
					$partID = intval($arr[1]);
					$status = 'normal';
					$stmt_participant->execute();
					if ($pm_additional_email) {
						$options['senderid'] = $userID;
						if ($arr[0] == "user") {
							$result = $conn->query("SELECT email FROM UserData WHERE id = $partID");
						} else {
							$result = $conn->query("SELECT email FROM UserData WHERE id IN (SELECT userID FROM relationship_team_user WHERE teamID = $partID)");
						}
						showError($conn->error);
						while ($result && $row = $result->fetch_assoc()) {
							if (isset($pm_additional_email_sent[$row["email"]]) && $pm_additional_email_sent[$row["email"]]) continue;
							$pm_additional_email_sent[$row["email"]] = true;
							send_standard_email($row["email"], $message, $options);
						}
					}
				}
			}
			if ($is_email && !empty($_POST["email_from"])) {
				$arr = explode(";", $_POST["email_from"]);
				$gpg_sign = isset($_POST["email_sign_gpg"]);
				$send_gpg_public_keys = isset($_POST["email_send_gpg_public_keys"]);
				if ($arr[0] == "user" && Permissions::has("POST.EXTERN_PERSONAL") && $arr[1] == $userID) {
					$options['senderid'] = intval($arr[1]);
					if ($gpg_sign) $options['sender_gpg_private_key'] = GPGMixins::get_gpg_key("user", intval($arr[1]));
					if ($send_gpg_public_keys) $options['attachments'] = ["PGP Key.txt" => GPGMixins::get_gpg_key("user", intval($arr[1]))["public_key"]];
				} elseif ($arr[0] == "team" && Permissions::has("POST.EXTERN_TEAM") && !empty($teamID_toName[$arr[1]])) {
					$options['teamid'] = intval($arr[1]);
					if ($gpg_sign) $options['sender_gpg_private_key'] = GPGMixins::get_gpg_key("team", intval($arr[1]));
					if ($send_gpg_public_keys) $options['attachments'] = ["PGP Key.txt" => GPGMixins::get_gpg_key("team", intval($arr[1]))["public_key"]];
				} elseif ($arr[0] == "company" && Permissions::has("POST.EXTERN_COMPANY") && in_array($arr[1], $available_companies)) {
					$options['companyid'] = intval($arr[1]);
					if ($gpg_sign) $options['sender_gpg_private_key'] = GPGMixins::get_gpg_key("company", intval($arr[1]));
					if ($send_gpg_public_keys) $options['attachments'] = ["PGP Key.txt" => GPGMixins::get_gpg_key("company", intval($arr[1]))["public_key"]];
				} else {
					showError("You don't have permission to send as this user/team/company");
					break;
				}
				if ($gpg_sign && empty($options['sender_gpg_private_key'])) {
					showError("No GPG private key found");
				}
			}

			if(isset($_FILES['new_message_files'])){ //5b45f089288db
				$s3 = getS3Object($bucket);
				for ($j = 0; $j < count($_FILES['new_message_files']['name']); $j++) {
					if($s3 && file_exists($_FILES['new_message_files']['tmp_name'][$j]) && is_uploaded_file($_FILES['new_message_files']['tmp_name'][$j])){
						$file_info = pathinfo($_FILES['new_message_files']['name'][$j]);
						$ext = test_input(strtolower($file_info['extension']));
						if (!validate_file($err, $ext, $_FILES['new_message_files']['size'][$j])){
							showError($err);
						} else {
							try{
								$hashkey = uniqid('', true); //23 chars
								$file = file_get_contents($_FILES['new_message_files']['tmp_name'][$j]);
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

            if ($is_email && Permissions::has("POST.EXTERN_PERSONAL") || Permissions::has("POST.EXTERN_TEAM") || Permissions::has("POST.EXTERN_COMPANY")){
				$gpg_encrypt = isset($_POST["email_encrypt_gpg"]);
                if (!empty($_POST['email_cc'])) {
                    foreach ($_POST['email_cc'] as $val) {
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
                if (!empty($_POST['email_bcc'])) {
                    foreach ($_POST['email_bcc'] as $val) {
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
                if (!empty($_POST['email_recipients'])) {
                    $val = $_POST['email_recipients'][$i];
                    $arr = explode('_', $val);
                    $val = intval($arr[1]);
                    $status = 'normal';

                    if ($arr[0] == 'client') {
                        $partType = 'client';
                        $result = $conn->query("SELECT mail AS email, name AS lastname, firstname, title, gender FROM clientInfoData WHERE clientID = $val LIMIT 1");
                    } else {
                        $partType = 'contact';
                        $result = $conn->query("SELECT email, firstname, lastname, gender, title, pgpKey FROM contactPersons WHERE id = $val LIMIT 1");
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
						if($gpg_encrypt && $row['pgpKey']) $options['recipient_gpg_public_key'] = $row['pgpKey'];
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

?>
<form>
	<div class="page-header h3">Nachrichten
	    <div class="page-header-button-group">
			<?php if(Permissions::has("POST.WRITE")): ?>
			<a data-toggle="modal" href="#new-message-modal" class="btn btn-default"><i class="fa fa-plus"></i></a>
			<?php endif; ?>
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
				ON (rcp.status != 'exited' AND rcp.conversationID = c.id AND ((rcp.partType = 'USER' AND rcp.partID = '$userID') OR ( rcp.partType = 'team' AND rcp.partID IN (SELECT teamID FROM relationship_team_user WHERE userID = $userID) AND NOT EXISTS (SELECT * FROM relationship_conversation_participant WHERE partType = 'USER' AND partID = '$userID' AND conversationID = c.id))))
				LEFT JOIN (SELECT COUNT(*) AS unreadMessages, rcp1.conversationID FROM messenger_messages m
					INNER JOIN relationship_conversation_participant rcp1 ON (rcp1.id = m.participantID AND (partType != 'USER' OR partID != '$userID'))
					WHERE m.sentTime >= ALL (SELECT lastCheck FROM relationship_conversation_participant rcp2 WHERE rcp2.conversationID = rcp1.conversationID
					AND rcp2.status != 'exited' AND ((rcp2.partType = 'USER' AND rcp2.partID = '$userID') OR ( rcp2.partType = 'team' AND rcp2.partID IN (SELECT teamID FROM relationship_team_user WHERE userID = $userID AND NOT EXISTS (SELECT * FROM relationship_conversation_participant WHERE partType = 'USER' AND partID = '$userID' AND conversationID = rcp2.id)))))
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
							if(isset($teamID_toName[$partrow['partID']])){
								echo $teamID_toName[$partrow['partID']]; // contains team names for teams the user is part of
							}else{
								echo $team_id_to_name[$partrow['partID']]; // contains all team names
							}
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
		<div id="new-message-modal-content" class="modal-dialog modal-content">
			<div class="modal-header"><h4>Neue Nachricht</h4></div>
			<div class="modal-body">

				<input type="hidden" name="message_type" value="pm" id="message-type-input" />
				<?php if (Permissions::has("POST.EXTERN_PERSONAL") || Permissions::has("POST.EXTERN_TEAM") || Permissions::has("POST.EXTERN_COMPANY")): ?>
				<ul class="nav nav-tabs nav-justified">
					<li class="active">
						<a href="#new-message-tab-pm" data-toggle="tab" onclick="$('#message-type-input').val('pm'); $('#new-message-modal-content').removeClass('modal-lg')">PM</a>
					</li>
					<li>
						<a href="#new-message-tab-email" data-toggle="tab" onclick="$('#message-type-input').val('email'); $('#new-message-modal-content').addClass('modal-lg')">E-Mail an externe Kontakte</a>
					</li>
				</ul>
				<?php endif; ?>

				<div class="tab-content">
					<div class="tab-pane  in active" id="new-message-tab-pm">
						<div class="row">
							<div class="col-xs-12">
								<label>An</label>
								<select class="select2-team-icons form-control" multiple name="pm_recipients[]">
									<?php
									foreach ($available_users as $val) {
										if ($val > 0) {
											echo '<option title="Benutzer" value="user;' . $val . '" data-icon="user">' . $userID_toName[$val] . '</option>';
										}
									}
									foreach ($team_id_to_name as $id => $name){
										$icon = $team_is_department[$id] ? "share-alt" : "group";
										$type = $team_is_department[$id] ? "Abteilung" : "Team";
										echo '<option title="' . $type . '" value="team;' . $id . '" data-icon="' . $icon . '">' . $name . '</option>';
									}
									?>
								</select>
								<div class="checkbox">
									<label>
										<input type="checkbox" id="pm_additional_email_checkbox" name="pm_additional_email" value="true"> Zusätzlich Email senden (Sendet jedem Benutzer im Team eine eigene Email)
									</label>
								</div>
							</div>
						</div>
					</div>
					<div class="tab-pane " id="new-message-tab-email">
						<div class="row">
							<div class="col-xs-12">
								<label>Von</label>
								<select class="select2-team-icons form-control" name="email_from">
									<?php
									$has_gpg_keys = GPGMixins::get_has_gpg_keys_list();
									if(Permissions::has("POST.EXTERN_PERSONAL")){
										$has_private_key = (isset($has_gpg_keys["user"][$userID]["private"]) && $has_gpg_keys["user"][$userID]["private"]);
										$has_public_key = (isset($has_gpg_keys["user"][$userID]["public"]) && $has_gpg_keys["user"][$userID]["public"]);
										echo '<option data-has-public-key="'.($has_public_key?"true":"false").'" data-has-private-key="'.($has_private_key?"true":"false").'" title="Benutzer" value="user;' . $userID . '" data-icon="user">' . $userID_toName[$userID] . '</option>';
									}
									if(Permissions::has("POST.EXTERN_TEAM")){
										foreach ($teamID_toName as $id => $name) {
											$icon = $team_is_department[$id] ? "share-alt" : "group";
											$type = $team_is_department[$id] ? "Abteilung" : "Team";
											$has_private_key = (isset($has_gpg_keys["team"][$id]["private"]) && $has_gpg_keys["team"][$id]["private"]);
											$has_public_key = (isset($has_gpg_keys["team"][$id]["public"]) && $has_gpg_keys["team"][$id]["public"]);
											echo '<option data-has-public-key="'.($has_public_key?"true":"false").'" data-has-private-key="'.($has_private_key?"true":"false").'" title="' . $type . '" value="team;' . $id . '" data-icon="' . $icon . '">' . $name . '</option>';
										}
									}
									if(Permissions::has("POST.EXTERN_COMPANY")){
										foreach($available_companies as $id){
											if($id > 0){
												$has_private_key = (isset($has_gpg_keys["company"][$id]["private"]) && $has_gpg_keys["company"][$id]["private"]);
												$has_public_key = (isset($has_gpg_keys["company"][$id]["public"]) && $has_gpg_keys["company"][$id]["public"]);
												echo '<option data-has-public-key="'.($has_public_key?"true":"false").'" data-has-private-key="'.($has_private_key?"true":"false").'" title="Mandant" value="company;' . $id . '" data-icon="building">' . $company_id_to_name[$id] . '</option>';
											}
										}
									}
									?>
								</select>
								<div class="checkbox">
									<label>
										<input type="checkbox" id="email_sign_gpg_checkbox" name="email_sign_gpg" value="true"> GPG Signatur
									</label>
									&NonBreakingSpace;
									<label>
										<input type="checkbox" id="email_send_gpg_public_keys_checkbox" name="email_send_gpg_public_keys" value="true"> Öffentlichen GPG Schlüssel mitsenden
									</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-xs-12">
								<label>An</label>
								<select class="select2-team-icons" name="email_recipients[]" multiple>
									<?php
									$options_message_contacts = '';
									$result = $conn->query("SELECT cp.id, cp.firstname, cp.lastname, cp.email, clientData.name AS clientName, companyData.name AS companyName, cp.pgpKey
										FROM contactPersons cp INNER JOIN clientData ON clientData.id = clientID INNER JOIN companyData ON companyData.id = companyID
										WHERE cp.email IS NOT NULL AND clientData.companyID IN (".implode(', ', $available_companies).")");
									while ($result && ($row = $result->fetch_assoc())) {
										$options_message_contacts .= '<option '.($row['pgpKey']?"data-icon='lock'":"data-icon='unlock'").' data-has-gpg-key="'.($row['pgpKey']?"true":"false").'" value="contact_'.$row['id'].'">'. $row['companyName'].' - '.$row ['clientName'].' - '.
										$row['firstname'].' '.$row['lastname']. ' ('.$row['email']. ')</option>';
									}
									$result = $conn->query("SELECT clientID, clientInfoData.mail, clientData.name AS clientName, companyData.name AS companyName
										FROM clientInfoData INNER JOIN clientData ON clientData.id = clientID INNER JOIN companyData ON companyData.id = companyID
										WHERE clientInfoData.mail IS NOT NULL AND clientData.companyID IN (".implode(', ', $available_companies).")");
										while ($result && ($row = $result->fetch_assoc())) {
											$options_message_contacts .=  '<option data-icon="unlock" data-has-gpg-key="false" value="client_'.$row['clientID'].'">'.$row['companyName'].' - '.$row ['clientName'].' ('.$row['mail'].')</option>';
										}
										echo $options_message_contacts;
									?>
								</select>
								<div class="checkbox">
									<label>
										<input type="checkbox" id="email_encrypt_gpg_checkbox" disabled name="email_encrypt_gpg" value="true"> GPG Verschlüsselung
									</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-xs-12">
								<label>CC</label>
								<select class="select2-team-icons" name="email_cc[]" multiple>
									<?php
										echo $options_message_contacts;
									?>
								</select>
							</div>
						</div>
						<div class="row">
							<div class="col-xs-12">
								<label>BCC</label>
								<select class="select2-team-icons" name="email_bcc[]" multiple>
									<?php
										echo $options_message_contacts;
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<script>
					function changeRecipientGpgEncryption(){
						var everyRecipientHasGpgKeys = true;
						// var recipients = $("[name='email_recipients[]']").find(':selected').toArray().concat(
						// 	$("[name='email_cc[]']").find(':selected').toArray().concat(
						// 		$("[name='email_bcc[]']").find(':selected').toArray()
						// 	)
						// );
						var recipients = $("[name='email_recipients[]']").find(':selected').toArray()
						if(!recipients.length) everyRecipientHasGpgKeys = false;
						recipients.forEach(function(elem){
							if(!$(elem).data("has-gpg-key")){
								everyRecipientHasGpgKeys = false;
							}
						})

						var otherRecipients = $("[name='email_cc[]']").find(':selected').toArray().concat(
							$("[name='email_bcc[]']").find(':selected').toArray()
						)
						
						if(everyRecipientHasGpgKeys && otherRecipients.length == 0){
							$("#email_encrypt_gpg_checkbox").prop("disabled", false);
						}else{
							$("#email_encrypt_gpg_checkbox").prop("disabled", true);
							$("#email_encrypt_gpg_checkbox").prop("checked", false);
						}
					}
					$("[name='email_recipients[]']").change(changeRecipientGpgEncryption)
					$("[name='email_cc[]']").change(changeRecipientGpgEncryption)
					$("[name='email_bcc[]']").change(changeRecipientGpgEncryption)
					
					function changeSenderGpgSignature(){
						var hasPrivateKey = $("[name='email_from']").find(':selected').data("has-private-key")
						var hasPublicKey = $("[name='email_from']").find(':selected').data("has-public-key")
						if(hasPrivateKey){
							$("#email_sign_gpg_checkbox").prop("disabled", false);
						}else{
							$("#email_sign_gpg_checkbox").prop("disabled", true);
							$("#email_sign_gpg_checkbox").prop("checked", false);
						}
						if(hasPublicKey){
							$("#email_send_gpg_public_keys_checkbox").prop("disabled", false);
						}else{
							$("#email_send_gpg_public_keys_checkbox").prop("disabled", true);
							$("#email_send_gpg_public_keys_checkbox").prop("checked", false);
						}
					}
					$("[name='email_from']").change(changeSenderGpgSignature)
					changeSenderGpgSignature();
				</script>


				<!-- <div class="row">
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
				</div> -->
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
				</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning" name="send_new_message">Senden</button>
			</div>
		</div>
	</div>
</form>

<script type="text/javascript">
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
