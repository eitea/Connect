<?php include 'header.php';
enableToSocialMedia($userID); ?>
<!-- BODY -->
<?php
function displayError($msg = ""){
    if ($msg == "") {
        $msg = $lang['ERROR_UNEXPECTED'];
    }
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $msg . '</div>';
}
function displaySuccess($msg = ""){
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
    if(isset($_POST["name"],$_POST["members"]) && $_POST["name"] != ""){
        $groupID = 0;
        $name = test_input($_POST["name"]);
        while($groupID<99999){
            if($conn->query("SELECT * FROM socialgroups WHERE groupID = $groupID")->num_rows == 0){
                break;
            }else{
                $groupID++;
            }
        }
        $conn->query("INSERT INTO socialgroups (groupID, userID, admin,name) VALUES ($groupID, $userID, 'TRUE','$name')");
        foreach ($_POST["members"] as $member) {
            $conn->query("INSERT INTO socialgroups (groupID, userID, admin,name) VALUES ($groupID, $member, 'FALSE','$name')");
        }
        
        redirect("../social/home");
    }else{
        if(!isset($_POST["members"])){
            displayError($lang["SOCIAL_ERR_NO_MEMBERS"]);
        }
        if(!isset($_POST["name"]) || $_POST["name"] == ""){
            displayError($lang["SOCIAL_ERR_NO_NAME"]);
        }
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editGroup'])) {
    if(isset($_POST["name"],$_POST["members"],$_POST["id"]) && $_POST["name"] != ""){
        $name = test_input($_POST["name"]);
        $groupID = $_POST["id"];
        $conn->query("DELETE FROM socialgroups WHERE $groupID = groupID");
        $conn->query("INSERT INTO socialgroups (groupID, userID, admin,name) VALUES ($groupID, $userID, 'TRUE','$name')");
        foreach ($_POST["members"] as $key => $member) {
            $conn->query("INSERT INTO socialgroups (groupID, userID, admin,name) VALUES ($groupID, $member, 'FALSE','$name')");
        }
        redirect("../social/home");
    }else{
        if(!isset($_POST["members"])){
            displayError($lang["SOCIAL_ERR_NO_MEMBERS"]);
        }
        if(!isset($_POST["name"]) || $_POST["name"] == ""){
            displayError($lang["SOCIAL_ERR_NO_NAME"]);
        }
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteGroup'])) {
    if(isset($_POST["id"])){
        $name = test_input($_POST["name"]);
        $groupID = $_POST["id"];
        $conn->query("DELETE FROM socialgroups WHERE $groupID = groupID");
        $conn->query("DELETE FROM socialgroupmessages WHERE $groupID = groupID");
        redirect("../social/home");
    }else{
            displayError($lang["SOCIAL_ERR_NO_MEMBERS"]);
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
    <!-- contacts -->
    <table class="table table-hover">
        <thead>
            <th style="white-space: nowrap;width: 1%;"></th>
            <th style="white-space: nowrap;width: 1%;"><?php echo $lang['SOCIAL_NAME']; ?></th>
            <th><?php echo $lang['SOCIAL_STATUS']; ?></th>
        </thead>
        <tbody>
            <?php
            $today = substr(getCurrentTimestamp(), 0, 10);
            $sql = "SELECT * FROM socialprofile INNER JOIN UserData ON UserData.id = socialprofile.userID INNER JOIN roles ON roles.userID = socialprofile.userID WHERE canUseSocialMedia = 'TRUE' ORDER BY isAvailable ASC";
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
                    $alertsvisible = $alerts == 0 ? "style='display:none;position:absolute'" : "style='position:absolute'";
                    echo "<tr class='$class' data-toggle='modal' data-target='#chat$x'>";
                    echo "<td><img src='$profilePicture' alt='Profile picture' class='img-circle' style='width:40px;display:inline-block;'><div id='badge$x' $alertsvisible class='badge row'>$alerts</div></td>";
                    echo "<td style='white-space: nowrap;width: 1%;'>$name</td>";
                    echo "<td>$status</td>";
                    echo '</tr>';
                    ?>
                    <script>setInterval(function(){updateSocialBadge("#badge<?php echo $x; ?>", <?php echo $x; ?>)},10000)</script>
                    <!-- chat modal -->
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
                                                <label class="btn btn-default" title="<?php echo $lang['SOCIAL_UPLOAD_PICTURE']; ?>">
                                                    <i class="fa fa-upload" aria-hidden="true"></i>
                                                        <input type="file" id="messagePictureUpload<?php echo $x; ?>" name="picture" style="display:none">
                                                </label>
                                                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['RETURN']; ?></button>
                                            </span>
                                        </div>

                                    </form>
                                    <script>
                                        interval<?php echo $x; ?> = 0
                                        limit<?php echo $x; ?> = 10
                                        $("#chat<?php echo $x; ?>").on('show.bs.modal', function (e) {
                                            getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>", true, limit<?php echo $x; ?>)
                                            interval<?php echo $x; ?> = setInterval(function () {
                                            getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>",false, limit<?php echo $x; ?>)
                                                },1000)
                                            })
                                            $("#messages<?php echo $x; ?>").parent().scroll(function(){
                                                if($("#messages<?php echo $x; ?>").parent().scrollTop() == 0){
                                                    limit<?php echo $x; ?> += 1
                                                    $("#messages<?php echo $x; ?>").parent().scrollTop(1);
                                                    getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>",false, limit<?php echo $x; ?>)
                                                }
                                            })
                                            $("#chat<?php echo $x; ?>").on('shown.bs.modal', function (e) {
                                                $("#messages<?php echo $x; ?>").parent().scrollTop($("#messages<?php echo $x; ?>")[0].scrollHeight);
                                            })
                                            $("#chat<?php echo $x; ?>").on('hide.bs.modal', function (e) {
                                                clearInterval(interval<?php echo $x; ?>)
                                            })
                                        $("#chatinput<?php echo $x; ?>").submit(function (e) {
                                            e.preventDefault()
                                            sendMessage(<?php echo $x; ?>,$("#message<?php echo $x; ?>").val(),"#messages<?php echo $x; ?>",limit<?php echo $x; ?>)
                                        $("#message<?php echo $x; ?>").val("")
                                        return false
                                            })
                                            $("#messagePictureUpload<?php echo $x; ?>").change(function(e){
                                                e.stopPropagation()
                                                e.preventDefault()
                                                file = e.target.files[0]
                                                var data = new FormData()
                                                if(!file.type.match('image.*')){
                                                    alert("Not an image")
                                                }else if (file.size > 1048576){
                                                    alert("File too large")
                                                }else{
                                                    data.append('picture', file)
                                                    data.append('partner',<?php echo $x; ?>)
                                                    $.ajax({
                                                        url: 'ajaxQuery/AJAX_socialSendMessage.php',
                                                        dataType: 'json',
                                                        data: data,
                                                        cache: false,
                                                        type: 'POST',
                                                        processData: false,
                                                        contentType: false,
                                                        success: function (response) {
                                                            getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>", true, limit<?php echo $x; ?>)
                                                        },
                                                        error: function(response){
                                                            getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>", true, limit<?php echo $x; ?>)
                                                        }
                                                    })
                                                }
                                            })
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /chat modal -->
                <?php

            }
        }
        else {
            echo mysqli_error($conn);
        }
        ?>

        </tbody>
    </table>
    <!-- /contacts -->

    <hr>
    <h4><?php echo $lang['SOCIAL_GROUPS']; ?>
        <button class="btn btn-default" data-toggle="modal" data-target="#newGroup" type="button"><?php echo $lang['SOCIAL_CREATE_GROUP']; ?></button>
    </h4>


    <!-- /groups -->
    <table class="table table-hover">
        <thead>
            <th style="white-space: nowrap;width: 1%;"></th>
            <th style="white-space: nowrap;width: 1%;"><?php echo $lang['SOCIAL_NAME']; ?></th>
            <th><?php echo $lang['SOCIAL_GROUP_MEMBERS']; ?></th>
        </thead>
        <tbody>
            <?php
            $hue_rotate = 0;
            $sql = "SELECT groupID,name FROM socialgroups WHERE userID = $userID ORDER BY name ASC, groupID ASC";
            if($userID == 1) //superuser
                $sql = "SELECT groupID,name FROM socialgroups GROUP BY groupID ORDER BY name ASC, groupID ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $x = $row["groupID"];
                    $name = $row["name"];
                    $result_members = $conn->query("SELECT * FROM socialgroups INNER JOIN UserData ON UserData.id = socialgroups.userID  WHERE groupID = $x");
                    $alerts = $conn->query("SELECT * FROM socialgroupmessages WHERE groupID = $x AND NOT ( seen LIKE '%,$userID,%' OR seen LIKE '$userID,%' OR seen LIKE '%,$userID' OR seen =  '$userID')")->num_rows;
                    $alertsvisible = $alerts == 0 ? "style='display:none;position:absolute'" : "style='position:absolute'";
                    echo "<tr>";
                    echo "<td data-toggle='modal' data-target='#editGroup$x'><img src='$defaultGroupPicture' alt='Group picture' class='img-circle' style='width:40px;display:inline-block; -webkit-filter: hue-rotate(${hue_rotate}deg); filter: hue-rotate(${hue_rotate}deg);position:relative;'><div id='groupbadge$x' $alertsvisible class='badge row'>$alerts</div></td>";
                    echo "<td data-toggle='modal' data-target='#groupchat$x'>$name</td>";
                    echo "<td data-toggle='modal' data-target='#groupchat$x'>";
                    $userIsGroupAdmin = $userID == 1; //superuser
                    while($row = $result_members->fetch_assoc()){
                        if($row["admin"] == 'TRUE' && $row["userID"] == $userID){
                            $userIsGroupAdmin = true;
                        }
                        $class = ($row["admin"] == 'TRUE')?"label label-warning":"label label-default";
                        echo "<span class='$class'>${row['firstname']} ${row['lastname']}</span> ";
                    }
                    echo "</td>";
                    echo '</tr>';
                    ?>
                     <script>setInterval(function(){updateGroupSocialBadge("#groupbadge<?php echo $x; ?>", <?php echo $x; ?>)},10000)</script>
                      <!-- group chat modal -->  
                                <div class="modal fade" id="groupchat<?php echo $x; ?>" tabindex="-1" role="dialog" aria-labelledby="groupchatLabel<?php echo $x; ?>">  
                    <div class="modal-dialog" role="form">
                        <div class="modal-content">
                            <div class="modal-header" style="padding-bottom:5px;">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="groupchatLabel<?php echo $x; ?>">
                                    <img src='<?php echo $defaultGroupPicture; ?>' alt='Group picture' class='img-circle' style='width:25px;display:inline-block; -webkit-filter: hue-rotate(<?php echo $hue_rotate; ?>deg); filter: hue-rotate(<?php echo $hue_rotate; ?>deg);'> <?php echo $name ?>
                                </h4>
                            </div>
                            <br>
                            <div class="modal-body">
                                <div id="groupmessages<?php echo $x; ?>"></div>
                            </div>
                            <div class="modal-footer">
                                <form id="groupchatinput<?php echo $x; ?>" class="form" autocomplete="off">
                                    <div class="input-group">
                                        <input type="text" id="groupmessage<?php echo $x; ?>" class="form-control not-dirty" placeholder="<?php echo $lang['SOCIAL_GROUP_MESSAGE']; ?>">
                                        <span class="input-group-btn">
                                            <button class="btn btn-warning" type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i></button>
                                            <label class="btn btn-default" title="<?php echo $lang['SOCIAL_UPLOAD_PICTURE']; ?>">
                                                    <i class="fa fa-upload" aria-hidden="true"></i>
                                                        <input type="file" id="groupMessagePictureUpload<?php echo $x; ?>" name="picture" style="display:none">
                                            </label>
                                            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['RETURN']; ?></button>
                                        </span>
                                    </div>

                                    </form>
                                    <script>
                                        groupinterval<?php echo $x; ?> = 0
                                        grouplimit<?php echo $x; ?> = 10
                                        $("#groupchat<?php echo $x; ?>").on('show.bs.modal', function (e) {
                                            getGroupMessages(<?php echo $x; ?>, "#groupmessages<?php echo $x; ?>", true, grouplimit<?php echo $x; ?>)
                                            groupinterval<?php echo $x; ?> = setInterval(function () {
                                                getGroupMessages(<?php echo $x; ?>, "#groupmessages<?php echo $x; ?>",false, grouplimit<?php echo $x; ?>)
                                                },1000)
                                            })
                                            $("#groupmessages<?php echo $x; ?>").parent().scroll(function(){
                                                if($("#groupmessages<?php echo $x; ?>").parent().scrollTop() == 0){
                                                    grouplimit<?php echo $x; ?> += 1
                                                    $("#groupmessages<?php echo $x; ?>").parent().scrollTop(1);
                                                    getGroupMessages(<?php echo $x; ?>, "#groupmessages<?php echo $x; ?>",false, grouplimit<?php echo $x; ?>)
                                                }
                                            })
                                            $("#groupchat<?php echo $x; ?>").on('shown.bs.modal', function (e) {
                                                $("#groupmessages<?php echo $x; ?>").parent().scrollTop($("#groupmessages<?php echo $x; ?>")[0].scrollHeight);
                                            })
                                            $("#groupchat<?php echo $x; ?>").on('hide.bs.modal', function (e) {
                                                clearInterval(groupinterval<?php echo $x; ?>)
                                            })
                                        $("#groupchatinput<?php echo $x; ?>").submit(function (e) {
                                            e.preventDefault()
                                            sendGroupMessage(<?php echo $x; ?>,$("#groupmessage<?php echo $x; ?>").val(),"#groupmessages<?php echo $x; ?>", grouplimit<?php echo $x; ?>)
                                        $("#groupmessage<?php echo $x; ?>").val("")
                                        return false
                                            })
                                            $("#groupMessagePictureUpload<?php echo $x; ?>").change(function(e){
                                                e.stopPropagation()
                                                e.preventDefault()
                                                file = e.target.files[0]
                                                var data = new FormData()
                                                if(!file.type.match('image.*')){
                                                    alert("Not an image")
                                                }else if (file.size > 1048576){
                                                    alert("File too large")
                                                }else{
                                                    data.append('picture', file)
                                                    data.append('group',<?php echo $x; ?>)
                                                    $.ajax({
                                                        url: 'ajaxQuery/AJAX_socialSendMessage.php',
                                                        dataType: 'json',
                                                        data: data,
                                                        cache: false,
                                                        type: 'POST',
                                                        processData: false,
                                                        contentType: false,
                                                        success: function (response) {
                                                            getGroupMessages(<?php echo $x; ?>, "#groupmessages<?php echo $x; ?>", true, grouplimit<?php echo $x; ?>)
                                                        },
                                                        error: function(response){
                                                            getGroupMessages(<?php echo $x; ?>, "#groupmessages<?php echo $x; ?>", true, grouplimit<?php echo $x; ?>)
                                                        }
                                                    })
                                                }
                                            })
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /group chat modal --> 
                    <!-- group settings modal -->
                    <?php if($userIsGroupAdmin){ ?>
                        <form method="post" enctype="multipart/form-data">
                        <div class="modal fade" id="editGroup<?php echo $x; ?>" tabindex="-1" role="dialog" aria-labelledby="editGroupLabel<?php echo $x; ?>">
                            <div class="modal-dialog" role="form">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="editGroupLabel<?php echo $x; ?>">
                                            <?php echo $lang['SOCIAL_EDIT_GROUP']; ?>
                                        </h4>
                                    </div>
                                    <br>
                                    <div class="modal-body">
                                        <!-- modal body -->
                                        <img src='<?php echo $defaultGroupPicture; ?>' style='width:20%;height:20%; -webkit-filter: hue-rotate(<?php echo $hue_rotate; ?>deg); filter: hue-rotate(<?php echo $hue_rotate; ?>deg);' class='img-circle center-block' alt='Group Picture'>
                                        <br>
                                        <label for="name"> <?php echo $lang['SOCIAL_NAME'] ?> </label>
                                        <input type="text" class="form-control" name="name" placeholder="<?php echo $lang['SOCIAL_NAME'] ?>" value="<?php echo $name; ?>"><br>
                                        <label><?php echo $lang['SOCIAL_GROUP_MEMBERS'] ?></label>
                                        <div class="checkbox">
                                            <?php 
                                            $user_result = $conn->query("SELECT id,firstname,lastname FROM UserData INNER JOIN roles ON roles.userID = UserData.id WHERE canUseSocialMedia = 'TRUE' ORDER BY lastname ASC");
                                            
                                            while($row = $user_result->fetch_assoc()){
                                                $name = "${row['firstname']} ${row['lastname']}";
                                                $_x = $row["id"];
                                                $isMember = $conn->query("SELECT * FROM socialgroups INNER JOIN UserData ON UserData.id = socialgroups.userID WHERE groupID = $x AND userID = $_x")->num_rows > 0;
                                            ?>
                                            <label>
                                                <input type="checkbox" value="<?php echo $_x; ?>" <?php if($_x == $userID){echo "checked disabled";}else{echo ' name="members[]" ';} if($isMember){echo "checked";}?>><?php echo $name ?><br>
                                            </label>
                                            <br>
                                            <?php } ?>
                                        </div>
                                        <input type="hidden" name="id" value="<?php echo $x;?>">
                                        <!-- /modal body -->
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                                        <button type="submit" class="btn btn-danger" name="deleteGroup"><?php echo $lang['DELETE']; ?></button>
                                        <button type="submit" class="btn btn-warning" name="editGroup"><?php echo $lang['SAVE']; ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </form>
                    <?php }?>
                    <!-- /group settings modal -->
                <?php
                $hue_rotate = ( $hue_rotate + 50 )%360;
                }
            }else{
                echo $lang['SOCIAL_NO_GROUPS'];
            }
              ?>
        </tbody>
    </table>
    <!-- /groups -->
    
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
                    <img src='<?php echo $defaultGroupPicture; ?>' style='width:20%;height:20%; -webkit-filter: hue-rotate(<?php echo $hue_rotate; ?>deg); filter: hue-rotate(<?php echo $hue_rotate; ?>deg);' class='img-circle center-block' alt='Group Picture'>
                    <br>
                    <label for="name"> <?php echo $lang['SOCIAL_NAME'] ?> </label>
                    <input type="text" class="form-control" name="name" placeholder="<?php echo $lang['SOCIAL_NAME'] ?>"><br>
                    <label><?php echo $lang['SOCIAL_GROUP_MEMBERS'] ?></label>
                    <div class="checkbox">
                        <?php 
                        $result = $conn->query("SELECT id,firstname,lastname FROM UserData INNER JOIN roles ON roles.userID = UserData.id WHERE canUseSocialMedia = 'TRUE' ORDER BY lastname ASC");
                        while($row = $result->fetch_assoc()){
                            $name = "${row['firstname']} ${row['lastname']}";
                            $x = $row["id"];
                        ?>
                        <label>
                            <input type="checkbox" value="<?php echo $x; ?>" <?php if($x == $userID){echo "checked disabled";}else{echo ' name="members[]" ';}?>><?php echo $name ?><br>
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

    <script>
    function getMessages(partner, target, scroll = false, limit = 50) {
        $.ajax({
            url: 'ajaxQuery/AJAX_socialGetMessage.php',
            data: {
                partner: partner,
                limit: limit,
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
    function sendMessage(partner, message, target, limit = 50) {
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
                getMessages(partner,target, true, limit)
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
    <script>
    function getGroupMessages(group, target, scroll = false, limit = 50) {
        $.ajax({
            url: 'ajaxQuery/AJAX_socialGetMessage.php',
            data: {
                group: group,
                limit: limit,
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
    function sendGroupMessage(group, message, target, limit = 50) {
        if(message.length==0){
            return
        }
        $.ajax({
            url: 'ajaxQuery/AJAX_socialSendMessage.php',
            data: {
                group: group,
                message: message,
            },
            type: 'GET',
            success: function (response) {
                getGroupMessages(group,target, true, limit)
            },
        })
    }
    function updateGroupSocialBadge(target, group) {
        $.ajax({
            url: 'ajaxQuery/AJAX_socialGetAlerts.php',
            type: 'GET',
            data:{
                group:group,
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