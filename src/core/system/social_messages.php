<?php 
require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'header.php'; 
/*

Tables: 
+ messages: sent messages
+ taskmessages: sent messages for tasks

 */

 //Evaluate the post form and insert the message into the databse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch (true) {
        case isset($_POST['createGroup']) && !empty($_POST['name']):
            $name = test_input($_POST['name']);
            $conn->query("INSERT INTO messagegroups (subject) VALUES('$name')");
            showError($conn->error);
            $groupID = mysqli_insert_id($conn);
            $conn->query("INSERT INTO messagegroups_user (userID, groupID, admin) VALUES ($userID, $groupID, 'TRUE')");
            if ($conn->error) {
                showError($conn->error);
            } else {
                showSuccess($lang["OK_ADD"]);
            }
            break;
        case isset($_POST['sendButton']):
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
                foreach ($_POST['to_userid'] as $to_userid) { //5abdd31716137
                    $to_userid = intval($to_userid);
                    if ($to_userid == $userID) {
                        showError($lang['INVALID_USER']);
                    } else {
                        $stmt->execute();
                    }
                }
                if (!$stmt->error) {
                    showSuccess($lang['MESSAGE_SENT']);
                } else {
                    showError($stmt->error);
                }
                $stmt->close();
            }
            break;
        case isset($_POST['saveGroup'], $_POST['members']):
            $groupID = intval($_POST["saveGroup"]);
            // only allow admins to edit groups 
            $result = $conn->query("SELECT admin FROM messagegroups_user WHERE groupID = $groupID AND userID = $userID");
            if (!$result || $result->num_rows == 0) break;
            $isAdmin = $result->fetch_assoc()["admin"] == "TRUE";
            if (!$isAdmin) break;

            $conn->query("DELETE FROM messagegroups_user WHERE groupID = $groupID");
            showError($conn->error);
            $stmtMember = $conn->prepare("INSERT INTO messagegroups_user (userID, groupID, admin) VALUES (?, $groupID, ?)");
            showError($conn->error);
            $stmtMember->bind_param("is", $memberID, $isAdmin);
            if (isset($_POST["admins"])) {
                foreach ($_POST["admins"] as $admin) {
                    $memberID = intval($admin);
                    if ($memberID === -1) continue;
                    $isAdmin = "TRUE";
                    $stmtMember->execute();
                    // showError($stmtMember->error);
                }
            } else {
                $memberID = $userID;
                $isAdmin = "TRUE";
                $stmtMember->execute();
                // showError($stmtMember->error);
            }
            foreach ($_POST["members"] as $member) {
                $memberID = intval($member);
                if ($memberID === -1) continue;
                $isAdmin = "FALSE";
                $stmtMember->execute();
                // showError($stmtMember->error);
            }
            $conn->query("DELETE FROM messagegroups WHERE id NOT IN (SELECT groupID FROM messagegroups_user)"); // remove groups with no members
            showError($conn->error);
            break;
    }
}

?>


<style>
    .subject {
        padding: 5px;
    }

    .subject:hover {
        cursor: pointer;

    }
</style>

<!-- Page header -->
<div class="page-header">
    <h4>
        <?php echo $lang['MESSAGING']; ?>
        <div class="page-header-button-group">
            <span data-container="body" data-toggle="tooltip" title="Neue Gruppe">
                <button class="btn btn-default" data-toggle="modal" data-target="#postMessages" type="button">
                    <i class="fa fa-plus"></i> Einzelchat</button>
            </span>
            <span data-container="body" data-toggle="tooltip" title="Neue Gruppe">
                <button type="button" data-toggle="modal" data-target="#newGroupModal" class="btn btn-default">
                    <i class="fa fa-plus"></i> Gruppenchat</button>
            </span>
        </div>
    </h4>
</div>


<!-- new group modal -->

