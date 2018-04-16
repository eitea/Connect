<?php require dirname(dirname(__DIR__)) . '/header.php'; ?>

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
        <button class="btn btn-default" data-toggle="modal" data-target="#postMessages" type="button"><i class="fa fa-plus"></i></button>
    </h4>
</div>


<!-- Page body -->
<div class="page-content-fixed-50">
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
    <form method="POST" autocomplete="off">
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
                        <select required class="js-example-basic-single" name="to_userid[]" multiple="multiple">
                            <?php //5abdd31716137
                            foreach($available_users as $x){
                            echo '<option value="'.$x.'">'.$userID_toName[$x].'</option>';
                            }
                            ?>
                        </select><br>
                        <br>

                        <label for="subject"> <?php echo $lang['SUBJECT']; ?> </label>
                        <input required id="subject" type="text" maxlength="250" name="subject" class="form-control">
                        <div id="textarea_count" class="pull-right" style="padding-top: 0.5em;" ></div>
                        <script>
                            //for the character counter
                            var text_max = 250;
                            $('#textarea_count').html(text_max + ' remaining')

                            $('#subject').on("change keyup keydown paste cut", function() {
                                var text_length = $('#subject').val().length;
                                var text_remaining = text_max - text_length;

                                $('#textarea_count').html(text_remaining + ' remaining')
                            });
                        </script>
                        <br>

                        <label for="message"> <?php echo $lang['MESSAGE'] ?></label>
                        <textarea required id="post_message" name="message" class="form-control" rows="6" wrap="hard" style="resize: none"></textarea>
                    </div>

                    <!-- modal footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                        <button type="submit" class="btn btn-warning" name="sendButton"><?php echo $lang['SEND']; ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Active Conversations -->
    <table class="table table-hover">
        <thead>
            <th style='white-space: nowrap;width: 1%;'><?php echo $lang['SUBJECT']; ?></th>
            <th style='white-space: nowrap;width: 2%;'></th>
        </thead>
    </table>

    <div class="row">
        <!-- Subjects -->
        <div class="col-sm-6">
            <?php
                $result = $conn->query("SELECT subject, userID, partnerID FROM messages WHERE $userID IN (partnerID, userID) GROUP BY subject, LEAST(userID, partnerID), GREATEST(userID, partnerID) ");
                $i = 0;
                while ($result && ($row = $result->fetch_assoc())) {
                    $subject = $row['subject'];
                    $sender = $row['userID'];
                    $receiver = $row['partnerID'];
                    $i++;

                    if($userID == $receiver) {
                        $help = $receiver;
                        $receiver = $sender; //sending process must be reversed
                        $sender = $help;    // 5ac62d49ea1c4
                    }

                    // 5ac62d49ea1c4
                    $sql = "SELECT firstname, lastname FROM UserData WHERE id = '{$receiver}' GROUP BY id";
                    $name_result = $conn->query($sql);
                    $name_row = $name_result->fetch_assoc();
                    $firstname = $name_row['firstname'];
                    $lastname = $name_row['lastname'];

                    if(!empty($firstname) && !empty($lastname)) 
                        $name = $firstname . " " . $lastname;
                    elseif ((empty($firstname) && !empty($lastname)) || (empty($firstname) && !empty($lastname)))   // the user has no firstname or no lastname (admin)
                        $name = $firstname . " " . $lastname;

                ?>
                    <div style="padding: 5px;">
                        <div class="subject<?php echo $i; ?> input-group" style="word-break: normal; word-wrap: normal; background-color: white; border: 1px solid gainsboro;">
                            <p style="padding: 10px;" onclick="showChat(<?php echo $receiver; ?>, '<?php echo $subject; ?>', '<?php echo $name; ?>')">
                                <?php echo $subject; ?>
                            </p>
                            
                            <div class="input-group-btn">
                                <div class="dropdown" id="menu<?php echo $i; ?>" style="background-color: white;">
                                    <button class="btn btn-default dropdown-toggle menuButton" type="button" data-toggle="dropdown" style="border: none;">
                                        <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="menu">
                                        <li>
                                            <a role="menuitem" href="#" onclick="deleteSubject(<?php echo $receiver; ?>, '<?php echo $subject; ?>')">
                                                <i class="fa fa-trash" aria-hidden="true"></i> Delete
                                            </a>
                                        </li>

                                        <!--Placeholder: 
                                        <li role="presentation"><a role="menuitem" href="#"></a></li>
                                        -->
                                    </ul>
                                </div>

                                <span id="badge<?php echo $i ?>" class="badge pull-right" style="display: none; position: relative; left: -8px"></span>
                            </div>   
                        </div>
                    </div>
                    
                    <script>
                    // interval for the notification badge
                    setInterval(function(){udpateBadge("#badge<?php echo $i; ?>", "#menu<?php echo $i; ?>", <?php echo $receiver; ?>, "<?php echo $subject; ?>")},1000);

                    //on hover (for dynamic html elements)
                    $(".subject<?php echo $i; ?>").hover(function(){
                        $(this).css("background-color", "whitesmoke")
                        $(".menuButton").css("background-color", "whitesmoke")
                    }, function(){
                        $(this).css("background-color", "white")
                        $(".menuButton").css("background-color", "white")
                    });
                    </script>
                
                <?php
                }
                echo $conn->error;
            ?>
        </div>

        <!-- Messages -->
        <div class="col-sm-6">
            <!-- 5ac62d49ea1c4 -->
            <div id="user_bar" style="display: none; background-color: whitesmoke; border: 1px gainsboro solid; border-bottom: none; max-height: 10vh; padding: 10px;"></div>
            
            <div class="pre-scrollable" id="messages" style="display: none; background-color: white; overflow: auto; overflow-x: hidden; border: 1px solid gainsboro; max-height: 55vh; padding-top: 5px"></div>

            <div id="chatinput" style="display: none; padding-top: 5px;">
                <form name="chatInputForm" autocomplete="off">
                    <div class="input-group">
                        <textarea id="message" wrap="hard" placeholder="Type a message" class="form-control" style="height: 3.6vh; max-height: 11vh; resize: none; "></textarea>
                        <span class="input-group-btn"><button id="sendButton" class="btn btn-default" type="submit" style="height: 3.6vh"><?php echo $lang['SEND'] ?></button></span>
                    </div>
                </form>

                <script>
                    // auto resize
                    $('#message, #sendButton').on('change keyup keydown paste cut click', function (event) {
                        $("#message").height(0).height(this.scrollHeight/1.4);
                    }).find('textarea').change();
                                
                    // send on enter
                    var shiftPressed = false;
                    $("#message").keydown(function(event) {
                        //shift + enter?
                        if(shiftPressed && event.which == 13) {
                            $("#message").append("<br>");
                        } else {
                            // update shiftPressed when not pressed shift+enter
                            if(event.which == 16) shiftPressed = true; else shiftPressed = false;
                        }
                        
                        if(event.which == 13 && !shiftPressed){
                            event.preventDefault();

                            if($(this).val().trim().length != 0){
                                messageLimit++;
                                sendMessage(selectedPartner, selectedSubject, $("#message").val(), "#messages", messageLimit);
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
                        sendMessage(selectedPartner, selectedSubject, $("#message").val(), "#messages", messageLimit);

                        // clear the field
                        $("#message").val("")

                        // prevent form redirect
                        return false;
                    })

                    //scroll
                    $("#messages").scroll(function(){
                        if($("#messages").scrollTop() == 0){
                            $("#messages").scrollTop(1);
                            messageLimit += 1
                            getMessages(selectedPartner, selectedSubject, "#messages", false, messageLimit);
                        }

                    })

                    //removes "do you really want to leave this site message"
                    $(document).ready(function() {
                        $(":input", document.chatInputForm).bind("click", function() {
                            window.onbeforeunload = null;
                            console.log(window.onbeforeunload);
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</div>

<script>
var selectedPartner = -1;
var selectedSubject = "";
var intervalID = -1;
var messageLimit = 10;

//Make the div visible, when someone clicks the button
function showChat(partner, subject, name) {
    //reset the limit after a new conversation will be shown
    messageLimit = 10;

    //Show the messages immediately
    getMessages(partner, subject, "#messages", true, messageLimit);

    selectedPartner = partner;
    selectedSubject = subject;

    // Clear and set the new interval for showing messages
    if(intervalID != -1) clearInterval(intervalID);
    intervalID = setInterval(function() {
        getMessages(partner, subject, "#messages", false, messageLimit);
    }, 1000);

    // make the messages and the response field visible
    var user_bar = document.getElementById("user_bar");     //5ac62d49ea1c4
    user_bar.style.display = "block";
    user_bar.innerHTML = name;

    var messagesElement = document.getElementById("messages");
    messagesElement.style.display = "block";

    var responseElement = document.getElementById("chatinput");
    responseElement.style.display = "block";
}

function getMessages(partner, subject, target, scroll = false, limit = 50) {
    if(partner == -1 || subject.length == 0) {
        return;
    }

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

            //Scroll down
            if (scroll) $(target).scrollTop($(target)[0].scrollHeight)
        },
        error: function (response) {
            $(target).html(response);
        },
    });
}

function sendMessage(partner, subject, message, target, limit = 50) {
    if(message.length==0 || partner == -1 || subject.length == 0){
        return;
    }

    $.ajax({
        url: 'ajaxQuery/AJAX_postSendMessage.php',
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

function deleteSubject(partner, subject) {
    $.ajax({
        url: 'ajaxQuery/AJAX_postDeleteSubject.php',
        data: {
            partner: partner,
            subject: subject,
        },
        type: 'GET',
        success: function (response) {
            // reload without resending resent form
            window.location.href = window.location.pathname;
        },
    })
}

function udpateBadge(target, menu, partner, subject) {
    if(partner == -1 || subject.length == 0) {
        return;
    }

    $.ajax({
        url: 'ajaxQuery/AJAX_postGetAlerts.php',
        type: 'GET',
        data:{
            partner: partner,
            subject: subject
        },
        success: function (response) {

            // dont show a badge, when the chat is already opened or the response is 0
            if(response == "0" || selectedPartner == partner || subject == selectedSubject) {
                $(menu).show();
                $(target).hide();
            } else {
                $(target).show();
                $(menu).hide();

                $(target).html(response);
            }
        },
    })
}

</script>

<?php require dirname(dirname(__DIR__)) . '/footer.php'; ?>
