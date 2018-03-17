<!-- TODO: remove static strings -->
<!-- TODO: Add ability to send pictures -->
<!-- TODO: show all conversations -->
<!-- TODO: Badge -->


<?php include dirname(dirname(__DIR__)) .'/header.php';?>


<!-- Page header -->
<div class="page-header-fixed">
    <div class="page-header">
        <h4>
            <?php echo $lang['MESSAGING']; ?>
            <button class="btn btn-default" data-toggle="modal" data-target="#postMessages" type="button"><?php echo "+"; ?></button>
        </h4>
    </div>
</div>



<!-- Page body -->
<div class="page-content-fixed-150">

    <!-- Evaluate the post form -->
    <?php
        $subject = $_POST['subject'];
        $to = $_POST['to'];
        $message = $_POST['message'];

        if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sendButton'])){
            //Check the $_POST values
            if(!isset($to) || $to == ""){
                showError($lang['RECEIVER_NOT_SPECIFIED']);
                return;
            }
            if(!isset($subject) || $subject == ""){
                showError($lang['SUBJECT_NOT_SPECIFIED']);
                return;
            }
            if(!isset($message) || $message == ""){
                showError($lang['MESSAGE_NOT_SPECIFIED']);
                return;
            }

            $sent = substr(getCurrentTimestamp(), 0, 10);

            //Find partner id TODO:
            $sql = "SELECT * FROM socialprofile INNER JOIN UserData ON UserData.id = socialprofile.userID INNER JOIN roles ON roles.userID = socialprofile.userID WHERE canUseSocialMedia = 'TRUE' AND GROUP BY UserData.id ORDER BY socialprofile.userID ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $x = $row['userID'];
                }
            }

            // messages [userID, partnerID, subject, message, picture, sent, seen]
            $conn->query("INSERT INTO messages (userID, partnerID, subject, message, sent, seen) VALUES ($userID, )");
            showInfo("Successs");
        }
    ?>

    <!-- Post Popup -->
    <form method="post">
        <div class="modal fade" id="postMessages" tabindex="-1" role="dialog" aria-labelledby="postLabel">
            <div class="modal-dialog" role="form">
                <div class="modal-content">

                    <!-- modal header -->
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="postLabel"><?php echo $lang['POST']; ?></h4>
                    </div>
                    <br>

                    <!-- modal body -->
                    <div class="modal-body">
                        <label for="to"> <?php echo $lang['POST_TO']; ?> </label>
                        <input required type="text" name="to" class="form-control" >


                        <label for="subject"> <?php echo $lang['SUBJECT']; ?> </label>
                        <input required type="text" name="subject" class="form-control">
                        
                        <label for="message"> <?php echo $lang['MESSAGE'] ?></label>
                        <textarea required name="message" class="form-control"></textarea>
                    </div>


                    <!-- modal footer -->
                    <div class="modal-footer">
                        <!-- FIXME: Cancel Button not working yet -->
                        <button type="button" class="btn btn-default" data-dsismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                        <button type="submit" class="btn btn-warning" name="sendButton" target="#chat"><?php echo $lang['SEND']; ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Active Conversations -->
    <h4><?php echo $lang['CONVERSATIONS']; ?></h4>
    <?php
        $result = $conn->query("SELECT id, firstname, lastname FROM UserData INNER JOIN roles ON roles.userID = UserData.id WHERE canUseSocialMedia = 'TRUE' ORDER BY lastname ASC");
        while($row = $result->fetch_assoc()){
            $name = "${row['firstname']} ${row['lastname']}";
            $x = $row["id"];
        }
    ?>

    <!-- Alerts -->
    <?php
        $today = substr(getCurrentTimestamp(), 0, 10);
        $sql = "SELECT * FROM socialprofile INNER JOIN UserData ON UserData.id = socialprofile.userID INNER JOIN roles ON roles.userID = socialprofile.userID WHERE canUseSocialMedia = 'TRUE' GROUP BY UserData.id ORDER BY isAvailable ASC";
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

                // Alerts
                $alerts = $conn->query("SELECT * FROM socialmessages WHERE seen = 'FALSE' AND partner = $userID AND userID = $x")->num_rows;
                $alertsvisible = $alerts == 0 ? "style='display:none;position:absolute'" : "style='position:absolute'";
                echo "<tr class='$class' data-toggle='modal' data-target='#chat$x' style='cursor:pointer;'>";
                echo "<td><img src='$profilePicture' alt='Profile picture' class='img-circle' style='width:40px;display:inline-block;'><div id='badge$x' $alertsvisible class='badge row'>$alerts</div></td>";
                echo "<td style='white-space: nowrap;width: 1%;'>$name</td>";
                echo "<td>$status</td>";
                echo '</tr>';
            }
        }
    ?>

    <!-- Show when a notification has been received. -->
    <!-- <script>setInterval(function(){updateSocialBadge("#badge<?php //echo $x; ?>", <?php //echo $x; ?>)},10000)</script> -->

    <!-- Chat Popup -->
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
</div>


<!--
TODO: Add AJAX Scripts to send and receive Messages (and one for Alerts)

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
-->
<?php include dirname(dirname(__DIR__)).'/footer.php'; ?>
