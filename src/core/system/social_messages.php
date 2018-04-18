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
                        <input required type="text" maxlength="40" name="subject" class="form-control">
                        <br>

                        <label for="message"> <?php echo $lang['MESSAGE'] ?></label>
                        <textarea required name="message" class="form-control" style="resize: none"></textarea>
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
        <div class="col-xs-4">
            <?php
                $result = $conn->query("SELECT subject, userID, partnerID FROM messages WHERE $userID IN (partnerID, userID) GROUP BY subject, LEAST(userID, partnerID), GREATEST(userID, partnerID) ");
                $i = 0;
                while ($result && ($row = $result->fetch_assoc())) {
                    $subject = $row['subject'];
                    $sender = $row['userID'];
                    $receiver = $row['partnerID'];
                    $i++;

                    if($userID == $receiver) $receiver = $sender; //sending process must be reversed
                    echo '<div style="padding: 5px">';
                    echo '<div class="subject'.$i.' input-group" style="background-color: white; border: 1px solid gainsboro;">';
                    echo '<p style="padding: 10px;" onclick="showChat('.$receiver.', \''.$subject.'\')">' . $subject . '</p>';
                    echo '<span class="input-group-btn"><button style="background-color: white; " class="icon'.$i.' btn" onclick="deleteSubject('.$receiver.', \''.$subject.'\')"><i class="fa fa-trash" aria-hidden="true"></i></button></span>';
                    echo '</div>';
                    echo '</div>';
                    
                    // on hover
                    echo '<script>';
                    echo '$(".subject'.$i.'").hover(function(){
                                $(this).css("background-color", "whitesmoke");
                                $(".icon'.$i.'").css("background-color", "whitesmoke");
                            }, function(){
                                $(this).css("background-color", "white");
                                $(".icon'.$i.'").css("background-color", "white");
                            });';
                    echo '</script>';
                }
                
                echo $conn->error;
            ?>
        </div>

        <!-- Messages -->
        <div class="col-xs-8">
            <div class="pre-scrollable" id="messages" style="display: none; background-color: white; overflow: auto; overflow-x: hidden; border: 1px solid gainsboro; max-height: 55vh"></div>

            <div id="chatinput" style="display: none; padding-top: 5px;">
                <form autocomplete="off">
                    <div class="input-group">
                        <textarea id="message" wrap="hard" placeholder="Type a message" class="form-control" style="height: 3.6vh; max-height: 11vh; resize: none; "></textarea>
                        <span class="input-group-btn"><button id="sendButton" class="btn btn-default" type="submit" style="height: 3.6vh"><?php echo $lang['SEND'] ?></button></span>
                    </div>
                </form>

                <script>
                    $('#message, #sendButton').on('change keyup keydown paste cut click', function (event) {
                        $("#message").height(0).height(this.scrollHeight/1.4);
                    }).find('textarea').change();
                                  
                    // send on enter
                    var shiftPressed = false;
                    $("#message").keydown(function(event) {
                        //shift + enter?
                        if(shiftPressed && event.which == 13) {
                            console.log("shit enter")
                            $("#message").append("<br>");
                        } else {
                            // update shiftPressed when not pressed shift+enter
                            if(event.which == 16) shiftPressed = true; else shiftPressed = false;
                        }
                        
                        

                        if(event.which == 13 && !shiftPressed){
                            console.log("send")
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
                        //parse the line breaks
                        $("#message").html($("#message").text().replace(/\n\r?/g, '<br>'));

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
                </script>
            </div>
        </div>
    </div>
</div>


<script>
//remove "are you sure you want to leave?"
window.onbeforeunload = null;

var selectedPartner = -1;
var selectedSubject = "";
var intervalID = -1;
var messageLimit = 10;

//Make the div visible, when someone clicks the button
function showChat(partner, subject) {
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
</script>


<?php require dirname(dirname(__DIR__)) . '/footer.php'; ?>
