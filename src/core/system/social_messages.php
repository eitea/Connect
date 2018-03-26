<!-- TODO: remove static strings -->
<!-- TODO: Add ability to send pictures -->
<!-- TODO: show all conversations -->
<!-- TODO: Badge -->
<!-- TODO: prevent sql injection (https://www.w3schools.com/php/php_mysql_prepared_statements.asp) -->
<!-- TODO: Multiple Receivers -->
<!-- FIXME: There's a loading screen forever bc of the return (when invalid input) -->


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
                ?>

                <script>
                    sendMessage(<?php echo $partnerID; ?>, $("#message<?php echo $partnerID; ?>").val(), "#messages<?php echo $partnerID; ?>", limit<?php echo $partnerID; ?>)
                </script>

                <?php
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
        $today = substr(getCurrentTimestamp(), 0, 10);

        $sql = "SELECT userID, partnerID, firstname, lastname, subject from UserData INNER JOIN messages ON messages.partnerID = userdata.id GROUP BY subject";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $name = "${row['firstname']} ${row['lastname']}";
                $x = $row["partnerID"];
                $subject = $row['subject'];

                echo $name . " " . $x;

                echo "<tr data-toggle='modal' data-target='#chat$x' style='cursor:pointer;'>";
                echo "<td style='white-space: nowrap;width: 1%;'>$subject</td>";
                echo "<td style='white-space: nowrap;width: 1%;'>$name</td>";
                echo '</tr>';
                ?>

                <!-- chat modal -->
                <div class="modal fade" id="chat<?php echo $x; ?>" tabindex="-1" role="dialog"
                     aria-labelledby="chatLabel<?php echo $x; ?>">
                    <div class="modal-dialog" role="form">

                        <div class="modal-content">
                            <!-- Title -->
                            <div class="modal-header" style="padding-bottom:5px;">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>

                                <h4 class="modal-title" id="chatLabel<?php echo $x; ?>">
                                    <img src='<?php echo $profilePicture; ?>' alt='Profile picture' class='img-circle'
                                         style='width:25px;display:inline-block;'> <?php echo $name ?>
                                </h4>
                            </div>

                            <br>

                            <!-- All messages -->
                            <div class="modal-body">
                                <div id="messages<?php echo $x; ?>"></div>
                            </div>

                            <!-- Get the messages -->
                            <script>
                                interval<?php echo $x; ?> = 0
                                limit<?php echo $x; ?> = 10
                                $("#chat<?php echo $x; ?>").on('show.bs.modal', function (e) {
                                    getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>", true, limit<?php echo $x; ?>)
                                    interval<?php echo $x; ?> = setInterval(function () {
                                        getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>", false, limit<?php echo $x; ?>)
                                    }, 1000)
                                })

                                $("#messages<?php echo $x; ?>").parent().scroll(function () {
                                    if ($("#messages<?php echo $x; ?>").parent().scrollTop() == 0) {
                                        limit<?php echo $x; ?> += 1
                                        $("#messages<?php echo $x; ?>").parent().scrollTop(1);
                                        getMessages(<?php echo $x; ?>, "#messages<?php echo $x; ?>", false, limit<?php echo $x; ?>)
                                    }
                                })

                                $("#chat<?php echo $x; ?>").on('shown.bs.modal', function (e) {
                                    $("#messages<?php echo $x; ?>").parent().scrollTop($("#messages<?php echo $x; ?>")[0].scrollHeight);
                                })

                                $("#chatinput<?php echo $x; ?>").submit(function (e) {
                                    e.preventDefault()
                                    sendMessage(<?php echo $x; ?>, $("#message<?php echo $x; ?>").val(), "#messages<?php echo $x; ?>", limit<?php echo $x; ?>)
                                    $("#message<?php echo $x; ?>").val("")
                                    return false
                                })
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
