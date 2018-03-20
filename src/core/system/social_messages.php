<!-- TODO: remove static strings -->
<!-- TODO: Add ability to send pictures -->
<!-- TODO: show all conversations -->
<!-- TODO: Badge -->
<!-- TODO: prevent sql injection (https://www.w3schools.com/php/php_mysql_prepared_statements.asp) -->
<!-- TODO: Multiple Receivers -->
<!-- FIXME: There's a loading screen forever bc of the return (when invalid input) --> 

<?php require dirname(dirname(__DIR__)) .'/header.php';?>


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

    <!-- Evaluate the post form and insert the message into the databse -->
    <?php
        if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sendButton'])){
            //Check the $_POST values
            if(!isset($_POST['to']) || $_POST['to'] == ""){
                showError($lang['RECEIVER_NOT_SPECIFIED']);
            }else if(!isset($_POST['subject']) || $_POST['subject'] == ""){
                showError($lang['SUBJECT_NOT_SPECIFIED']);
            }else if(!isset($_POST['message']) || $_POST['message'] == ""){
                showError($lang['MESSAGE_NOT_SPECIFIED']);
            }else {
                // no errors
                $to = $_POST['to'];
                $subject = $_POST['subject'];
                $message = $_POST['message'];
                $partnerID = -1;

                // select the partnerid from the database
                $sql = "SELECT * FROM socialprofile INNER JOIN userdata ON userdata.id = socialprofile.userID WHERE concat(firstname, ' ', lastname) = '{$to}' GROUP BY userdata.id LIMIT 1";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $partnerID = $row['userID'];
                    }
                }

                // the partner was not found in the database or its the same user
                if($partnerID == -1 || $partnerID == $userID){
                    showInfo("Invalid Receiver");
                    return;
                }

                // messages [userID, partnerID, subject, message, picture, sent, seen]
                $conn->query("INSERT INTO messages (userID, partnerID, subject, message, sent, seen) VALUES ($userID, $partnerID, '$subject', '$message', CURRENT_TIMESTAMP, 'FALSE')");        
                
                ?>
                <script>
                    sendMessage(<?php echo $partnerID; ?>,$("#message<?php echo $partnerID; ?>").val(),"#messages<?php echo $partnerID; ?>",limit<?php echo $partnerID; ?>)
                </script>
                <?php

                showInfo("Message sent!");
                
            }
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
    <script>
        getMessages(<?php echo $partnerID; ?>, "#messages<?php echo $partnerID; ?>", true, limit<?php echo $partnerID; ?>)
    </script>
    
    <?php

        $sql = "SELECT partnerID, firstname, lastname from UserData INNER JOIN messages ON messages.partnerID = userdata.id GROUP BY partnerID";
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()){
            $name = "${row['firstname']} ${row['lastname']}";
            $x = $row["partnerID"];
            echo "Conversation: " . $name . " - ID: " . $x . "<br>";
        }
    ?>

    



</div>


<!-- TODO: Add AJAX Scripts to send and receive Messages (and one for Alerts) -->

<script>
    /**
    *  Receive new messages
    * partner: the user_id of your conversation partner
    * scroll: 
    * limit: 
    */
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

    /**
    *  Send messages
    * partner: the user_id of your conversation partner
    * scroll: 
    * limit: 
    */
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
</script>

<?php include dirname(dirname(__DIR__)).'/footer.php'; ?>
