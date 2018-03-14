<!-- TODO: remove static strings -->
<?php include dirname(dirname(__DIR__)) .'/header.php';?>
<!-- +-Button -->
<div class="page-header-fixed">
    <div class="page-header">
        <button class="btn btn-default" data-toggle="modal" data-target="#postMessages" type="button"><?php echo "+"; ?></button>
    </div>
</div>

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
                    <button type="button" class="btn btn-default" data-dsismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning" name="sendButton"><?php echo $lang['SEND']; ?></button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Evaluate the form -->
<?php
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sendButton'])){
        //Check the $_POST values
        if(!isset($_POST['to']) || $_POST["to"] == ""){
            showError($lang['RECEIVER_NOT_SPECIFIED']);
            return;
        }
        if(!isset($_POST['subject']) || $_POST["subject"] == ""){
            showError($lang['SUBJECT_NOT_SPECIFIED']);
            return;
        }
        if(!isset($_POST['message']) || $_POST["message"] == ""){
            showError($lang['MESSAGE_NOT_SPECIFIED']);
            return;
        }

        // TODO: Insert the new message into the database 
        // TODO: Create messages table in setup_inc.php, doUpdate.php
        showInfo("Successs");
    }
?>


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
