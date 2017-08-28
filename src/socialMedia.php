<?php include 'header.php';
enableToSocialMedia($userID); ?>
<!-- BODY -->
<?php
function displayError(string $msg = "")
{
    if ($msg == "") {
        $msg = $lang['ERROR_UNEXPECTED'];
    }
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $msg . '</div>';
}
function displaySuccess(string $msg = "")
{
    if ($msg == "") {
        $msg = $lang['OK_SAVE'];
    }
    echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $msg . '</div>';
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['saveSocial'])) {
    // picture upload
    if (isset($_FILES['profilePictureUpload']) && !empty($_FILES['profilePictureUpload']['name'])) {
        require __DIR__ . "/utilities.php";
        $pp = uploadFile("profilePictureUpload", 1, 1);
        if (!is_array($pp)) {
            $stmt = $conn->prepare("UPDATE socialprofile SET picture = ? WHERE userID = $userID");
            echo $conn->error;
            $null = NULL;
            $stmt->bind_param("b", $null);
            $stmt->send_long_data(0, $pp);
            $stmt->execute();
            if ($stmt->errno) {
                displayError($stmt->error);
            }
            else {
                displaySuccess($lang['SOCIAL_SUCCESS_IMAGE_UPLOAD']);
            }
            $stmt->close();
        }
        else {
            displayError(print_r($filename));
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['newGroup'])) {
    if(isset($_POST["name"],$_POST["members"])){
        var_dump($_POST);
        //TODO: foreach member insert into table
    }
}

$result = $conn->query("SELECT * FROM socialprofile WHERE userID = $userID");
$row = $result->fetch_assoc();
$status = $row["status"];
$isAvailable = $row["isAvailable"];
$defaultPicture = "images/defaultProfilePicture.png";
$defaultGroupPicture = "images/group.png";
$profilePicture = $row['picture'] ? "data:image/jpeg;base64,".base64_encode($row['picture']) : $defaultPicture;

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

        <h4><?php echo $lang['SOCIAL_CONTACTS']; ?></h4>

    <table class="table table-hover">
        <thead>
            <th style="white-space: nowrap;width: 1%;"></th>
            <th style="white-space: nowrap;width: 1%;"><?php echo $lang['SOCIAL_NAME']; ?></th>
            <th><?php echo $lang['SOCIAL_STATUS']; ?></th>
        </thead>
        <tbody>
            <?php
            $today = substr(getCurrentTimestamp(), 0, 10);
            $sql = "SELECT * FROM socialprofile INNER JOIN userdata ON userdata.id = socialprofile.userID INNER JOIN roles ON roles.userID = socialprofile.userID WHERE canUseSocialMedia = 'TRUE' ORDER BY isAvailable ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $x = $row['userID'];
                    $profilePicture = $row['picture'] ? "data:image/jpeg;base64,".base64_encode($row['picture']) : $defaultPicture;
                    $booked = $conn->query("SELECT * FROM $logTable WHERE userID = $x AND time LIKE '$today %' AND timeEnd = '0000-00-00 00:00:00'")->num_rows > 0;
                    $name = "${row['firstname']} ${row['lastname']}";
                    $status = $row['status'];
                    $class = "danger";
                    if ($row['isAvailable'] == 'TRUE') { //available + booked = green; available + !booked = yellow; !available = red
                        if ($booked) {
                            $class = "success";
                        }
                        else {
                            $class = "warning";
                        }
                    }
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
    
    <hr>
    <h4><?php echo $lang['SOCIAL_GROUPS']; ?>
        <button class="btn btn-default" data-toggle="modal" data-target="#newGroup" type="button"><?php echo $lang['SOCIAL_CREATE_GROUP']; ?></button>
    </h4>
        <!-- new group modal -->
        <form method="post" enctype="multipart/form-data">
        <div class="modal fade" id="newGroup" tabindex="-1" role="dialog" aria-labelledby="newGroupLabel">
            <div class="modal-dialog" role="form">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="newGroupLabel">
                            <?php echo $lang['SOCIAL_CREATE_GROUP']; ?>
                        </h4>
                    </div>
                    <br>
                    <div class="modal-body">
                        <!-- modal body -->
                        <img src='<?php echo $defaultGroupPicture; ?>' style='width:20%;height:20%;' class='img-circle center-block' alt='Group Picture'>
                        <br>
                        <label for="name"> <?php echo $lang['SOCIAL_NAME'] ?> </label>
                        <input type="text" class="form-control" name="name" placeholder="<?php echo $lang['SOCIAL_NAME'] ?>"><br>
                        <label><?php echo $lang['SOCIAL_GROUP_MEMBERS'] ?></label>
                        <div class="checkbox">
                            <?php 
                            $result = $conn->query("SELECT id,firstname,lastname FROM userData INNER JOIN roles ON roles.userID = userData.id WHERE canUseSocialMedia = 'TRUE'  ORDER BY lastname ASC");
                            while($row = $result->fetch_assoc()){
                                $name = "${row['firstname']} ${row['lastname']}";
                                $x = $row["id"];
                            ?>
                            <label>
                                <input type="checkbox" name="members" value="<?php echo $x; ?>" <?php if($x == $userID){echo "checked disabled";}?>><?php echo $name ?><br>
                            </label>
                            <br>
                            <?php } ?>
                        </div>
                        <!-- /modal body -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                        <button type="submit" class="btn btn-warning" name="newGroup"><?php echo $lang['SOCIAL_CREATE_GROUP']; ?></button>
                    </div>
                </div>
            </div>
        </div>
        </form>
        <!-- /new group modal -->


    <table class="table table-hover">
        <thead>
            <th style="white-space: nowrap;width: 1%;"></th>
            <th style="white-space: nowrap;width: 1%;"><?php echo $lang['SOCIAL_NAME']; ?></th>
            <th><?php echo $lang['SOCIAL_GROUP_MEMBERS']; ?></th>
        </thead>
        <tbody>
            <?php
            
            // $conn->query("CREATE TABLE socialgroups(
            //     groupID INT(6) UNSIGNED,
            //     userID INT(6) UNSIGNED,
            //     name VARCHAR(30),
            //     admin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
            //   )");
            //   $conn->query("CREATE TABLE socialgroupmessages(
            //     userID INT(6) UNSIGNED,
            //     groupID INT(6) UNSIGNED,
            //     message TEXT,
            //     picture MEDIUMBLOB,
            //     sent DATETIME DEFAULT CURRENT_TIMESTAMP,
            //     seen ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
            //   )");
            echo "<td><img src='$defaultGroupPicture' alt='Profile picture' class='img-circle' style='width:40px;display:inline-block;'><div id='badge$x' $alertsvisible class='badge row'>$alerts</div></td>";
              ?>
        </tbody>

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