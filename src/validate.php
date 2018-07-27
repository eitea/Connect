<?php

/**
 * Does nothing when user has permission. When the user doesn't have permission, 
 * a message and the footer are displayed and the script execution stops
 * 
 * @see has_permission, example: dsgvo_training.php
 */
function require_permission($type, $group_name, $permission_name, $userID = false)
{
  if (!has_permission($type, $group_name, $permission_name, $userID)) {
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    $type = strtolower($type);
    showError("You don't have permission to ${type} $group_name $permission_name (<a href='#' onclick='window.history.back()'>go back</a> or <a href='../user/logout'>logout</a>)");
    include 'footer.php';
    die();
  }
}

/**
 * Test if user has a specific permission.
 * 
 * @param string $type 'READ' or 'WRITE'
 * @param string|false $permission_name Permission name. If false, has_permission returns true 
 *                     when a user has ANY permission in that group
 * @param int|false $userID User ID; uses $_SESSION if false
 * 
 * @see example: dsgvo_training.php
 */
function has_permission($type, $group_name, $permission_name = false, $userID = false) : bool
{
  if (!$userID) $userID = $_SESSION['userid'];
  if ($userID == 1) return true; // admin
  $group_only = $permission_name === false;
  global $conn;
  static $cache = [];
  if(!$group_only && isset($cache[$group_name][$permission_name][$userID][$type])){
    return $cache[$group_name][$permission_name][$userID][$type];
  }
  if (!isset($conn)) {
    require 'connection.php';
  }
  if($group_only){
    $additional = $type == "READ"?" AND (rel.type = 'READ' OR rel.type = 'WRITE')":" AND (rel.type = 'WRITE')";
    $result = $conn->query("SELECT rel.type FROM access_permission_groups groups 
                            INNER JOIN access_permissions perm ON perm.groupID = groups.id 
                            INNER JOIN relationship_access_permissions rel ON rel.permissionID = perm.id
                            WHERE groups.name = '$group_name'
                            AND rel.userID = $userID $additional GROUP BY rel.type");
  }else{
    $result = $conn->query("SELECT rel.type FROM access_permission_groups groups 
                            INNER JOIN access_permissions perm ON perm.groupID = groups.id 
                            INNER JOIN relationship_access_permissions rel ON rel.permissionID = perm.id
                            WHERE groups.name = '$group_name'
                            AND perm.name = '$permission_name'
                            AND rel.userID = $userID");
  }
  echo $conn->error;
  $has_permission = false;
  if ($result && $row = $result->fetch_assoc()) {
    $has_permission = $row["type"] == $type || $row["type"] == 'WRITE'; // if user has WRITE, they can read too
  }
  if(!$group_only){
    $cache[$group_name][$permission_name][$userID][$type] = $has_permission;
  }
  return $has_permission;
}


function enableToCore($userID){
  global $conn;
  if(!$conn) require 'connection.php'; //5ab9e57714ff6
  $sql = "SELECT isCoreAdmin FROM roles WHERE userID = $userID AND isCoreAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToTime($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT isTimeAdmin FROM roles WHERE userID = $userID AND isTimeAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToProject($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT isProjectAdmin FROM roles WHERE userID = $userID AND isProjectAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToStamps($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT canStamp FROM roles WHERE userID = $userID AND canStamp = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToBookings($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT * FROM roles WHERE userID = $userID AND canBook = 'TRUE' AND canStamp = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToTemplate($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT * FROM roles WHERE userID = $userID AND (isCoreAdmin = 'TRUE' OR canEditTemplates = 'TRUE')";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToReport($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT isReportAdmin FROM roles WHERE userID = $userID AND isReportAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToERP($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT isERPAdmin FROM roles WHERE userID = $userID AND isERPAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToFinance($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT isFinanceAdmin FROM roles WHERE userID = $userID AND isFinanceAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

// function enableToDSGVO($userID){ // replaced by permissions
//   global $conn;
//   if(!$conn) require 'connection.php';
//   $sql = "SELECT isFinanceAdmin FROM roles WHERE userID = $userID AND isDSGVOAdmin = 'TRUE'";
//   $result = $conn->query($sql);
//   if($userID != 1 && (!$result || $result->num_rows <= 0)){
//     echo 'Access denied. <a href="../user/logout"> logout</a>';
//     include 'footer.php';
//     die();
//   }
// }

function enableToClients($userID){
    global $conn;
    if(!$conn) require 'connection.php';
    $result = $conn->query("SELECT userID FROM roles WHERE userID = $userID AND (isERPAdmin = 'TRUE' OR isCoreAdmin = 'TRUE' OR canUseClients = 'TRUE'
    OR canEditClients = 'TRUE' OR canUseSuppliers = 'TRUE' OR canEditSuppliers = 'TRUE')");
    if($userID != 1 && (!$result || $result->num_rows <= 0)){
        echo 'Access denied. <a href="../user/logout"> logout</a>';
        include 'footer.php';
        die();
    }
}

function denyToContainer(){
  if(getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])){
    echo 'Docker access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToSocialMedia($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT * FROM roles WHERE userID = $userID AND canUseSocialMedia = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function isDynamicProjectAdmin($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  $sql = "SELECT * FROM roles WHERE userID = $userID AND isDynamicProjectsAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo ('Access denied.');
    include 'footer.php';
    die('<a href="../user/logout"> log out</a>');
  }
}

function enableToDynamicProjects($userID){
  global $conn;
  if(!$conn) require 'connection.php';
  // test whether user has active dynamic projects
  $result = $conn->query("SELECT dynamicprojectsemployees.*, dynamicprojectsoptionalemployees.*, dynamicprojects.*
  FROM dynamicprojects
  LEFT JOIN dynamicprojectsoptionalemployees ON dynamicprojectsoptionalemployees.projectid = dynamicprojects.projectid
  LEFT JOIN dynamicprojectsemployees on dynamicprojectsemployees.projectid = dynamicprojects.projectid
  WHERE dynamicprojectsoptionalemployees.userid = $userID
  OR dynamicprojectsemployees.userid = $userID
  OR dynamicprojects.projectowner = $userID");
  if($result && $result->num_rows == 0){
    echo "You are not part of any dynamic projects <a href='../user/logout'> logout</a>";
    include 'footer.php';
    die();
  }
}

function enableToWorkflow($userID){
    global $conn;
    if(!$conn) require 'connection.php';
    $result = $conn->query("SELECT userID FROM roles WHERE userID = $userID AND (canUseWorkflow = 'TRUE' OR isProjectAdmin = 'TRUE')");
    if($userID != 1 && (!$result || $result->num_rows <= 0)){
        echo 'Access denied. <a href="../user/logout"> logout</a>';
        include 'footer.php';
        die();
    }
}
?>
