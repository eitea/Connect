<?php
session_start();
if (isset($_GET["partner"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);
    $userID = $_SESSION["userid"];
}
else {
    die('Invalid Request');
}
$userID = $_SESSION["userid"];
require dirname(__DIR__) . "/connection.php";
$conn->query("UPDATE socialmessages SET seen = 'TRUE' WHERE ( userID = $partner AND partner = $userID )");
$result = $conn->query("SELECT * FROM socialmessages WHERE ( userID = $userID AND partner = $partner ) OR ( userID = $partner AND partner = $userID )");
if (!$result || $result->num_rows == 0) {
    echo "no messages";
}
else {
    while ($row = $result->fetch_assoc()) {
        $message = $row["message"];
        $pull = $row["userID"] == $userID ? "pull-right":"pull-left";
        $seen = $row["seen"] == 'TRUE' ? "fa-eye":"fa-eye-slash";
        $showseen = $row["userID"] == $userID;
        $lastdate = $date ?? "";
        $date = date('Y-m-d', strtotime($row["sent"]));
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
                        <?php if($showseen){ ?>
                        <i class="fa <?php echo $seen; ?>" style="display:block;top:0px;right:-3px;position:absolute;color:#9d9d9d;"></i>
                        <?php } ?>
                        <div><?php echo $message; ?></div>
                    </div>
                </div>
            </div>
        <?php
    }
}
?>
