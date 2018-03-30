<?php
session_start();
require dirname(__DIR__) . "/connection.php";

$userID = $_SESSION["userid"] ?? -1;
$limit = $_REQUEST["limit"] ?? 50;

if (isset($_GET["partner"], $_GET["subject"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);
    $subject = test_input($_GET["subject"]);

    // message has been seen
    $conn->query("UPDATE messages SET seen = 'TRUE' WHERE ( userID = $partner AND partnerID = $userID )");

    // get the name of the partner
    $result = $conn->query("SELECT firstname, lastname FROM UserData WHERE id = '{$partner}' GROUP BY id");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $firstname = $row['firstname'];
            $lastname = $row['lastname'];
        }
    }

    // get the messages
    $sql = "SELECT * FROM (SELECT * FROM messages WHERE (( userID = $userID AND partnerID = $partner ) OR ( userID = $partner AND partnerID = $userID )) AND subject = '$subject' ORDER BY sent DESC LIMIT $limit) AS temptable ORDER BY sent ASC";
    $result = $conn->query($sql);
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
        $pull = $row["userID"] == $userID ? "pull-right":"pull-left";       // left or right side?
        $seen = $row["seen"] == 'TRUE' ? "fa-eye":"fa-eye-slash";
        $showseen = ($row["userID"] == $userID);
        $lastdate = $date ?? "";
        $date = date('Y-m-d', strtotime($row["sent"]));


        if(!empty($firstname) && !empty($lastname)) $name = $firstname . " " . $lastname;

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
                    <div class="well <?php echo $pull; ?>" style="position:relative">
                        <!-- if -->
                        <?php if($showseen): ?>
                            <i class="fa <?php echo $seen; ?>" style="display:block; top:0px; right:-3px; position:absolute; color:#9d9d9d;"></i>
                        <?php elseif(!$showseen): ?>
                            <span class="label label-default" style="display:block; top:-17px; left:0px; position:absolute;"><?php echo $name; ?></span>
                        <?php endif; ?>
                        <!-- endif -->

                        <div><?php echo $message; ?></div>
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
