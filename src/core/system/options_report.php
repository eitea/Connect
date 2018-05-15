<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID);?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
if(isset($_POST['saveButton'])){
	if(isset($_POST['defaultOptions'])){
		$conn->query("UPDATE mailingOptions SET host = 'adminmail', port = 25, username = 'admin', password = 'admin', smtpSecure = 'SSL',
		sender = 'noreply@eitea.at', sendername = 'Connect im Auftrag von ', isDefault = 1");
	} else {
		$conn->query("UPDATE mailingOptions SET isDefault = 0");
		if(!empty($_POST['smtp_host'])){
			$val = test_input($_POST['smtp_host']);
			$conn->query("UPDATE mailingOptions SET host = '$val'");
		}
		if(!empty($_POST['smtp_port'])){
			$val = intval($_POST['smtp_port']);
			$conn->query("UPDATE mailingOptions SET port = '$val'");
		}
		if(isset($_POST['mail_username'])){
			$val = test_input($_POST['mail_username']);
			$conn->query("UPDATE mailingOptions SET username = '$val'");
		}
		if(isset($_POST['mail_password'])){
			$val = test_input($_POST['mail_password']);
			$conn->query("UPDATE mailingOptions SET password = '$val'");
		}
		if(isset($_POST['smtp_secure'])){
			$val = test_input($_POST['smtp_secure']);
			$conn->query("UPDATE mailingOptions SET smtpSecure = '$val'");
		}
		if(!empty($_POST['mail_sender'])){
			$val = test_input($_POST['mail_sender']);
			$conn->query("UPDATE mailingOptions SET sender = '$val'");
		}
		if(!empty($_POST['mail_sender_name'])){
			$val = test_input($_POST['mail_sender_name']);
			$conn->query("UPDATE mailingOptions SET sendername = '$val'");
		}
		if(!empty($_POST['feedback_mail_recipient'])){
			$val = test_input($_POST['feedback_mail_recipient']);
			$conn->query("UPDATE mailingOptions SET feedbackRecipient = '$val'");
		}
	}
	echo mysqli_error($conn);
}
if(isset($_POST['sendEmail']) && !empty($_POST['sendEmail_recipient'])){
  $error = send_standard_email($_POST['sendEmail_recipient'], '<h1>Test</h1> Das ist die Test E-Mail. <br><br> -Ende-');
  if($error){
	echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>E-Mail konnte nicht gesendet werden: '.$error.'</div>';
  }
}

$result = $conn->query("SELECT * FROM mailingOptions");
$row = $result->fetch_assoc();

$checked = $visibility = '';
if($row['isDefault'] ){
	$checked = 'checked';
	$visibility = 'style=display:none';
	$row = array_fill_keys(array_keys($row), '');
}
?>

<form method="POST">
	<div class="page-header">
		<h3>E-mail <?php echo $lang['OPTIONS']; ?>
			<div class="page-header-button-group"><button type="submit" class="btn btn-default blinking" title="<?php echo $lang['SAVE']; ?>" name="saveButton"><i class="fa fa-floppy-o"></i></button></div>
		</h3>
	</div>

	<div class="col-md-4"><h4>SMTP Einstellungen</h4></div>
	<div class="col-md-8" style="padding-left:20px"><?php echo '<input id="defaultCheck" '.$checked.' type="checkbox" name="defaultOptions">Default</input>'; ?> <br><br><br></div>
	<div class="container-fluid">
		<div id="emailContainer" <?php echo $visibility; ?> >
			<div class="col-md-4">SMTP Security</div>
			<div class="col-md-8">
				<select id = "smtpDropDown" class="js-example-basic-single" name="smtp_secure" style="width:200px">
					<option value="" <?php if($row['smtpSecure'] == ''){echo "selected";} ?>> - </option>
					<option value="tls" <?php if($row['smtpSecure'] == 'tls'){echo "selected";} ?>> TLS </option>
					<option value="ssl" <?php if($row['smtpSecure'] == 'ssl'){echo "selected";} ?>> SSL </option>
				</select><br>
			</div>
			<div class="col-md-4"> Absender-Adresse </div>
			<div class="col-md-8"><input type="text" class="form-control" name="mail_sender"  value="<?php echo $row['sender']; ?>" /><br></div>
			<div class="col-md-4"> Absender-Name </div>
			<div class="col-md-8"><input type="text" class="form-control" name="mail_sender_name"  value="<?php echo $row['senderName'];?>" /><br></div>
			<div class="col-md-4">Host</div>
			<div class="col-md-8"><input type="text" class="form-control" name="smtp_host" value="<?php echo $row['host']; ?>" /><br></div>
			<div class="col-md-4">Port</div>
			<div class="col-md-8"><input type="number" class="form-control" name="smtp_port"  value="<?php echo $row['port']; ?>" /><br></div>
			<div class="col-md-4">Username</div>
			<div class="col-md-8"><input type="text" autocomplete="new-user" class="form-control" name="mail_username" value="<?php echo $row['username']; ?>" /><br></div>
			<div class="col-md-4">Passwort</div>
			<div class="col-md-8"><input type="text" autocomplete="new-password" class="form-control password" name="mail_password" /><br></div>
		</div>
		<div class="col-md-4">Speichern und Test E-Mail verschicken</div>
		<div class="col-md-8">
			<div class="input-group">
				<input type="text" class="form-control" name="sendEmail_recipient" placeholder="empfaenger@test.com" />
				<span class="input-group-btn"><button type="submit" class="btn btn-info" name="sendEmail" title="Speichern und Test E-Mail schicken"><i class="fa fa-envelope-o"></i></button></span>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<h4>Feedback Einstellungen</h4>
	</div>
	<br/>
	<br/>
	<div class="container-fluid">
		<br/>
		<div class="col-md-4"> Feedback-Empfänger </div>
		<div class="col-md-8"><input type="text" class="form-control" name="feedback_mail_recipient"  value="<?php echo $row['feedbackRecipient']; ?>" /></div>
		<br><br>
	</div>
</form>

<script>
$('#defaultCheck').change(function(){
	$('#emailContainer').toggle(!this.checked);
});
</script>

<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
