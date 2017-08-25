<?php include 'header.php';
enableToSocialMedia($userID);?>
<!-- BODY -->
<?php
// $result = $conn->query("SELECT enableReadyCheck FROM $configTable");
// $row = $result->fetch_assoc();
// $isAdmin = $conn->query("SELECT * FROM $roleTable WHERE userID = $userID AND isCoreAdmin = 'TRUE'");
// if(!$row['enableReadyCheck'] && !$isAdmin){
//   die("Access restricted, only a CORE Admin can view this page and enable it for others.");
// }




// $target_dir = "uploads/";
// $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
// $uploadOk = 1;
// $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
// // Check if image file is a actual image or fake image
// if(isset($_POST["submit"])) {
//     $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
//     if($check !== false) {
//         echo "File is an image - " . $check["mime"] . ".";
//         $uploadOk = 1;
//     } else {
//         echo "File is not an image.";
//         $uploadOk = 0;
//     }
// }
// // Check if file already exists
// if (file_exists($target_file)) {
//     echo "Sorry, file already exists.";
//     $uploadOk = 0;
// }
// // Check file size
// if ($_FILES["fileToUpload"]["size"] > 500000) {
//     echo "Sorry, your file is too large.";
//     $uploadOk = 0;
// }
// // Allow certain file formats
// if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
//     echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
//     $uploadOk = 0;
// }
// // Check if $uploadOk is set to 0 by an error
// if ($uploadOk == 0) {
//     echo "Sorry, your file was not uploaded.";
// // if everything is ok, try to upload file
// } else {
//     if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
//         echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
//     } else {
//         echo "Sorry, there was an error uploading your file.";
//     }
// }







?>
    <div class="page-header">
        <h3>
            <?php echo $lang['SOCIAL_MEDIA_HEADING']; ?>
        </h3>
    </div>

    <table class="table table-hover">
        <thead>
            <th>Name</th>
            <th>Checkin</th>
        </thead>
        <tbody>
            <?php
    $today = substr(getCurrentTimestamp(), 0, 10);
    $sql = "SELECT * FROM $logTable INNER JOIN $userTable ON $userTable.id = $logTable.userID WHERE time LIKE '$today %' AND timeEnd = '0000-00-00 00:00:00' ORDER BY lastname ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<tr><td>' . $row['firstname'] .' '. $row['lastname'] .'</td>';
            echo '<td>'. substr(carryOverAdder_Hours($row['time'], $row['timeToUTC']), 11, 5) . '</td></tr>';
        }
    } else {
        echo mysqli_error($conn);
    }
    if (isset($_POST['savePAS']) && !empty($_POST['password']) && !empty($_POST['passwordConfirm'])) {
        if (test_input($_POST['password']) != $_POST['password']) {
            die("Malicious Code Injection Detected, please do not use any HTML, SQL or Javascript specific characters.");
        }
        $password = $_POST['password'];
        $passwordConfirm = $_POST['passwordConfirm'];
        $output = '';
        if (strcmp($password, $passwordConfirm) == 0 && match_passwordpolicy($password, $output)) {
            $psw = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE $userTable SET psw = '$psw', lastPswChange = UTC_TIMESTAMP WHERE id = '$userID';";
            $conn->query($sql);
            $validation_output  = '<div class="alert alert-success fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><strong>Success! </strong>Password successfully changed.</div>';
        } else {
            $validation_output  = '<div class="alert alert-danger fade in"><a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
            $validation_output .= "<strong>Failed! </strong>Passwords did not match or were invalid. $output".'</div>';
        }
    }
    ?>

        </tbody>
    </table>


    <a class="btn btn-warning" data-toggle="modal" data-target="#socialSettings"><i class="fa fa-gears"></i> Social Settings</a>
    <!-- modal -->
    <form method="post" enctype="multipart/form-data">
        <div class="modal fade" id="socialSettings" tabindex="-1" role="dialog" aria-labelledby="socialSettingsLabel">
            <div class="modal-dialog" role="form">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="socialSettingsLabel">
                            <?php echo $lang['SOCIAL_PROFILE_SETTINGS']; ?>
                        </h4>
                    </div>
                    <br>
                    <img src='https://www.w3schools.com/howto/img_avatar.png' style='width:30%;height:30%;' class='img-responsive img-circle center-block'>
                        Select image to upload:
                        <input type="file" name="profilePicture">
                    
                    <div class="modal-body">
                        <label for="status"> <?php echo $lang['SOCIAL_STATUS']?> </label>
                            <input type="text" class="form-control" name="status" placeholder="<?php echo $lang['SOCIAL_STATUS_EXAMPLE']?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" name="savePAS"><?php echo $lang['SAVE']; ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- /modal -->







    <!-- /BODY -->
    <?php include 'footer.php'; ?>