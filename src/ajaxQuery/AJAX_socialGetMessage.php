<?php
session_start();
require dirname(__DIR__) . "/connection.php";
$userID = $_SESSION["userid"] ?? -1;
if (isset($_GET["partner"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);
    $conn->query("UPDATE socialmessages SET seen = 'TRUE' WHERE ( userID = $partner AND partner = $userID )");
    $result = $conn->query("SELECT * FROM socialmessages WHERE ( userID = $userID AND partner = $partner ) OR ( userID = $partner AND partner = $userID )");
    $groupView = false;
} elseif (isset($_GET["group"]) && !empty($_SESSION["userid"])){
    $groupView = true;
    $group = intval($_GET["group"]);
    $conn->query("UPDATE socialgroupmessages SET seen = CONCAT(seen, ',$userID') WHERE NOT ( seen LIKE '%,$userID,%' OR seen LIKE '$userID,%' OR seen LIKE '%,$userID' OR seen = '$userID' )");
    $result = $conn->query("SELECT * FROM socialgroupmessages INNER JOIN userdata ON userdata.id = socialgroupmessages.userID WHERE ( groupID = $group )");
} else {
    die('Invalid Request');
}


if (!$result || $result->num_rows == 0) {
    echo "no messages";
}
else {
    while ($row = $result->fetch_assoc()) {
        $message = $row["message"];
        $pull = $row["userID"] == $userID ? "pull-right":"pull-left";
        $seen = $row["seen"] == 'TRUE' ? "fa-eye":"fa-eye-slash";
        $showseen = ($row["userID"] == $userID);
        $lastdate = $date ?? "";
        $date = date('Y-m-d', strtotime($row["sent"]));
        if(isset($row["firstname"],$row["lastname"]))
            $name =  $name = "${row['firstname']} ${row['lastname']}";
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