<form method="post">
    <div id="newGroupModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <i class="fa fa-users"></i> Neue Gruppe</h4>
                </div>
                <div class="modal-body">
                    <label>Betreff</label>
                    <input type="text" class="form-control" name="name" placeholder="Meine tolle Gruppe" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-warning" name="createGroup" value="true">
                        <?php echo $lang['ADD']; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- /new group modal -->


<!-- Page body -->
<div class="page-content-fixed-50">

    <!-- Post Popup -->
    <form method="POST" autocomplete="off">
        <div class="modal fade" id="postMessages" tabindex="-1" role="dialog" aria-labelledby="postLabel">
            <div class="modal-dialog" role="form">
                <div class="modal-content">

                    <!-- modal header -->
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="postLabel">
                            <?php echo $lang['POST']; ?>
                        </h4>
                    </div>
                    <br>

                    <!-- modal body -->
                    <div class="modal-body">
                        <label>
                            <?php echo $lang['POST_TO']; ?> </label>
                        <select required class="js-example-basic-single" name="to_userid[]" multiple="multiple">
                            <?php //5abdd31716137
                            foreach ($userID_toName as $x => $name) {
                                if ($x == -1 || $x == $userID) continue;
                                echo '<option value="' . $x . '">' . $name . '</option>';
                            }
                            ?>
                        </select>
                        <br>
                        <br>

                        <label for="subject">
                            <?php echo $lang['SUBJECT']; ?> </label>
                        <input required id="subject" type="text" maxlength="250" name="subject" class="form-control">
                        <div id="textarea_count" class="pull-right" style="padding-top: 0.5em;"></div>
                        <script>
                            //for the character counter
                            var text_max = 250;
                            $('#textarea_count').html(text_max + ' remaining');

                            $('#subject').on("change keyup keydown paste cut", function () {
                                var text_length = $('#subject').val().length;
                                var text_remaining = text_max - text_length;

                                $('#textarea_count').html(text_remaining + ' remaining')
                            });
                        </script>
                        <br>

                        <label for="message">
                            <?php echo $lang['MESSAGE'] ?>
                        </label>
                        <textarea required id="post_message" name="message" class="form-control" rows="6" wrap="hard" style="resize: none"></textarea>
                    </div>

                    <!-- modal footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo $lang['CANCEL']; ?>
                        </button>
                        <button type="submit" class="btn btn-warning" name="sendButton">
                            <?php echo $lang['SEND']; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Active Conversations -->
    <table class="table table-hover">
        <thead>
            <th style='white-space: nowrap;width: 1%;'>
                <?php echo $lang['SUBJECT']; ?>
            </th>
            <th style='white-space: nowrap;width: 2%;'></th>
        </thead>
    </table>

    <div class="row">
        <!-- Subjects -->
        <div class="col-sm-6">
            <?php
            $i = 0; // unique number for each chat (mainly for ids)
            foreach (array("single", "group") as $j) {
                if ($j === "single") {
                    $result = $conn->query("SELECT subject, userID, partnerID FROM messages WHERE ((partnerID = $userID AND partner_deleted = 'FALSE') OR (userID = $userID AND user_deleted = 'FALSE')) GROUP BY subject, LEAST(userID, partnerID), GREATEST(userID, partnerID) ");
                } else {
                    $result = $conn->query("SELECT messagegroups.subject, messagegroups.id groupID from messagegroups INNER JOIN messagegroups_user ON messagegroups_user.groupID = messagegroups.id WHERE messagegroups_user.userID = $userID GROUP BY messagegroups.id");
                }
                showError($conn->error);

                while ($result && ($row = $result->fetch_assoc())) {
                    $i++;
                    if ($j === "single") {
                        $subject = $row['subject'];
                        $sender = $row['userID'];
                        $receiver = $row['partnerID'];
                    } else {
                        $subject = $row['subject'];
                        $groupID = $row['groupID'];
                        $receiver = $groupID;
                        $sender = $userID;
                    }

                    if ($userID == $receiver) {
                        $help = $receiver;
                        $receiver = $sender; //sending process must be reversed
                        $sender = $help;    // 5ac62d49ea1c4
                    }

                    if ($j === "single") {
                        // 5ac62d49ea1c4
                        $name_result = $conn->query("SELECT firstname, lastname FROM UserData WHERE id = '{$receiver}' GROUP BY id");
                        $name_row = $name_result->fetch_assoc();
                        $firstname = $name_row['firstname'];
                        $lastname = $name_row['lastname'];
                        $name = $firstname . " " . $lastname;
                    } else {
                        $name = $subject;
                    }

                    if ($j === "single") {
                        $chat_onclick = "showChat('single', $receiver, '$subject', '$name')";
                        $delete_onclick = "deleteSubject($receiver, '$subject')";
                    } else {
                        $chat_onclick = "showChat('group', $groupID, 'group subject', 'group name')";
                        $delete_onclick = "alert('not possible at the moment')"; //todo:
                    }
                    ?>
                    <div style="padding: 5px; cursor: pointer;">
                        <div class="subject<?php echo $i; ?> input-group" style="word-break: normal; word-wrap: normal; background-color: white; border: 1px solid gainsboro;">
                            <p style="padding: 10px;" onclick="<?= $chat_onclick ?>">
                                <?= $subject ?>
                            </p>
    
                            <div class="input-group-btn">
                                <div class="dropdown" id="menu<?php echo $i; ?>" style="background-color: white;">
                                    <button class="btn btn-default dropdown-toggle menuButton" type="button" data-toggle="dropdown" style="border: none;">
                                        <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                    </button>
    
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="menu">
                                    <?php if ($j === "single") : ?>
                                        <li>
                                            <a role="menuitem" href="#" onclick="<?= $delete_onclick ?>">
                                                <i class="fa fa-trash" aria-hidden="true"></i> Delete
                                            </a>
                                        </li>
                                    <?php else : ?>
                                        <li>
                                            <a role="menuitem" href="#" onclick="showGroupInformation(<?= $groupID ?>)">
                                                <i class="fa fa-users" aria-hidden="true"></i> Gruppe bearbeiten
                                            </a>
                                        </li>
                                        <li>
                                            <a role="menuitem" href="#" onclick="leaveGroup(<?= $groupID ?>)">
                                                <i class="fa fa-sign-out" aria-hidden="true"></i> Gruppe verlassen
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    </ul>
                                </div>
    
                                <span id="badge<?php echo $i ?>" class="badge pull-right" style="display: none; position: relative; left: -8px"></span>
                            </div>
                        </div>
                    </div>
    
                    <script>
                        // interval for the notification badge
                        setInterval(function () { //todo:
                            udpateBadge("#badge<?php echo $i; ?>", "#menu<?php echo $i; ?>", <?php echo $receiver; ?>, "<?php echo $subject; ?>", "<?= $j ?>")
                        }, 1000);
    
                        //on hover (for dynamic html elements) //todo:
                        $(".subject<?php echo $i; ?>").hover(function () {
                            $(this).css("background-color", "whitesmoke")
                            $(".menuButton").css("background-color", "whitesmoke")
                        }, function () {
                            $(this).css("background-color", "white")
                            $(".menuButton").css("background-color", "white")
                        });
                    </script>
    
                    <?php

                }
            }
            echo $conn->error;
            ?>
        </div>

        <!-- Messages -->
        <div class="col-sm-6">
            <!-- 5ac62d49ea1c4 -->
            <div id="user_bar" style="display: none; background-color: whitesmoke; border: 1px gainsboro solid; border-bottom: none; padding: 10px; user-select: none;"></div>

            <div class="pre-scrollable" id="messages" style="display: none; background-color: white; overflow: auto; overflow-x: hidden; border: 1px solid gainsboro; max-height: 55vh; padding-top: 5px"></div>

            <div id="chatinput" style="display: none; padding-top: 5px;">
                <form name="chatInputForm" autocomplete="off">
                    <div class="input-group">
                        <!-- TODO: set resize to none and create a js listerner to auto resize the textarea -->
                        <textarea id="message" wrap="hard" placeholder="Type a message" class="form-control" style="max-height: 11vh; resize: vertical;"></textarea>
                        <span class="input-group-btn">
                            <span id="sendButton" class="btn btn-default" type="submit" style="height: 100%;">
                                <?php echo $lang['SEND'] ?>
                            </span>
                            <label class="btn btn-default" title="Bild senden">
                                <i class="fa fa-upload" aria-hidden="true"></i>
                                <input type="file" id="messagePictureUpload" name="picture" style="display:none">
                            </label>
                        </span>
                    </div>
                </form>

                <script>
                    // send on enter
                    var shiftPressed = false;
                    $("#message").keydown(function (event) {
                        //shift + enter?
                        if (shiftPressed && event.which == 13) {
                            $("#message").append("<br>");
                        } else {
                            // update shiftPressed when not pressed shift+enter
                            shiftPressed = (event.which == 16)
                        }

                        if (event.which == 13 && !shiftPressed) {
                            event.preventDefault();

                            if ($(this).val().trim().length != 0) {
                                messageLimit++;
                                sendMessage($("#message").val());
                                $("#message").val("")
                            }
                        }
                    });

                    //submit
                    $("#chatinput").submit(function (e) {
                        //prevent enter
                        e.preventDefault()

                        // send the message
                        messageLimit++;
                        sendMessage($("#message").val());

                        // clear the field
                        $("#message").val("")

                        // prevent form redirect
                        return false;
                    })

                    //scroll
                    $("#messages").scroll(function () {
                        if ($("#messages").scrollTop() == 0) {
                            $("#messages").scrollTop(1);
                            messageLimit += 1
                            getMessages(false);
                        }

                    })

                    //removes "do you really want to leave this site message"
                    $(document).ready(function () {
                        $(":input", document.chatInputForm).bind("click", function () {
                            window.onbeforeunload = null;
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</div>
<div id="current_open_modal"></div>

<script>
    var selectedPartner = -1;
    var selectedSubject = "";
    var intervalID = -1;
    var messageLimit = 10;
    var selectedMode = ''; // single or group
    var selectedSelector = "";
    var lastGetMessagesResponse = "";


    function setCurrentModal(data, type, url) {
        $.ajax({
            url: url,
            data: data,
            type: type,
            success: function (resp) {
                $("#current_open_modal").html(resp);
            },
            error: function (resp) {
                showError(resp)
            },
            complete: function (resp) {
                onModalLoad();
                $("#current_open_modal .modal").modal('show');
            }
        });
    }

    function showUserProfile(partner) {
        setCurrentModal({
            partner: partner
        }, 'get', 'ajaxQuery/ajax_post_get_user_profile.php')
    }

    function showGroupInformation(groupID){
        setCurrentModal({group: groupID},"get","ajaxQuery/ajax_post_get_group_information.php")
    }

    function fetchUserBar(targetSelector = "#user_bar") {
        $.ajax({
            url: 'ajaxQuery/ajax_post_get_user_bar.php',
            data: {
                partner: selectedPartner,
                mode: selectedMode
            },
            type: 'GET',
            success: function (response) {
                $(targetSelector).html(response);
            },
            error: function (response) {
                $(targetSelector).html(response);
            },
        });
    }

    //Make the div visible, when someone clicks the button
    function showChat(mode, partner, subject, name) {
        //reset the limit after a new conversation will be shown
        messageLimit = 10;
        selectedPartner = partner;
        selectedSubject = subject;
        selectedSelector = "#messages";
        selectedMode = mode; // single or group

        //Show the messages immediately
        getMessages(true);
        // Clear and set the new interval for showing messages
        if (intervalID != -1) clearInterval(intervalID);
        intervalID = setInterval(function () {
            getMessages(false);
        }, 1000);

        fetchUserBar();

        // make the messages and the response field visible
        $("#user_bar").html(name);
        $("#user_bar").show();
        $("#messages").show();
        $("#chatinput").show();
    }

    function getMessages(scroll = false) {
        if (selectedPartner == -1 || subject.length == 0) {
            return;
        }
        var data;
        if(selectedMode == "group"){
            data = {
                group: selectedPartner,
                limit: messageLimit
            }
        }else{
            data = {
                partner: selectedPartner,
                subject: selectedSubject,
                limit: messageLimit,
            }
        }

        $.ajax({
            url: 'ajaxQuery/ajax_post_get_messages.php',
            data: data,
            type: 'GET',
            success: function (response) {
                if (response == lastGetMessagesResponse) return;
                $(selectedSelector).html(response);
                //Scroll down
                if (scroll) $(selectedSelector).scrollTop($(selectedSelector)[0].scrollHeight)
                lastGetMessagesResponse = response;
            },
            error: function (response) {
                $(selectedSelector).html(response);
            },
        });
    }

    function sendMessage(message) {
        if (message.length == 0 || selectedPartner == -1 || selectedSubject.length == 0) {
            return;
        }
        var data;
        if(selectedMode == "group"){
            data = {
                group: selectedPartner,
                message: message
            }
        }else{
            data = {
                partner: selectedPartner,
                subject: selectedSubject,
                message: message,
            }
        }

        $.ajax({
            url: 'ajaxQuery/ajax_post_send_message.php',
            data: data,
            type: 'GET',
            success: function (response) {
                showInfo(response, 1000);
                getMessages(true)
            },
        })
    }

    $("#messagePictureUpload").change(function (e) {
        e.stopPropagation()
        e.preventDefault()
        file = e.target.files[0]
        var data = new FormData()
        if (!file.type.match('image.*')) {
            alert("Not an image")
        } else if (file.size > 1048576) {
            alert("File too large")
        } else {
            if(selectedMode == "group"){
                data.append('group',selectedPartner);
            }else{
                if (selectedPartner == -1 || selectedSubject.length == 0) return
                data.append('partner', selectedPartner)
                data.append('subject', selectedSubject)
            }
            data.append('picture', file)
            
            const finishedFunction = function (response) {
                showInfo(response.responseText || response.statusText || response, 1000);
                getMessages(true)
            }
            $.ajax({
                url: 'ajaxQuery/ajax_post_send_message.php',
                dataType: 'json',
                data: data,
                cache: false,
                type: 'POST',
                processData: false,
                contentType: false,
                success: finishedFunction,
                error: finishedFunction
            })
        }
    })

    function deleteSubject(partner, subject) {
        $.ajax({
            url: 'ajaxQuery/ajax_post_delete_subject.php',
            data: {
                partner: partner,
                subject: subject,
            },
            type: 'GET',
            success: function (response) {
                showInfo(response);
                // reload without resending resent form
                window.location.href = window.location.pathname;
            },
        })
    }

    function leaveGroup(partner) {
        $.ajax({
            url: 'ajaxQuery/ajax_post_leave_group.php',
            data: {
                group: partner,
            },
            type: 'GET',
            success: function (response) {
                window.location.href = window.location.pathname;
            },
        })
    }

    function udpateBadge(target, menu, partner, subject, mode) {
        if (partner == -1) {
            return;
        }

        $.ajax({
            url: 'ajaxQuery/ajax_post_get_alerts.php',
            type: 'GET',
            data: {
                partner: partner,
                subject: subject,
                mode: mode
            },
            success: function (response) {

                // dont show a badge, when the chat is already opened or the response is 0
                if (response == "0" || (selectedPartner == partner && subject == selectedSubject)) {
                    $(menu).show();
                    $(target).hide();
                    udpateHeaderBadge("#globalMessagingBadge");
                } else {
                    $(target).show();
                    $(menu).hide();

                    $(target).html(response);
                }
            },
        })
    }

    function showGroupMessageInfo(messageID){
        setCurrentModal({messageID: messageID},"get","ajaxQuery/ajax_post_get_message_information.php")
    }

    function onModalLoad() {
        $(".js-example-basic-single").select2();
    }
</script>

<?php require dirname(dirname(__DIR__)) . '/footer.php'; ?>
