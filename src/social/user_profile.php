<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'header.php'; enableToSocialMedia($userID); ?>
<?php
if (isset($_POST['saveSocial']) && !empty($_POST['social_realemail'])) {
	$val = $userdata['real_email'] =  test_input($_POST['social_realemail']);
	$conn->query("UPDATE UserData SET real_email = '$val' WHERE id = $userID");

	if (isset($_FILES['profilePictureUpload']) && !empty($_FILES['profilePictureUpload']['name'])) {
		$pp = uploadImage("profilePictureUpload", 1, 1); //takes care of interlacing, size and cropping
		if (!is_array($pp)) {
			$stmt = $conn->prepare("UPDATE socialprofile SET picture = ? WHERE userID = $userID");
			$validation_output = $conn->error;
			$null = NULL;
			$stmt->bind_param("b", $null);
			$stmt->send_long_data(0, $pp);
			$stmt->execute();
			if ($stmt->errno) {
				showError($stmt->error);
			}
			$stmt->close();
		} else {
			print_r($filename);
		}
	}
	// other settings
	if (!empty($_POST['social_status'])) {
		$status = test_input($_POST['social_status']);
		$conn->query("UPDATE socialprofile SET status = '$status' WHERE userID = $userID");
	}
	if (isset($_POST['social_isAvailable'])) {
		$sql = "UPDATE socialprofile SET isAvailable = 'TRUE' WHERE userID = '$userID'";
	} else {
		$sql = "UPDATE socialprofile SET isAvailable = 'FALSE' WHERE userID = '$userID'";
	}
	$conn->query($sql);
	if (isset($_POST['social_newMessageEmail'])) {
		$sql = "UPDATE socialprofile SET new_message_email = 'TRUE' WHERE userID = '$userID'";
	} else {
		$sql = "UPDATE socialprofile SET new_message_email = 'FALSE' WHERE userID = '$userID'";
	}
	$conn->query($sql);

	//5ab7bd3310438
	if(!empty($_POST['social_birthday']) && test_Date($_POST['social_birthday'], 'Y-m-d')){
		$userdata['displayBirthday'] = isset($_POST['social_display_birthday']) ? 'TRUE' : 'FALSE';
		$userdata['birthday'] = test_input($_POST['social_birthday']);
		$conn->query("UPDATE UserData SET birthday = '".$userdata['birthday']."', displayBirthday = '".$userdata['displayBirthday']."' WHERE id = $userID");
		if($conn->error){
			showError($conn->error);
		} else {
			showSuccess($lang['OK_SAVE']);
		}
	}
} elseif(isset($_POST['saveSocial'])){
	showError("Es wurde keine E-Mail Adresse angegeben");
}
$result = $conn->query("SELECT picture, status, new_message_email, isAvailable FROM socialprofile WHERE userID = $userID"); echo $conn->error;
$social_profile = $result->fetch_assoc();
 ?>

 <form method="POST" enctype="multipart/form-data">
	<div class="page-header"><h3>Mein Profil
	 	<div class="page-header-button-group">
	 		<button type="submit" name="saveSocial" class="btn btn-default blinking" ><i class="fa fa-floppy-o"></i></button>
	 	</div> </h3>
	</div>

	<div class="row">
		<div class="col-md-4">
			<img src='<?php echo $social_profile['picture'] ? 'data:image/jpeg;base64,'.base64_encode($social_profile['picture']) : 'images/defaultProfilePicture.png'; ?>'  style="width:250px;height:250px;" class='img-circle'>
			<br><br>
			<label class="btn btn-default">
				Neues Profilbild hochladen
				<input type="file" name="profilePictureUpload" style="display:none">
			</label>
		</div>
		<div class="col-md-8">
			<div class="row">
				<div class="col-sm-5">
					<label><?php echo $lang['SOCIAL_STATUS'] ?></label>
					<input type="text" class="form-control" name="social_status" placeholder="<?php echo $lang['SOCIAL_STATUS_EXAMPLE'] ?>" value="<?php echo $social_profile['status']; ?>">
				</div>
				<div class="col-sm-7 checkbox">
					<label><br>
						<input type="checkbox" name="social_isAvailable" <?php if($social_profile['isAvailable'] == 'TRUE') {echo 'checked';} ?> ><?php echo $lang['SOCIAL_AVAILABLE']; ?>
					</label>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-5">
					<label><?php echo $lang['BIRTHDAY']; ?></label>
					<input type="text" class="form-control datepicker" name="social_birthday" placeholder="1990-01-30" value="<?php echo $userdata['birthday']; ?>" >
				</div>
				<div class="col-sm-7 checkbox">
					<label><br>
						<input type="checkbox" name="social_display_birthday" <?php if($userdata['displayBirthday'] == 'TRUE') echo 'checked'; ?> /> Im Kalender Anzeigen
					</label>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12 checkbox">
					<label>
						<input type="checkbox" name="social_newMessageEmail" <?php if($social_profile['new_message_email'] == 'TRUE') {echo 'checked';} ?> >
						Bei neuer Chat Nachricht per E-Mail informieren
					</label><br>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-5">
					<label>E-Mail Adresse</label>
					<input type="email" required name="social_realemail" value="<?php echo $userdata['real_email']; ?>" class="form-control required-field">
				</div>
			</div>
		</div>
	</div>
</form>


<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; ?>
