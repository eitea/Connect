<!-- TODO: Add ability to send pictures -->
<!-- TODO: show all conversations -->
<!-- TODO: Badge -->
<!-- TODO: prevent sql injection (https://www.w3schools.com/php/php_mysql_prepared_statements.asp) -->
<!-- TODO: Multiple Receivers -->
<!-- TODO: use AJAX scripts -->


<!-- Task: 5aa53cf53c635 -->

<?php require dirname(dirname(__DIR__)) . '/header.php'; ?>


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
            $to = $_POST['to'];
            $subject = $_POST['subject'];
            $message = $_POST['message'];
            $partnerID = -1;

            //TODO: improve

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
                echo $sql;
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
                        <!-- FIXME: Cancel Button not working yet -->
                        <button type="button" class="btn btn-default"
                                data-dsismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                        <button type="submit" class="btn btn-warning" name="sendButton"
                                target="#chat"><?php echo $lang['SEND']; ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>


    <!-- Active Conversations -->
    <h4><?php echo $lang['CONVERSATIONS']; ?></h4>

    <!-- contacts -->
    <table class="table table-hover">
        <thead>
            <th style="white-space: nowrap;width: 1%;"><?php echo $lang['SUBJECT']; ?></th>
            <th><?php echo $lang['RECEIVER']; ?></th>
        </thead>

        <tbody>
            <?php
            // the currently logged in user
            $currentUser = $_SESSION["userid"];
            $sql = "SELECT userID, partnerID, firstname, lastname, subject FROM UserData 
                INNER JOIN messages ON messages.partnerID = UserData.id 
                WHERE userID = '{$currentUser}' or partnerID = '{$currentUser}'
                GROUP BY subject";

            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $name = "${row['firstname']} ${row['lastname']}";
                    $partnerID = $row["partnerID"];
                    $subject = $row['subject'];   /*Identify the html elements with the subject*/

                    echo "<tr data-toggle='modal' data-target='#chat$subject' style='cursor:pointer;'>";
                    echo "<td style='white-space: nowrap;width: 1%;'>$subject</td>";
                    echo "<td style='white-space: nowrap;width: 1%;'>$name</td>";
                    
                    echo '</tr>';
                    ?>

                    <!-- chat modal -->
                    <div class="modal fade" id="chat<?php echo $subject; ?>" tabindex="-1" role="dialog"
                        aria-labelledby="chatLabel<?php echo $subject; ?>">
                        <div class="modal-dialog" role="form">

                            <div class="modal-content">
                                <!-- Title -->
                                <div class="modal-header" style="padding-bottom:5px;">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>

                                    <h4 class="modal-title" id="chatLabel<?php echo $subject; ?>">
                                        <img src='<?php echo $profilePicture; ?>' alt='Profile picture' class='img-circle'
                                            style='width:25px;display:inline-block;'> <?php echo $name ?>
                                    </h4>
                                </div>

                                <br>

                                <!-- All messages -->
                                <div class="modal-body">
                                    <div id="messages<?php echo $subject; ?>">
                                        <?php
                                            // select all messages
                                            $sql = "SELECT message, firstname, lastname FROM UserData 
                                                INNER JOIN messages ON messages.partnerID = UserData.id 
                                                WHERE subject = '{$subject}' 
                                                ORDER BY sent ASC";

                                            $result = $conn->query($sql);
                                            if ($result && $result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $message = $row['message'];
                                                    $name = "${row['firstname']} ${row['lastname']}";
                                                    ?>
                                                    
                                                    <!-- The message -->
                                                    <div class='row'>
                                                        <div class='col-xs-12'>
                                                            <div class='well <?php echo $pull; ?>' style='position:relative'>
                                                                <i class="fa <?php echo $seen; ?>" style="display:block;top:0px;right:-3px;position:absolute;color:#9d9d9d;"></i>
                                                                <span class="label label-default" style="display:block;top:-17px;left:0px;position:absolute;"><?php echo $name; ?></span>
                                                                <div><?php echo $message ?></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php
                                                }
                                            }
                                        ?>
                                    </div>
                                </div>

                                <!-- AJAX stuff -->
                                <!--<script>
                                    interval<?php echo $subject; ?> = 0
                                    limit<?php echo $subject; ?> = 10
                                    $("#chat<?php echo $subject; ?>").on('show.bs.modal', function (e) {
                                        getMessages(<?php echo $subject; ?>, "#messages<?php echo $subject; ?>", true, limit<?php echo $subject; ?>)
                                        interval<?php echo $subject; ?> = setInterval(function () {
                                            getMessages(<?php echo $subject; ?>, "#messages<?php echo $subject; ?>", false, limit<?php echo $subject; ?>)
                                        }, 1000)
                                    })

                                    // scroll
                                    $("#messages<?php echo $subject; ?>").parent().scroll(function () {
                                        if ($("#messages<?php echo $subject; ?>").parent().scrollTop() == 0) {
                                            limit<?php echo $subject; ?> += 1
                                            $("#messages<?php echo $subject; ?>").parent().scrollTop(1);
                                            getMessages(<?php echo $subject; ?>, "#messages<?php echo $subject; ?>", false, limit<?php echo $subject; ?>)
                                        }
                                    })

                                    // scroll to top
                                    $("#chat<?php echo $subject; ?>").on('shown.bs.modal', function (e) {
                                        $("#messages<?php echo $subject; ?>").parent().scrollTop($("#messages<?php echo $subject; ?>")[0].scrollHeight);
                                    })-->
                                </script>
                            </div>
                        </div>
                    </div>
                    <!-- /chat modal -->
                    <?php

                }
            } else {
                echo mysqli_error($conn);
            }
            ?>
        </tbody>
    </table>
    <!-- /contacts -->

</div>


<!-- TODO: Add AJAX Scripts for alerts -->

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
                if (scroll)
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
        if (message.length == 0) {
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
                getMessages(partner, target, true, limit)
            },
        })
    }
</script>

<?php require dirname(dirname(__DIR__)) . '/footer.php'; ?>
