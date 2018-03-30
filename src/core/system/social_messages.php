<?php require dirname(dirname(__DIR__)) . '/header.php'; ?>
<style>
.subject {
    padding: 5px;
    border-radius: 5px;
}

.subject:hover {
    background-color: #F5F5F5;
    cursor: pointer;
}
</style>

<!-- TODO: Add ability to send pictures -->
<!-- TODO: Badge -->
<!-- Page header -->
<div class="page-header-fixed">
    <div class="page-header">
        <h4>
            <?php echo $lang['MESSAGING']; ?>
            <button class="btn btn-default" data-toggle="modal" data-target="#postMessages" type="button"><i class="fa fa-plus"></i></button>
        </h4>
    </div>
</div>


<!-- Page body -->
<div class="page-content-fixed-150">
    <?php //Evaluate the post form and insert the message into the databse
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sendButton'])) {
        //Check the $_POST values
        if (empty($_POST['to_userid'])) {
            showError($lang['RECEIVER_NOT_SPECIFIED']);
        } elseif (empty($_POST['subject'])) {
            showError($lang['SUBJECT_NOT_SPECIFIED']);
        } elseif (empty($_POST['message'])) {
            showError($lang['MESSAGE_NOT_SPECIFIED']);
        } else {
            $subject = test_input($_POST['subject']);
            $message = test_input($_POST['message']);
            // messages [userID, partnerID, subject, message, picture, sent, seen]
            $stmt = $conn->prepare("INSERT INTO messages (userID, partnerID, subject, message, sent, seen) VALUES ($userID, ?, '$subject', '$message', CURRENT_TIMESTAMP, 'FALSE')");
            $stmt->bind_param('i', $to_userid);
            foreach($_POST['to_userid'] AS $to_userid){ //5abdd31716137
                $to_userid = intval($to_userid);
                if($to_userid == $userID){
                    showError($lang['INVALID_USER']);
                } else {
                    $stmt->execute();
                }
            }
            if(!$stmt->error){
                showSuccess($lang['MESSAGE_SENT']);
            } else {
                showError($stmt->error);
            }
            $stmt->close();
        }
    }
    ?>

    <!-- Post Popup -->
    <form method="POST">
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
                        <label><?php echo $lang['POST_TO']; ?> </label>
                        <select class="js-example-basic-single" name="to_userid[]" multiple="multiple">
                            <?php //5abdd31716137
                            foreach($available_users as $x){
                            echo '<option value="'.$x.'">'.$userID_toName[$x].'</option>';
                            }
                            ?>
                        </select><br>

                        <br>

                        <label for="subject"> <?php echo $lang['SUBJECT']; ?> </label>
                        <input required type="text" name="subject" class="form-control">
                        <br>

                        <label for="message"> <?php echo $lang['MESSAGE'] ?></label>
                        <textarea required name="message" class="form-control"></textarea>
                    </div>
                    <!-- modal footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                        <button type="submit" class="btn btn-warning" name="sendButton" target="#chat"><?php echo $lang['SEND']; ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Active Conversations -->
    <h4><?php echo $lang['MESSAGES']; ?></h4>

    <table class="table table-hover">
        <thead>
            <th style='white-space: nowrap;width: 1%;'><?php echo $lang['SUBJECT']; ?></th>
            <th style='white-space: nowrap;width: 2%;'></th>
        </thead>
    </table>

    <div class="row">
        <div class="col-xs-4">
            <?php
                //select all subjects
                $result = $conn->query("SELECT subject, userID, partnerID FROM messages WHERE userID = '$userID' or partnerID = '$userID' GROUP BY partnerID, subject");
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $subject = $row['subject'];
                        $x = $row['userID'];
                        $partnerID = $row['partnerID'] ;
                        echo '<div class="subject"><p style="padding: 10px" onclick="showChat('.$partnerID.', \''.$subject.'\')">'.$userID_toName[$partnerID].' - '.$subject.'</p></div>';
                    }
                } else {
                    echo mysqli_error($conn);
                }
            ?>
        </div>

        <!-- Messages -->
        <div class="col-xs-8">
            <div id="messages" style="display: none; background-color: WhiteSmoke"></div>
        </div>
    </div>
    <!-- /contacts -->
</div>


<script>
//Make the div visible, when someone clicks the button
function showChat(partner, subject) {
    getMessages(partner, subject, "#messages", false, 10);
    // make it visible
    var element = document.getElementById("messages");
    element.style.display = "block";
}

function getMessages(partner, subject, target, scroll = false, limit = 50) {
    $.ajax({
        url: 'ajaxQuery/AJAX_postGetMessage.php',
        data: {
            partner: partner,
            subject: subject,
            limit: limit,
        },
        type: 'GET',
        success: function (response) {
            $(target).html(response);
            if (scroll) $(target).parent().scrollTop($(target)[0].scrollHeight);
        },
        error: function (response) {
            $(target).html(response);
        },
    });
}
</script>


<?php require dirname(dirname(__DIR__)) . '/footer.php'; ?>
