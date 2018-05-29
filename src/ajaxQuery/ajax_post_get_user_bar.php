<?php
session_start();
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";


isset($_SESSION["userid"]) or die("Not logged in");
isset($_REQUEST["partner"]) or die("No Partner specified");
isset($_REQUEST["mode"]) or die("No mode specified");
$userID = $_SESSION["userid"];
$partner = intval($_REQUEST["partner"]);
$mode = test_input($_REQUEST["mode"]);

$defaultPicture = "images/defaultProfilePicture.png";

if ($mode != "group") {
    $result = $conn->query("SELECT socialprofile.status, socialprofile.picture, socialprofile.isAvailable, UserData.firstname, UserData.lastname FROM socialprofile INNER JOIN UserData ON UserData.id = socialprofile.userID WHERE UserData.id = $partner");
    ($result && $result->num_rows != 0) or die("Partner not found");
    $row = $result->fetch_assoc();
    $name = $row["firstname"] . " " . $row["lastname"];
    $profilePicture = $row['picture'] ? "data:image/jpeg;base64," . base64_encode($row['picture']) : $defaultPicture;
} else {
    $result = $conn->query("SELECT subject FROM messagegroups WHERE id = $partner");
    ($result && $result->num_rows != 0) or die("Group not found");
    $row = $result->fetch_assoc();
    $name = $row["subject"];
    $profilePicture = $defaultPicture;
}
echo $conn->error;
?>


<div class="center-block">
    <img src='<?php echo $profilePicture; ?>' style='width:30px;height:30px;' class='img-circle' alt='Profile Picture'>
    <span style="vertical-align: center; margin-left: 10px;" ><?= $name ?></span>
    <div class="input-group-btn pull-right clearfix" style="padding-right: 30px;">
        <button class="btn dropdown-toggle" type="button" data-toggle="dropdown" style="border: none;background-color: whitesmoke;">
            <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-right clearfix" role="menu" aria-labelledby="menu">
            <?php if ($mode != "group") : ?>
                <li>
                    <a role="menuitem" href="#" onclick="showUserProfile(<?= $partner ?>)">
                        <i class="fa fa-user" aria-hidden="true"></i> User Info
                    </a>
                </li>
            <?php else : ?>
                <li>
                    <a role="menuitem" href="#" onclick="showGroupInformation(<?= $partner ?>)">
                        <i class="fa fa-users" aria-hidden="true"></i> Gruppe bearbeiten
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

