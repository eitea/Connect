<?php 
require dirname(dirname(__DIR__)) . '/header.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>


<!-- TODO: Add ability to send pictures -->
<!-- TODO: show all conversations -->
<!-- TODO: Badge -->
<!-- TODO: prevent sql injection (https://www.w3schools.com/php/php_mysql_prepared_statements.asp) -->
<!-- TODO: Multiple Receivers -->
<!-- TODO: use AJAX scripts -->

<!-- AJAX scripts -->
<script>
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
                $(target).html(response)
                
                if (scroll)
                    $(target).parent().scrollTop($(target)[0].scrollHeight);
            },
            error: function (response) {
                $(target).html(response);
            },
        })
    }

    function sendMessage(partner, subject, message, target, limit = 50) {
        if (message.length == 0) {
            return
        }

        $.ajax({
            url: 'ajaxQuery/AJAX_socialSendMessage.php',
            data: {
                partner: partner,
                subject: subject,
                message: message,
            },
            type: 'GET',
            success: function (response) {
                getMessages(partner, subject, target, true, limit)
            },
        })
    }
</script>

<!-- Page header -->
<div class="page-header-fixed">
    <div class="page-header">
        <h4>
            <?php echo $lang['MESSAGING']; ?>
            <button class="btn btn-default" data-toggle="modal" data-target="#postMessages"
                    type="button"><?php echo "+"; ?></button>
        </h4>
    </div>
</div>


<!-- Page body -->
<div class="page-content-fixed-150">

    <!-- Evaluate the post form and insert the message into the databse -->
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sendButton'])) {
        //Check the $_POST values
        if (!isset($_POST['to']) || $_POST['to'] == "") {
            showError($lang['RECEIVER_NOT_SPECIFIED']);
        } else if (!isset($_POST['subject']) || $_POST['subject'] == "") {
            showError($lang['SUBJECT_NOT_SPECIFIED']);
        } else if (!isset($_POST['message']) || $_POST['message'] == "") {
            showError($lang['MESSAGE_NOT_SPECIFIED']);
        } else {
            // no errors
            $to = test_input($_POST['to']);
            $subject = test_input($_POST['subject']);
            $message = test_input($_POST['message']);
            $partnerID = -1;

            // select the partnerid from the database
            $sql = "SELECT id FROM UserData WHERE concat(firstname, ' ', lastname) = '{$to}' GROUP BY id LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $partnerID = $row['id'];
                }
            }

            // the partner was not found in the database or its the same user
            if ($partnerID == -1) {
                showInfo("Receiver not found!");
            } else if ($partnerID == $userID) {
                showInfo("You cannot send messages to yourself!");
            } else {
                // messages [userID, partnerID, subject, message, picture, sent, seen]
                $conn->query("INSERT INTO messages (userID, partnerID, subject, message, sent, seen) VALUES ($userID, $partnerID, '$subject', '$message', CURRENT_TIMESTAMP, 'FALSE')");
               showInfo("Message sent!");
            }
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="postLabel"><?php echo $lang['POST']; ?></h4>
                    </div>
                    <br>

                    <!-- modal body -->
                    <div class="modal-body">
                        <label for="to"> <?php echo $lang['POST_TO']; ?> </label>
                        <input required type="text" name="to" class="form-control">


                        <label for="subject"> <?php echo $lang['SUBJECT']; ?> </label>
                        <input required type="text" name="subject" class="form-control">

                        <label for="message"> <?php echo $lang['MESSAGE'] ?></label>
                        <textarea required name="message" class="form-control"></textarea>
                    </div>


                    <!-- modal footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default"
                                data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                        <button type="submit" class="btn btn-warning" name="sendButton"
                                target="#chat"><?php echo $lang['SEND']; ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>


    <!-- Active Conversations -->
    <h4><?php echo $lang['MESSAGES']; ?></h4>

    <!-- contacts -->
    <table class="table table-hover">
        <thead>
            <th><?php echo $lang['SUBJECT']; ?></th>
            <th><?php echo "Nachrichten" ?></th>
        </thead>

        <tbody>
            <div class="row">

                <!-- Subjects -->
                <div class="col-xs-6">
                    <?php
                        // the currently logged in user
                        $currentUser = $_SESSION["userid"];

                        //select all 
                        $sql = "SELECT subject, userID, partnerID FROM messages WHERE userID = '{$currentUser}' or partnerID = '{$currentUser}' GROUP BY subject";
                        $result = $conn->query($sql);
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $subject = $row['subject'];
                                $userID = $row['userID'];
                                $partnerID = $row['partnerID'] ;

                                // the real partner (sometimes was the partner the same user because of the ways the message gets saved)
                                $x = ($userID == $currentUser) ? $partnerID : $userID;

                                ?>

                                <!-- TABLE: new row --> 
                                <tr>
                                    <!-- Subject -->
                                    <td style='white-space: nowrap;width: 0.2%;' onclick="showChat<?php echo $subject; ?>()"><?php echo $subject; ?></td>
                                    
                                    <!-- Make the div visible, when someone clicks the button -->
                                    <script>
                                        function showChat<?php echo $subject; ?>() {
                                            var element = document.getElementById("messages");
                                            var limit = 10;

                                            // get the messages
                                            getMessages(<?php echo $x; ?>, "<?php echo $subject; ?>", "#messages", false, limit);
                                            element.style.display = "block";
                                        }
                                    </script>
                                </tr>

                                <?php
                            }   
                        } else {
                            echo mysqli_error($conn);
                        }
                    ?>
                </div>

                <!-- Messages -->
                <div class="col-xs-6">
                    <div id="messages" style="display: block">asdf</div>
                </div>
            </div>
        </tbody>
    </table>


      
    <!-- /contacts -->

</div>


<?php require dirname(dirname(__DIR__)) . '/footer.php'; ?>
