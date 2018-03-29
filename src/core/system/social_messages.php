<?php
require dirname(dirname(__DIR__)) . '/header.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// the currently logged in user
$currentUser = $_SESSION["userid"];
?>


<!-- TODO: Add ability to send pictures -->
<!-- TODO: Badge -->

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
        if (!isset($_POST['to_userid']) || $_POST['to_userid'] == "") {
            showError($lang['RECEIVER_NOT_SPECIFIED']);
        } else if (!isset($_POST['subject']) || $_POST['subject'] == "") {
            showError($lang['SUBJECT_NOT_SPECIFIED']);
        } else if (!isset($_POST['message']) || $_POST['message'] == "") {
            showError($lang['MESSAGE_NOT_SPECIFIED']);
        } else {
            // no errors
            $to_userid = intval($_POST['to_userid']);  //TODO: replace this with userID and clean with intval()       
            $subject = test_input($_POST['subject']);
            $message = test_input($_POST['message']);

            if($to_userid == $currentUser){
                showError($lang['INVALID_USER']);
            } else {
                // messages [userID, partnerID, subject, message, picture, sent, seen]
                $conn->query("INSERT INTO messages (userID, partnerID, subject, message, sent, seen) VALUES ($userID, $to_userid, '$subject', '$message', CURRENT_TIMESTAMP, 'FALSE')");
                showInfo($lang['MESSAGE_SENT']);
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="postLabel"><?php echo $lang['POST']; ?></h4>
                    </div>
                    <br>

                    <!-- modal body -->
                    <div class="modal-body">

                        <label for="to"> <?php echo $lang['POST_TO']; ?> </label>
                        <div class="dropdown" id="to">
                            <input type="button" id="username" class="dropdown-toggle form-control" name="to_username" data-toggle="dropdown" value='<?php echo $lang["SELECT_USER"] ?>'></button>
                            <input type="hidden" id="userid" class="dropdown-toggle form-control" name="to_userid" data-toggle="dropdown" value='<?php echo $lang["SELECT_USER"] ?>'></button>
                            
                            <ul class="dropdown-menu">                                
                                <?php
    	                            $sql = "SELECT id, concat(firstname, ' ', lastname) AS username FROM UserData where id != $currentUser GROUP BY id";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $userID = $row['id'];
                                            $username = $row['username'];
                                            ?>

                                            <li onclick='selectUser(<?php echo $userID ?>, "<?php echo $username?>")'><a href="#"><?php echo $username ?></a></li>

                                            <?php
                                        }
                                    }
                                ?>
                            </ul>

                            <script>
                                function selectUser(id, name){
                                    if(id == "" && name == "")
                                        return;

                                    var formUsernameElement = document.getElementById("username");
                                    var formIdElement = document.getElementById("userid");

                                    formUsernameElement.value = name;
                                    formIdElement.value = id;
                                }
                            </script>
                        </div>

                        <br>

                        <label for="subject"> <?php echo $lang['SUBJECT']; ?> </label>
                        <input required type="text" name="subject" class="form-control">
                        <br>

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


    <table class="table table-hover">
        <thead>
            <th style='white-space: nowrap;width: 1%;'><?php echo $lang['SUBJECT']; ?></th>
            <th style='white-space: nowrap;width: 2%;'></th>
        </thead>
    </table>

    <div class="row">
        <div class="col-xs-4">
            <?php
                //note: currentUser is defined at the beginning of the file

                //select all subjects
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


                            <!-- Subject -->
                            <style>
                                #subject {
                                    padding: 5px;
                                    border-radius: 5px;
                                }

                                #subject:hover {
                                    background-color: #F5F5F5;
                                    cursor: pointer;
                                }
                            </style>

                            <div id="subject">
                                <p style='padding: 10px' onclick="showChat<?php echo $subject; ?>()"><?php echo $subject; ?></h1>
                            </div>

                            <!-- Make the div visible, when someone clicks the button -->
                            <script>
                                function showChat<?php echo $subject; ?>() {
                                    // get the messages - function getMessages(partner, subject, target, scroll = false, limit = 50)
                                    getMessages(<?php echo $x; ?>, "<?php echo $subject; ?>", "#messages", false, 10);

                                    // make it visible
                                    var element = document.getElementById("messages");
                                    element.style.display = "block";
                                }
                            </script>


                        <?php
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


<?php require dirname(dirname(__DIR__)) . '/footer.php'; ?>
