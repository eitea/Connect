<?php
session_start();
require dirname(__DIR__) . "/connection.php";

$userID = $_SESSION["userid"] ?? -1;
$limit = $_REQUEST["limit"] ?? 50;

if (isset($_GET["partner"], $_GET["subject"]) && !empty($_SESSION["userid"])) {
    $taskView = false;
    $partner = intval($_GET["partner"]);
    $subject = test_input($_GET["subject"]);

    // message has been seen
    $conn->query("UPDATE messages SET seen = 'TRUE' WHERE ( userID = $partner AND partnerID = $userID ) AND subject = '$subject'");

    //TODO: remove this the name sql queries with a UserData Join

    // get the name of the partner
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
    $sql = "SELECT * FROM (SELECT * FROM messages WHERE (( userID = $userID AND partnerID = $partner ) OR ( userID = $partner AND partnerID = $userID )) AND subject = '$subject' ORDER BY sent DESC LIMIT $limit) AS temptable ORDER BY sent ASC";
    $result = $conn->query($sql);
} elseif(isset($_GET["taskID"], $_GET["taskName"]) && !empty($_SESSION["userid"])) {
    $taskView = true;
    $taskID = intval($_GET["taskID"]);
    $taskName = test_input($_GET["taskName"]);

    // get the messages
    $result = $conn->query("SELECT * FROM (SELECT * FROM taskmessages INNER JOIN UserData ON UserData.id = taskmessages.userID WHERE ( taskID = $taskID and taskName = '$taskName' ) ORDER BY sent DESC LIMIT $limit) AS temptable ORDER BY sent ASC");
}else {
    die('Invalid Request');
}

// check the result
if (!$result || $result->num_rows == 0) {
    echo "no messages\t" . $taskID . "\t" . $taskName;
} else {
    // process the result
    while ($row = $result->fetch_assoc()) {
        $message = $row["message"];
        $firstname = $row["firstname"];
        $lastname = $row["lastname"];
        $pull = $row["userID"] == $userID ? "pull-right":"pull-left";       // left or right side?
        $color = $row["userID"] == $userID ? "#c7f4a4" : "#whitesmoke";     //dcf8c6

        // only available in normal chat
        if(!$taskView) $seen = $row["seen"] == 'TRUE' ? "fa-eye":"fa-eye-slash";

        $showseen = ($row["userID"] == $userID);
        $lastdate = $date ?? "";
        $date = date('Y-m-d', strtotime($row["sent"]));
        $messageDate = date('G:i', strtotime($row["sent"]));

        //5ac62d49ea1c4
        if(!empty($firstname) && !empty($lastname)) 
            $name = $firstname . " " . $lastname;
        elseif ((empty($firstname) && !empty($lastname)) || (empty($firstname) && !empty($lastname)))   // the user has no firstname or no lastname (admin)
            $name = $firstname . " " . $lastname;

        //partner
        if(!empty($partner_firstname) && !empty($partner_lastname)) 
            $partner_name = $partner_firstname . " " . $partner_lastname;
        elseif ((empty($partner_firstname) && !empty($partner_lastname)) || (empty($partner_firstname) && !empty($partner_lastname)))   // the user has no firstname or no lastname (admin)
            $partner_name = $partner_firstname . " " . $partner_lastname;

        if($lastdate != $date):
        ?>
        
            <div class="row">
                <div class="text-center">
                    <?php echo $date; ?>
                </div>
            </div>

        <?php endif; ?>

            <div class="row">
                <div class="col-xs-12">
                    <div class="well <?php echo $pull; ?>" style="position:relative; background-color: <?php echo $color ?>;">
                        <!-- if -->
                        <?php if($showseen): ?>
                            <!-- 5ac62d49ea1c4 -->
                            <span class="label label-default" style="display:block; top:-17px; right:0px; position:absolute; background-color: white; color: black;"><?php echo $name . " - " . $messageDate; ?></span>
                            
                            <?php if(!$taskView): ?>
                                <i class="fa <?php echo $seen; ?>" style="display:block; top:0px; right:-3px; position:absolute; color:#9d9d9d;"></i>
                            <?php endif; ?>
                        <?php elseif(!$showseen): ?>
                            <span class="label label-default" style="display:block; top:-17px; left:0px; position:absolute; background-color: white; color: black;"><?php echo $partner_name . " - " . $messageDate; ?></span>
                        <?php endif; ?>
                        <!-- endif -->

                        <div style='word-break: normal; word-wrap: normal;'>
                            <?php 
                                // handle line breaks
                                $parts = explode("\n", $message);

                                foreach  ($parts as $part){
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

function test_input($data){
    require dirname(__DIR__) . "/connection.php";
    $data = $conn->escape_string($data);
    $data = trim($data);
    return $data;
}
?>
