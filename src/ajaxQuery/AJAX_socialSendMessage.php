<?php
session_start();
if (isset($_GET["partner"], $_GET["message"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);
    $userID = $_SESSION["userid"];
    $message = test_input($_GET["message"]);
    require dirname(__DIR__) . "/connection.php";
    $conn->query("INSERT INTO socialmessages (userID, partner, message) VALUES ($userID, $partner, '$message')");
    echo $conn->error;
} elseif(isset($_GET["group"], $_GET["message"]) && !empty($_SESSION["userid"])){
    $group = intval($_GET["group"]);
    $userID = $_SESSION["userid"];
    $message = test_input($_GET["message"]);
    require dirname(__DIR__) . "/connection.php";
    $conn->query("INSERT INTO socialgroupmessages (userID, groupID, message, seen) VALUES ($userID, $group, '$message', '$userID')");
    echo $conn->error;
}else if (isset($_REQUEST["partner"],$_FILES["picture"]) && !empty($_SESSION["userid"])){
    require __DIR__ . "/../utilities.php";
    $partner = intval($_REQUEST["partner"]);
    $userID = $_SESSION["userid"];
    $picture = uploadFile("picture", 1, 1);
    require dirname(__DIR__) . "/connection.php";
    $stmt = $conn->prepare("INSERT INTO socialmessages (userID, partner, picture) VALUES ($userID, $partner, ?)");
    echo $conn->error;
    $null = NULL;
    $stmt->bind_param("b", $null);
    $stmt->send_long_data(0, $picture);
    $stmt->execute();
    echo $stmt->error;
}else if (isset($_REQUEST["group"],$_FILES["picture"]) && !empty($_SESSION["userid"])){
    require __DIR__ . "/../utilities.php";
    $group = intval($_REQUEST["group"]);
    $userID = $_SESSION["userid"];
    $picture = uploadFile("picture", 1, 1);
    require dirname(__DIR__) . "/connection.php";
    $stmt = $conn->prepare("INSERT INTO socialgroupmessages (userID, groupID, picture, seen) VALUES ($userID, $group, ?, '$userID')");
    echo $conn->error;
    $null = NULL;
    $stmt->bind_param("b", $null);
    $stmt->send_long_data(0, $picture);
    $stmt->execute();
    echo $stmt->error;
} else {
    die('Invalid Request');
}
// if (isset($_FILES['profilePictureUpload']) && !empty($_FILES['profilePictureUpload']['name'])) {
//     require __DIR__ . "/utilities.php";
//     $pp = uploadFile("profilePictureUpload", 1, 1);
//     if (!is_array($pp)) {
//         $stmt = $conn->prepare("UPDATE socialprofile SET picture = ? WHERE userID = $userID");
//         echo $conn->error;
//         $null = NULL;
//         $stmt->bind_param("b", $null);
//         $stmt->send_long_data(0, $pp);
//         $stmt->execute();
//         if ($stmt->errno) {
//             displayError($stmt->error);
//         }
//         else {
//             displaySuccess($lang['SOCIAL_SUCCESS_IMAGE_UPLOAD']);
//         }
//         $stmt->close();
//     }
//     else {
//         displayError(print_r($filename));
// }

function test_input($data)
{
    require dirname(__DIR__) . "/connection.php";
    $data = $conn->escape_string($data);
    $data = trim($data);
    return $data;
}