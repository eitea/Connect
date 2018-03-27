<?php
session_start();
require dirname(__DIR__) . "/connection.php";

$userID = $_SESSION["userid"] ?? -1;
$limit = $_REQUEST["limit"] ?? 50;

if (isset($_GET["partner"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);

    // message has been seen
    $conn->query("UPDATE messages SET seen = 'TRUE' WHERE ( userID = $partner AND partner = $userID )");

    // get the messages
    $result = $conn->query("SELECT * FROM (SELECT * FROM messages WHERE ( userID = $userID AND partnerID = $partner ) OR ( userID = $partner AND partnerID = $userID ) ORDER BY sent DESC LIMIT $limit) AS temptable ORDER BY sent ASC");

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

        
        if(isset($row["firstname"], $row["lastname"]))
            $name = "${row['firstname']} ${row['lastname']}";

        if($lastdate != $date){
            ?>
            <div class="row">
                <div class="text-center">
                    <?php echo $date; ?>
                </div>
            </div>

            <?php
        }
        ?>
            <div class="row">
                <div class="col-xs-12">
                    <div class="well <?php echo $pull; ?>" style="position:relative">
                        <?php if($showseen && !$groupView){ ?>
                        <i class="fa <?php echo $seen; ?>" style="display:block;top:0px;right:-3px;position:absolute;color:#9d9d9d;"></i>
                        <?php }elseif($groupView && !$showseen){ ?>
                            <span class="label label-default" style="display:block;top:-17px;left:0px;position:absolute;"><?php echo $name; ?></span>
                        <?php }?>
                        <div><?php echo $message; ?></div>
                    </div>
                </div>
            </div>
        <?php
    }
}
?>
