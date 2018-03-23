<?php
/*
isCoreAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',      |
isTimeAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',      | - Define accessable Pages
isProjectAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',   |

canStamp ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',          | - Define Menu Items.
canBook ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',          |
*/

function enableToCore($userID){
  global $conn;
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
  $sql = "SELECT isFinanceAdmin FROM roles WHERE userID = $userID AND isFinanceAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToDSGVO($userID){
  global $conn;
  $sql = "SELECT isFinanceAdmin FROM roles WHERE userID = $userID AND isDSGVOAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    echo 'Access denied. <a href="../user/logout"> logout</a>';
    include 'footer.php';
    die();
  }
}

function enableToClients($userID){
    global $conn;
    $result = $conn->query("SELECT userID FROM roles WHERE userID = $userID AND (isERPAdmin = 'TRUE' OR isCoreAdmin = 'TRUE' OR canUseClients = 'TRUE' OR canEditClients = 'TRUE')");
    if($userID != 1 && (!$result || $result->num_rows <= 0)){
        echo 'Access denied. <a href="../user/logout"> logout</a>';
        include 'footer.php';
        die();
    }
}

function enableToSuppliers($userID){
    global $conn;
    $result = $conn->query("SELECT userID FROM roles WHERE userID = $userID AND (isERPAdmin = 'TRUE' OR isCoreAdmin = 'TRUE' OR canUseSuppliers = 'TRUE' OR canEditSuppliers = 'TRUE')");
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
  enableToBookings($userID);
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
?>
