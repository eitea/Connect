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
                    if (!file_exists("${imagePath}$userID")) {
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
    if (isset($_POST['status'])) {
        $status = test_input($_POST['status']);
        $conn->query("UPDATE socialprofile SET status = '$status' WHERE userID = $userID");
    }
    if (isset($_POST['isAvailable'])) {
        $sql = "UPDATE socialprofile SET isAvailable = 'TRUE' WHERE userID = '$userID'";
    }
    else {
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
            <div class="page-header-button-group">
                <button class="btn btn-default" data-toggle="modal" data-target="#socialSettings" type="button"><?php echo $lang['SOCIAL_PROFILE_SETTINGS']; ?></button>
            </div>
        </h3>
    </div>


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
                        <br>
                        <label class="btn btn-default">
                            <?php echo $lang['SOCIAL_UPLOAD_PICTURE']; ?>
                            <input type="file" name="profilePictureUpload" style="display:none">
                        </label>

                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="isAvailable" <?php if ($isAvailable == 'TRUE') {
                                                                                echo 'checked';
                                                                            } ?>><?php echo $lang['SOCIAL_AVAILABLE']; ?>
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
            <th style="white-space: nowrap;width: 1%;"><?php echo $lang['SOCIAL_NAME']; ?></th>
            <th><?php echo $lang['SOCIAL_STATUS']; ?></th>
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
                    $alerts = $conn->query("SELECT * FROM socialmessages WHERE seen = 'FALSE' AND partner = $userID AND userID = $x")->num_rows;
                    $alertsvisible = $alerts == 0 ? "style='display:none;'" : "";
                    echo "<tr class='$class' data-toggle='modal' data-target='#chat$x'>";
                    echo "<td><img src='$profilePicture' alt='Profile picture' class='img-circle' style='width:40px;display:inline-block;'><div id='badge$x' $alertsvisible class='badge row'>$alerts</div></td>";
                    echo "<td style='white-space: nowrap;width: 1%;'>$name</td>";
                    echo "<td>$status</td>";
                    echo '</tr>';
                    ?>
                    <script>setInterval(function(){updateSocialBadge("#badge<?php echo $x; ?>", <?php echo $x; ?>)},10000)</script>
                <div class="modal fade" id="chat<?php echo $x; ?>" tabindex="-1" role="dialog" aria-labelledby="chatLabel<?php echo $x; ?>">
                    <div class="modal-dialog" role="form">
                        <div class="modal-content">
                            <div class="modal-header" style="padding-bottom:5px;">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="chatLabel<?php echo $x; ?>">
                                    <img src='<?php echo $profilePicture; ?>' alt='Profile picture' class='img-circle' style='width:25px;display:inline-block;'> <?php echo $name ?>
                                </h4>
                            </div>
                            <br>
                            <div class="modal-body">
                                <div id="messages<?php echo $x; ?>"></div>
                            </div>
                            <div class="modal-footer">
                                <form id="chatinput<?php echo $x; ?>" class="form" autocomplete="off">
                                    <div class="input-group">
                                        <input type="text" id="message<?php echo $x; ?>" class="form-control not-dirty" placeholder="<?php echo $lang['SOCIAL_PERSONAL_MESSAGE']; ?>">
                                        <span class="input-group-btn">
                                            <button class="btn btn-warning" type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i></button>
                                            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['RETURN']; ?></button>
                                        </span>
                                    </div>

                                </form>
                                <script>
                                    interval<?php echo $x; ?> = 0
                                    $("#chat<?php echo $x; ?>").on('show.bs.modal', function (e) {
                                        getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>", true)
                                        interval<?php echo $x; ?> = setInterval(function () {
                                        getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>")
                                            },1000)
                                        })
                                        $("#chat<?php echo $x; ?>").on('shown.bs.modal', function (e) {
                                            $("#messages<?php echo $x; ?>").parent().scrollTop($("#messages<?php echo $x; ?>")[0].scrollHeight);
                                        })
                                        $("#chat<?php echo $x; ?>").on('hide.bs.modal', function (e) {
                                            clearInterval(interval<?php echo $x; ?>)
                                        })
                                    $("#chatinput<?php echo $x; ?>").submit(function (e) {
                                        e.preventDefault()
                                        sendMessage(<?php echo $x; ?>,$("#message<?php echo $x; ?>").val(),"#messages<?php echo $x; ?>")
                                    $("#message<?php echo $x; ?>").val("")
                                    return false
                                        })
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
                <?php

            }
        }
        else {
            echo mysqli_error($conn);
        }
        ?>

        </tbody>
    </table>

    <script>
    function getMessages(partner, target, scroll = false) {
        $.ajax({
            url: 'ajaxQuery/AJAX_socialGetMessage.php',
            data: {
                partner: partner
            },
            type: 'GET',
            success: function (response) {
                $(target).html(response)
                if(scroll)
                    $(target).parent().scrollTop($(target)[0].scrollHeight);
            },
            error: function (response) {
                $(target).html(response)
            },
        })
    }
    function sendMessage(partner, message, target) {
        if(message.length==0){
            return
        }
        $.ajax({
            url: 'ajaxQuery/AJAX_socialSendMessage.php',
            data: {
                partner: partner,
                message: message,
            },
            type: 'GET',
            success: function (response) {
                getMessages(partner,target, true)
            },
        })
    }
    function updateSocialBadge(target, partner) {
        $.ajax({
            url: 'ajaxQuery/AJAX_socialGetAlerts.php',
            type: 'GET',
            data:{
                partner:partner,
            },
            success: function (response) {
                $(target).html(response)
                if(response == "0"){
                    $(target).hide()
                }else{
                    $(target).show()
                }
            },
        })
    }
    </script>



    <!-- /BODY -->
    <?php include 'footer.php'; ?>