<?php
session_start();
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";

$userID = $_SESSION["userid"] ?? -1;
$limit = $_REQUEST["limit"] ?? 50;
$limit = intval($limit);


$defaultPicture = "images/defaultProfilePicture.png";

$result = $conn->query("SELECT socialprofile.picture, userID FROM socialprofile WHERE picture IS NOT NULL");

$profilePictures = array();
while ($result && $row = $result->fetch_assoc()) {
    $profilePictures[$row["userID"]] = $row["picture"];
}

if (isset($_GET["partner"], $_GET["subject"]) && !empty($_SESSION["userid"])) {
    $taskView = false;
    $partner = intval($_GET["partner"]);
    $subject = test_input($_GET["subject"]);

    // message has been seen
    $conn->query("UPDATE messages SET seen = 'TRUE' WHERE ( userID = $partner AND partnerID = $userID ) AND subject = '$subject'");

    // its needed to select the usernames for - get the name of the partner
    $sql = "SELECT firstname, lastname FROM UserData WHERE id = '{$partner}' GROUP BY id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $partner_firstname = $row['firstname'];
    $partner_lastname = $row['lastname'];

    // get the name of the current logged in user
    $sql = "SELECT firstname, lastname FROM UserData WHERE id = '{$userID}' GROUP BY id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $firstname = $row['firstname'];
    $lastname = $row['lastname'];

    // get the messages
    $sql = "SELECT * FROM (
                SELECT * FROM messages 
                WHERE 
                (
                    ( 
                        userID = $userID 
                        AND partnerID = $partner 
                        AND user_deleted = 'FALSE' 
                    ) 
                    OR 
                    ( 
                        userID = $partner AND partnerID = $userID AND partner_deleted = 'FALSE' 
                    )
                ) 
                AND subject = '$subject' ORDER BY sent DESC LIMIT $limit
            ) AS temptable ORDER BY sent ASC";
    $result = $conn->query($sql);
    echo $conn->error;
} elseif (isset($_GET["taskID"]) && !empty($_SESSION["userid"])) {
    $taskView = true;
    $taskID = intval($_GET["taskID"]);

    if (isset($_GET["taskName"])) {
        $taskName = test_input($_GET["taskName"]);
        $result = $conn->query("SELECT * FROM (SELECT * FROM taskmessages INNER JOIN UserData ON UserData.id = taskmessages.userID WHERE ( taskID = $taskID and taskName = '$taskName' ) ORDER BY sent DESC LIMIT $limit) AS temptable ORDER BY sent ASC");
    } else {
        $result = $conn->query("SELECT * FROM (SELECT * FROM taskmessages INNER JOIN UserData ON UserData.id = taskmessages.userID WHERE ( taskID = $taskID) ORDER BY sent DESC LIMIT $limit) AS temptable ORDER BY sent ASC");
    }
} elseif (isset($_REQUEST["group"])) {
    $taskView = true;
    $groupID = intval($_REQUEST["group"]);
    $result = $conn->query("SELECT * FROM (SELECT message, picture, sent, sender AS userID, firstname, lastname FROM groupmessages INNER JOIN UserData ON UserData.id = groupmessages.sender WHERE groupID = $groupID ORDER BY groupmessages.sent DESC LIMIT $limit) as temp order by sent asc");
    $conn->query("INSERT INTO groupmessages_user (userID, messageID, seen) SELECT $userID, id, 'TRUE' FROM groupmessages WHERE groupID = $groupID ON DUPLICATE KEY UPDATE seen = 'TRUE'");
} else {
    die('Invalid Request');
}

