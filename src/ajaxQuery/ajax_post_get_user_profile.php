<?php
session_start();

require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";

isset($_SESSION["userid"]) or die("Not logged in");
isset($_REQUEST["partner"]) or die("No Partner specified");
$userID = $_SESSION["userid"];
$partner = intval($_REQUEST["partner"]);
$result = $conn->query("SELECT socialprofile.status, socialprofile.picture, socialprofile.isAvailable, UserData.firstname, UserData.lastname FROM socialprofile INNER JOIN UserData ON UserData.id = socialprofile.userID WHERE UserData.id = $partner");
if (!$result || !($row = $result->fetch_assoc())) {
    die("User not found");
}
$name = $row["firstname"] . " " . $row["lastname"];
$defaultPicture = "images/defaultProfilePicture.png";
$profilePicture = $row['picture'] ? "data:image/jpeg;base64," . base64_encode($row['picture']) : $defaultPicture;
$status = $row['status'];
$today = substr(getCurrentTimestamp(), 0, 10);
$checkedIn = $conn->query("SELECT * FROM $logTable WHERE userID = $partner AND time LIKE '$today %' AND timeEnd = '0000-00-00 00:00:00'")->num_rows > 0;
$isAvailable = $row["isAvailable"] == "TRUE";
$available = "";
switch (true) {
    case $isAvailable && $checkedIn:
        $available = "success";
        break;
    case $isAvailable && !$checkedIn:
        $available = "warning";
        break;
    case !$isAvailable:
        $available = "danger";
}
?>
<form method="POST">
    <div class="modal fade">
        <div class="modal-dialog modal-content modal-md">
        <div class="modal-header"><?= $name ?></div>
        <div class="modal-body">
            
            <img src='<?php echo $profilePicture; ?>' style='width:50%;height:50%;' class='img-circle center-block' alt='Profile Picture'>
            <br />
            <h4 class="center-block text-center text-<?=$available?> bg-<?=$available?>"><?= $name ?></h4>
            <span class="" >Status: <?= $status ?></span>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
        </div>
        </div>
    </div>
</form>