<?php
session_start();
require dirname(__DIR__) . "/connection.php";


isset($_SESSION["userid"]) or die("Not logged in");
isset($_REQUEST["partner"]) or die("No Partner specified");
$userID = $_SESSION["userid"];
$partner = intval($_REQUEST["partner"]);

$result = $conn->query("SELECT socialprofile.status, socialprofile.picture, socialprofile.isAvailable, UserData.firstname, UserData.lastname FROM socialprofile INNER JOIN UserData ON UserData.id = socialprofile.userID WHERE UserData.id = $partner");
echo $conn->error;
($result && $result->num_rows != 0) or die("Partner not found");
$row = $result->fetch_assoc();
$name = $row["firstname"] . " " . $row["lastname"];
$defaultPicture = "images/defaultProfilePicture.png";
$profilePicture = $row['picture'] ? "data:image/jpeg;base64," . base64_encode($row['picture']) : $defaultPicture;
?>


<div class="center-block">
    <img src='<?php echo $profilePicture; ?>' style='width:30px;height:30px;' class='img-circle' alt='Profile Picture'>
    <span style="vertical-align: center; margin-left: 10px;" ><?= $name ?></span>
    <div class="input-group-btn pull-right clearfix" style="padding-right: 30px;">
        <button class="btn dropdown-toggle" type="button" data-toggle="dropdown" style="border: none;background-color: whitesmoke;">
            <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-right clearfix" role="menu" aria-labelledby="menu">
            <li>
                <a role="menuitem" href="#" onclick="showUserProfile(<?= $partner ?>)">
                    <i class="fa fa-user" aria-hidden="true"></i> User Info
                </a>
            </li>
        </ul>
    </div>
</div>