// check the result
if (!$result || $result->num_rows == 0) {
    echo "no messages";
} else {
    // process the result
    while ($row = $result->fetch_assoc()) {
        $message = $row["message"];
        $picture = $row["picture"];
        $profilePicture = $defaultPicture;
        if (isset($profilePictures[$row["userID"]])) {
            $profilePicture = "data:image/jpeg;base64," . base64_encode($profilePictures[$row["userID"]]);
        }

        if ($taskView) { // firstname and lastname not available in normal query
            $firstname = $row["firstname"];
            $lastname = $row["lastname"];
        }

        $pull = $row["userID"] == $userID ? "pull-right" : "pull-left";       // left or right side?
        $color = $row["userID"] == $userID ? "#c7f4a4" : "#whitesmoke";     //dcf8c6

        // seen is only available in normal sql query
        if (!$taskView) $seen = $row["seen"] == 'TRUE' ? "fa-eye" : "fa-eye-slash";

        $showseen = ($row["userID"] == $userID);
        $alignment = ($row["userID"] != $userID) ? 'left: 0px;' : 'right: 0;';   // alignment of the username+date: partner(s) = left, current user = right

        $lastdate = $date ?? "";
        $date = date('Y-m-d', strtotime($row["sent"]));
        $messageDate = date('G:i', strtotime($row["sent"]));

        //5ac62d49ea1c4
        if (!empty($firstname) || !empty($lastname))
            $name = $firstname . " " . $lastname;
        
        //partner only available when not using taskView, bc in taskView its handled with a join
        if ((!empty($partner_firstname) || !empty($partner_lastname)) && !$taskView)
            $partner_name = $partner_firstname . " " . $partner_lastname;
        

        //TODO: decrypt message

        // Testing: 
        // RX1gCMdnGcM5+2CMSEnoRx4XzxwqidP7/rwO3XwcsGQ=
        //showError("Private key: " . $privateKey);

        //  jgoLsRNAdiUC3oReQUnZBnxLm7Z26h99Q7GC
        //showError("Encrypted string: " . simple_encryption("Testmessage", $privateKey));

        // Decrypt: 
        //showError("Decrypted string: " . simple_decryption("jgoLsRNAdiUC3oReQUnZBnxLm7Z26h99Q7GC", $privateKey));


        if ($lastdate != $date) :
        ?>
        
            <div class="row">
                <div class="text-center">
                    <?php echo $date; ?>
                </div>
            </div>

        <?php endif; ?>

            <div class="row">
                <div class="col-xs-12" <?php if ($taskView && ($row["userID"] != $userID)) : echo 'style="padding-top:10px"';
                                        endif; ?>>
                    <div class="well <?php echo $pull; ?>" style="position:relative; background-color: <?php echo $color ?>;">
                        <?php if ($taskView) : ?>
                            <span class="label label-default" style="display:block; <?= ($row["userID"] != $userID) ? "top:-35px;" : "top:-17px;" ?> <?php echo $alignment ?> position:absolute; background-color: white; color: black;">
                                 <?php if ($row["userID"] != $userID) : ?><img src='<?php echo $profilePicture; ?>' style='width:25px;height:25px;margin-right:5px;' class='img-circle' alt='Profile Picture'><?php endif; ?>
                                 <?php echo $name . " - " . $messageDate; ?>
                            </span>
                        <?php else : ?>
                            <?php if ($showseen) : ?>
                                <span class="label label-default" style="display:block; top:-17px; right:0px; position:absolute; background-color: white; color: black;"><?php echo $name . " - " . $messageDate; ?></span>
                                <i class="fa <?php echo $seen; ?>" style="display:block; top:0px; right:-3px; position:absolute; color:#9d9d9d;"></i>
                            <?php else : ?>
                                <span class="label label-default" style="display:block; top:-17px; left:0px; position:absolute; background-color: white; color: black;"><?php echo $partner_name . " - " . $messageDate; ?></span>
                            <?php endif; ?>
                        <?php endif ?>

                        <div style='word-break: normal; word-wrap: normal;'>
                            <?php 
                            if ($picture) {
                                echo "<img src='data:image/jpeg;base64," . base64_encode($row["picture"]) . "' alt='Picture type not supported' style='max-width:100%;' >";
                            }
                                // handle line breaks
                            $parts = explode("\n", $message);

                            foreach ($parts as $part) {
                                echo $part . "<br>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php

    }
}

?>
