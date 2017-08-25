<?php include 'header.php';
enableToSocialMedia($userID); ?>
<!-- BODY -->
<?php
function displayError(string $msg = "")
{
    if ($msg == "") {
        $msg = $lang['ERROR_UNEXPECTED'];
    }
    echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $msg . '</div>';
}
function displaySuccess(string $msg = "")
{
    if ($msg == "") {
        $msg = $lang['OK_SAVE'];
    }
    echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $msg . '</div>';
}


$imagePath = 'modules/social/images/'; //trailing slash
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['saveSocial'])) {
    // picture upload
    if (isset($_FILES['profilePictureUpload']) && !empty($_FILES['profilePictureUpload']['name'])) {
        if (preg_match("/.*(\.png|\.jpg|\.gif)$/", basename($_FILES["profilePictureUpload"]["name"]), $matches)) {
            $target_file = "${imagePath}$userID/profilePicture${matches[1]}";
            $check = getimagesize($_FILES["profilePictureUpload"]["tmp_name"]);
            if ($check !== false) {
                if ($_FILES["profilePictureUpload"]["size"] <= 500000) {
                    if(!file_exists("${imagePath}$userID")){
                        mkdir("${imagePath}$userID");
                    }
                    array_map('unlink', glob("${imagePath}$userID/profilePicture.*"));
                    if (move_uploaded_file($_FILES["profilePictureUpload"]["tmp_name"], $target_file)) {
                        displaySuccess($lang['SOCIAL_SUCCESS_IMAGE_UPLOAD']);
                    }
                    else {
                        displayError($lang['SOCIAL_ERR_IMAGE_UPLOAD']);
                    }
                }
                else {
                    displayError($lang['SOCIAL_ERR_IMAGE_TOO_BIG']);
                }
            }
            else {
                displayError($lang['SOCIAL_ERR_WRONG_IMAGE_TYPE']);
            }
        }
        else {
            displayError($lang['SOCIAL_ERR_WRONG_IMAGE_TYPE']);
        }
    }
    // other settings
    if(isset($_POST['status'])){
        $status = test_input($_POST['status']);
        $conn->query("UPDATE socialprofile SET status = '$status' WHERE userID = $userID");
    }
    if(isset($_POST['isAvailable'])){
        $sql = "UPDATE socialprofile SET isAvailable = 'TRUE' WHERE userID = '$userID'";
      } else {
        $sql = "UPDATE socialprofile SET isAvailable = 'FALSE' WHERE userID = '$userID'";
      }
      $conn->query($sql);
}

$result = $conn->query("SELECT * FROM socialprofile WHERE userID = $userID");
$row = $result->fetch_assoc();
$status = $row["status"];
$isAvailable = $row["isAvailable"];
$defaultPicture = "${imagePath}default.png";
$profilePicture = $defaultPicture;
if (file_exists("${imagePath}$userID/profilePicture.png")) {
    $profilePicture = "${imagePath}$userID/profilePicture.png";
}
if (file_exists("${imagePath}$userID/profilePicture.jpg")) {
    $profilePicture = "${imagePath}$userID/profilePicture.jpg";
}
if (file_exists("${imagePath}$userID/profilePicture.gif")) {
    $profilePicture = "${imagePath}$userID/profilePicture.gif";
}

?>
    <div class="page-header">
        <h3>
            <?php echo $lang['SOCIAL_MEDIA_HEADING']; ?>
        </h3>
    </div>

    <a class="btn btn-warning" data-toggle="modal" data-target="#socialSettings"><i class="fa fa-gears"></i> Social Settings</a>
    <!-- social settings modal -->
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
                    <div class="modal-body">
                        <!-- modal body -->



                        <img src='<?php echo $profilePicture; ?>' style='width:30%;height:30%;' class='img-circle center-block' alt='Profile Picture'>

                        <label>
                            <input type="file" name="profilePictureUpload">
                        </label>

                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="isAvailable" <?php if ($isAvailable == 'TRUE') { echo 'checked'; } ?>><?php echo $lang['SOCIAL_AVAILABLE']; ?>
                            </label>
                            <br>
                        </div>

                        <label for="status"> <?php echo $lang['SOCIAL_STATUS'] ?> </label>
                        <input type="text" class="form-control" name="status" placeholder="<?php echo $lang['SOCIAL_STATUS_EXAMPLE'] ?>" value="<?php echo $status; ?>">



                        <!-- /modal body -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                        <button type="submit" class="btn btn-warning" name="saveSocial"><?php echo $lang['SAVE']; ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- /social settings modal -->

    <table class="table table-hover">
        <thead>
            <th style="white-space: nowrap;width: 1%;"></th>
            <th style="white-space: nowrap;width: 1%;">Name</th>
            <th>Status</th>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM socialprofile INNER JOIN userdata ON userdata.id = socialprofile.userID INNER JOIN roles ON roles.userID = socialprofile.userID WHERE canUseSocialMedia = 'TRUE' ORDER BY isAvailable ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $x = $row['userID'];
                    $profilePicture = $defaultPicture;
                    if (file_exists("${imagePath}$x/profilePicture.png")) {
                        $profilePicture = "${imagePath}$x/profilePicture.png";
                    }
                    if (file_exists("${imagePath}$x/profilePicture.jpg")) {
                        $profilePicture = "${imagePath}$x/profilePicture.jpg";
                    }
                    if (file_exists("${imagePath}$x/profilePicture.gif")) {
                        $profilePicture = "${imagePath}$x/profilePicture.gif";
                    }
                    $name = "${row['firstname']} ${row['lastname']}";
                    $status = $row['status'];
                    $class = $row['isAvailable'] == 'TRUE' ? "success" : "danger";

                    echo "<tr class='$class'>";
                    echo "<td><img src='$profilePicture' alt='Profile picture' class='img-circle' style='width:40px;display:inline-block;'></td>";
                    echo "<td style='white-space: nowrap;width: 1%;'>$name</td>";
                    echo "<td>$status</td>";
                    echo "<td><a data-toggle='modal' class='btn btn-warning' data-target='#chat$x'>".$lang['SOCIAL_PERSONAL_MESSAGE']."</a></td>";
                    echo '</tr>';
                    ?>
                        <form method="post">
                            <div class="modal fade" id="chat<?php echo $x; ?>" tabindex="-1" role="dialog" aria-labelledby="chatLabel<?php echo $x; ?>">
                                <div class="modal-dialog" role="form">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title" id="chatLabel<?php echo $x; ?>">
                                                <?php echo $name ?>
                                            </h4>
                                        </div>
                                        <br>
                                        <div class="modal-body">
                                            <!-- modal body -->
                                            TODO: messages
                                            <!-- /modal body -->
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['RETURN']; ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php
                }
            } else {
                echo mysqli_error($conn);
            }
            ?>

        </tbody>
    </table>





    <!-- /BODY -->
    <?php include 'footer.php'; ?>