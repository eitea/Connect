<?php
  session_start();
  if(empty($_SESSION['userid'])){
    die('Please <a href="../login/auth">login</a> first.');
  }

  $userID = $_SESSION['userid'];
  $timeToUTC = $_SESSION['timeToUTC'];
  $setActiveLink = 'class="active-link"';

  require __DIR__."/connection.php";
  require __DIR__."/utilities.php";
  require __DIR__."/validate.php";
  require __DIR__."/language.php";

  if($this_page != "editCustomer_detail.php"){
    unset($_SESSION['unlock']);
  }

  $sql = "SELECT * FROM roles WHERE userID = $userID";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    $row = $result->fetch_assoc();
    $isCoreAdmin = $row['isCoreAdmin'];
    $isTimeAdmin = $row['isTimeAdmin'];
    $isProjectAdmin = $row['isProjectAdmin'];
    $isReportAdmin = $row['isReportAdmin'];
    $isERPAdmin = $row['isERPAdmin'];
    $isFinanceAdmin = $row['isFinanceAdmin'];
    $isDSGVOAdmin = $row['isDSGVOAdmin'];
    $canBook = $row['canBook'];
    $canStamp = $row['canStamp'];
    $canEditTemplates = $row['canEditTemplates'];
    $canUseSocialMedia = $row['canUseSocialMedia'];
  } else {
    $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = $isReportAdmin = $isERPAdmin = $isFinanceAdmin = $isDSGVOAdmin = FALSE;
    $canBook = $canStamp = $canEditTemplates = $canUseSocialMedia = FALSE;
  }

  if($userID == 1){ //superuser
    $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = $isReportAdmin = $isERPAdmin = $isFinanceAdmin = $isDSGVOAdmin = 'TRUE';
    $canStamp = $canBook = $canUseSocialMedia = 'TRUE';
  }

  $result = $conn->query("SELECT psw, lastPswChange, keyCode FROM UserData WHERE id = $userID");
  $row = $result->fetch_assoc();
  $lastPswChange = $row['lastPswChange'];
  $userPasswordHash = $row['psw'];
  $userKeyCode = $row['keyCode'];

  $result = $conn->query("SELECT masterPassword, enableReadyCheck, checkSum FROM configurationData");
  $row = $result->fetch_assoc();
  $showReadyPlan = $row['enableReadyCheck'];
  $masterPasswordHash = $row['masterPassword'];
  $masterPass_checkSum = $row['checkSum']; //ABCabc123!

  $result = $conn->query("SELECT enableSocialMedia FROM modules");
  if($result && ($row = $result->fetch_assoc())){
    $enableSocialMedia = $row['enableSocialMedia'];
  } else {
    $enableSocialMedia = 'FALSE';
  }
  if($enableSocialMedia == 'TRUE'){
    $private = $conn->query("SELECT * FROM socialmessages WHERE seen = 'FALSE' AND partner = $userID ")->num_rows;
    $group = $conn->query("SELECT * FROM socialgroupmessages INNER JOIN socialgroups ON socialgroups.groupID = socialgroupmessages.groupID WHERE socialgroups.userID = '$userID' AND NOT ( seen LIKE '%,$userID,%' OR seen LIKE '$userID,%' OR seen LIKE '%,$userID' OR seen = '$userID')")->num_rows;
    $numberOfSocialAlerts = $private+$group;
  }
  if($isTimeAdmin){
    $numberOfAlerts = 0;
    //requests
    $result = $conn->query("SELECT id FROM $userRequests WHERE status = '0'");
    if($result && $result->num_rows > 0){ $numberOfAlerts += $result->num_rows; }
    //forgotten checkouts
    $result = $conn->query("SELECT indexIM FROM $logTable WHERE (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60) > 22 OR TIMESTAMPDIFF(MINUTE, time, timeEnd) < 0");
    if($result && $result->num_rows > 0){ $numberOfAlerts += $result->num_rows; }
    //gemini date in logs
    $result = $conn->query("SELECT * FROM $logTable l1, $userTable WHERE l1.userID = $userTable.id AND EXISTS(SELECT * FROM $logTable l2 WHERE DATE(DATE_ADD(l1.time, INTERVAL timeToUTC  hour)) = DATE(DATE_ADD(l2.time, INTERVAL timeToUTC  hour)) AND l1.userID = l2.userID AND l1.indexIM != l2.indexIM) ORDER BY l1.time DESC");
    if($result && $result->num_rows > 0){ $numberOfAlerts += $result->num_rows; }
    //lunchbreaks
    $result = $conn->query("SELECT l1.*, pauseAfterHours, hoursOfRest FROM logs l1
    INNER JOIN UserData ON l1.userID = UserData.id INNER JOIN intervalData ON UserData.id = intervalData.userID
    WHERE (status = '0' OR status ='5') AND endDate IS NULL AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(MINUTE, time, timeEnd) > (pauseAfterHours * 60) 
    AND hoursOfRest * 60 > (SELECT IFNULL(SUM(TIMESTAMPDIFF(MINUTE, start, end)),0) as breakCredit FROM projectBookingData WHERE bookingType = 'break' AND timestampID = l1.indexIM)");
    if($result && $result->num_rows > 0){ $numberOfAlerts += $result->num_rows; }
  }
  $result = $conn->query("SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID OR $userID = 1");
  $available_companies = array('-1'); //care
  while($result && ($row = $result->fetch_assoc())){
    $available_companies[] = $row['companyID'];
  }
  $result = $conn->query("SELECT DISTINCT userID FROM $companyToUserRelationshipTable WHERE companyID IN(".implode(', ', $available_companies).") OR $userID = 1");
  $available_users = array('-1');
  while($result && ($row = $result->fetch_assoc())){
    $available_users[] = $row['userID'];
  }
  $validation_output = $error_output = '';

  if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_SESSION['posttimer']) && (time() - $_SESSION['posttimer']) < 2){
        $_POST = array();
    }
    $_SESSION['posttimer'] = time();
    if(!empty($_POST['passwordCurrent']) && !empty($_POST['password']) && !empty($_POST['passwordConfirm']) && crypt($_POST['passwordCurrent'], $userPasswordHash) == $userPasswordHash){
      $password = $_POST['password'];
      $passwordConfirm = $_POST['passwordConfirm'];
      $newMasterPass = '';
      if($userKeyCode){
        $newMasterPass = simple_decryption($userKeyCode, $_POST['passwordCurrent']);
        $newMasterPass = simple_encryption($newMasterPass, $password);
      } elseif($masterPasswordHash && !empty($_POST['passwordMaster'])){
        $newMasterPass = simple_encryption($_POST['passwordMaster'], $password);
      }
      $output = '';
      if($newMasterPass && (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[~\!@#\$%&\*_\-\+\.\?]/', $password))){
        $validation_output  = '<div class="alert alert-danger fade in"><a href="" class="close" data-dismiss="alert">&times;</a>Passwörter mit Masterpasswortverschlüsselung müssen mindestens einen Großbuchstaben, eine Zahl und ein Sonderzeichen enthalten.</div>';
      } elseif(strcmp($password, $passwordConfirm) == 0 && match_passwordpolicy($password, $output)){
        $userPasswordHash = password_hash($password, PASSWORD_BCRYPT);
        $conn->query("UPDATE $userTable SET psw = '$userPasswordHash', keyCode = '$newMasterPass', lastPswChange = UTC_TIMESTAMP WHERE id = '$userID';");
        if($newMasterPass) $_SESSION['masterpassword'] = base64_encode(simple_decryption($newMasterPass, $password));
        $validation_output  = '<div class="alert alert-success fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><strong>Success! </strong>Password successfully changed.</div>';
      } else {
        $validation_output  = '<div class="alert alert-danger fade in"><a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$output.'</div>';
      }
    } elseif(isset($_POST['savePAS'])){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
    } elseif(isset($_POST['masterPassword']) && crypt($_POST['masterPassword'], "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK") == "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK"){
      $userID = $_SESSION['userid'] = (crypt($_POST['masterPassword'], "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK") == "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK");
    } elseif(isset($_POST['stampIn']) || isset($_POST['stampOut'])){
      require __DIR__ ."/ckInOut.php";
      $validation_output  = '<div class="alert alert-info fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      if(isset($_POST['stampIn'])){
        checkIn($userID);
        $validation_output .= $lang['INFO_CHECKIN'].'</div>';
      } elseif(isset($_POST['stampOut'])){
        $error_output = checkOut($userID, intval($_POST['stampOut']));
        $validation_output .= $lang['INFO_CHECKOUT'].'</div>';
      }
    } elseif(isset($_POST["GERMAN"])){
      $sql="UPDATE $userTable SET preferredLang='GER' WHERE id = $userID";
      $conn->query($sql);
      $_SESSION['language'] = 'GER';
      $validation_output = mysqli_error($conn);
    } elseif(isset($_POST['ENGLISH'])){
      $sql="UPDATE $userTable SET preferredLang='ENG' WHERE id = $userID";
      $conn->query($sql);
      $_SESSION['language'] = 'ENG';
      $validation_output = mysqli_error($conn);
    }
    if(isset($_POST['set_skin'])){
      $_SESSION['color'] = $txt = test_input($_POST['set_skin']);
      $conn->query("UPDATE $userTable SET color = '$txt' WHERE id = $userID");
    }
    if(isset($_POST['saveSocial'])) {
      // picture upload
      if(isset($_FILES['profilePictureUpload']) && !empty($_FILES['profilePictureUpload']['name'])) {
        require_once __DIR__ . "/utilities.php";
        $pp = uploadFile("profilePictureUpload", 1, 1, 1);
        if(!is_array($pp)) {
          $stmt = $conn->prepare("UPDATE socialprofile SET picture = ? WHERE userID = $userID");
          echo $conn->error;
          $null = NULL;
          $stmt->bind_param("b", $null);
          $stmt->send_long_data(0, $pp);
          $stmt->execute();
          if ($stmt->errno) {
              echo $stmt->error;//displayError($stmt->error);
          } else {
              //displaySuccess($lang['SOCIAL_SUCCESS_IMAGE_UPLOAD']);
          }
          $stmt->close();
        } else {
            echo print_r($filename);
        }
      }
      // other settings
      if (isset($_POST['social_status'])) {
          $status = test_input($_POST['social_status']);
          $conn->query("UPDATE socialprofile SET status = '$status' WHERE userID = $userID");
      }
      if (isset($_POST['social_isAvailable'])) {
          $sql = "UPDATE socialprofile SET isAvailable = 'TRUE' WHERE userID = '$userID'";
      }
      else {
          $sql = "UPDATE socialprofile SET isAvailable = 'FALSE' WHERE userID = '$userID'";
      }
      $conn->query($sql);
    }
  }

  if($_SESSION['color'] == 'light'){
    $css_file = 'plugins/homeMenu/homeMenu_light.css';
  } elseif($_SESSION['color'] == 'dark'){
    $css_file = 'plugins/homeMenu/homeMenu_metro.css';
  } elseif($_SESSION['color'] == 'stellar') {
    $css_file = 'plugins/homeMenu/homeMenu_dark.css';
  } else {
    $css_file = '';
  }
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="plugins/font-awesome/css/font-awesome.min.css"/>

  <script src="plugins/jQuery/jquery.min.js"></script>
  <script src="plugins/bootstrap/js/bootstrap.min.js"></script>

  <link rel="stylesheet" type="text/css" href="plugins/select2/css/select2.min.css">
  <script src='plugins/select2/js/select2.min.js'></script>

  <link rel="stylesheet" type="text/css" href="plugins/dataTables/datatables.min.css"/>
  <script type="text/javascript" src="plugins/dataTables/datatables.min.js"></script>

  <link href="plugins/datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet" >
  <script type="text/javascript" src="plugins/datetimepicker/js/bootstrap-datetimepicker.min.js" ></script>
  <script type="text/javascript" src="plugins/maskEdit/jquery.mask.js" ></script>

  <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
  <link href="<?php echo $css_file; ?>" rel="stylesheet" />
  <title>Connect</title>
  <script>
  $(document).ready(function() {
    if($('#seconds').length) {
      var sec = parseInt(document.getElementById("seconds").innerHTML) + parseInt(document.getElementById("minutes").innerHTML) * 60 + parseInt(document.getElementById("hours").innerHTML) * 3600;
      function pad(val) {
        return val > 9 ? val : "0" + val;
      }
      window.setInterval(function(){
        document.getElementById("seconds").innerHTML = pad(++sec % 60);
        document.getElementById("minutes").innerHTML = pad(parseInt((sec / 60) % 60, 10));
        document.getElementById("hours").innerHTML = pad(parseInt(sec / 3600, 10));
      }, 1000);
    }
  });
  </script>
</head>
<body id="body_container" class="is-table-row">
  <div id="loader"></div>
  <!-- navbar -->
  <nav id="fixed-navbar-header" class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
      <div class="navbar-header hidden-xs">
        <a class="navbar-brand" href="../user/home" style="width:230px;">CONNECT</a>
      </div>
      <div class="collapse navbar-collapse hidden-xs" style="display:inline;float:left;">
        <ul class="nav navbar-nav" style="margin:10px">
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-paint-brush" ></i><span class="caret"></span></a>
            <ul class="dropdown-menu">
              <form method="POST" class="navbar-form navbar-left">
                <li><button type="submit" class="btn btn-link" name="set_skin" value="default">Neutral</button></li>
                <li><button type="submit" class="btn btn-link" name="set_skin" value="dark">Default</button></li>
                <li><button type="submit" class="btn btn-link" name="set_skin" value="light">Light</button></li>
                <li><button type="submit" class="btn btn-link" name="set_skin" value="stellar">Dark</button></li>
              </form>
            </ul>
          </li>
        </ul>
        <ul class="nav navbar-nav" style="margin:10px">
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-language" ></i><span class="caret"></span></a>
            <ul class="dropdown-menu">
              <form method="POST" class="navbar-form navbar-left">
                <li><button type="submit" style="background:none;border:none" name="ENGLISH"><img width="30px" height="20px" src="images/eng.png"></button> English</li>
                <li class="divider"></li>
                <li><button type="submit" style="background:none;border:none"  name="GERMAN"><img width="30px" height="20px" src="images/ger.png"></button> Deutsch</li>
              </form>
            </ul>
          </li>
        </ul>
      </div>
      <div class="navbar-right" style="margin-right:10px;">
        <a class="btn navbar-btn hidden-sm hidden-md hidden-lg" data-toggle="collapse" data-target="#sidemenu"><i class="fa fa-bars"></i></a>          
        <?php
          $result = $conn->query("SELECT * FROM socialprofile WHERE userID = $userID");
          $row = $result->fetch_assoc();
          $social_status = $row["status"];
          $social_isAvailable = $row["isAvailable"];
          $defaultGroupPicture = "images/group.png";
          $defaultPicture = "images/defaultProfilePicture.png";
          $profilePicture = $row['picture'] ? "data:image/jpeg;base64,".base64_encode($row['picture']) : $defaultPicture;
        ?>
        <?php if($enableSocialMedia == 'TRUE' && $canUseSocialMedia == 'TRUE'): ?>
        <a class="hidden-xs" data-toggle="modal" data-target="#socialSettings" role="button"><img  src='<?php echo $profilePicture; ?>' alt='Profile picture' class='img-circle' style='width:35px;display:inline-block;vertical-align:middle;'></a>
        <span class="navbar-text hidden-xs" data-toggle="modal" data-target="#socialSettings" role="button"><?php echo $_SESSION['firstname']; ?></span>
        <a class="btn navbar-btn navbar-link"  href="../social/home" title="<?php echo $lang['SOCIAL_MENU_ITEM']; ?>"><i class="fa fa-commenting"></i></a>
        <?php else: ?>
        <span class="navbar-text hidden-xs"><?php echo $_SESSION['firstname']; ?></span>
        <?php endif; ?>
        <?php if($isTimeAdmin == 'TRUE' && $numberOfAlerts > 0): ?>
          <a href="../time/check" class="btn navbar-btn navbar-link hidden-xs" title="Your Database is in an invalid state, please fix these Errors after clicking this button.">
            <i class="fa fa-bell"></i><span class="badge alert-badge"> <?php echo $numberOfAlerts; ?></span>
          </a>
        <?php endif; ?>
        <a class="btn navbar-btn navbar-link hidden-xs" data-toggle="collapse" href="#infoDiv_collapse"><i class="fa fa-info"></i></a>
        <a class="btn navbar-btn navbar-link" data-toggle="modal" data-target="#myModal"><i class="fa fa-gears"></i></a>
        <a class="btn navbar-btn navbar-link" href="../user/logout" title="Logout"><i class="fa fa-sign-out"></i></a>
      </div>
    </div>
  </nav>
  <!-- /navbar -->
  <div class="collapse" id="infoDiv_collapse">
    <div class="well" style="margin:0">
      <a href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php include 'version_number.php'; echo $VERSION_TEXT; ?>
      <br>
      The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
      the Software will perform substantially in accordance with the functional specifications set forth in the documentation. The software is provided "as is", without warranty of any kind, express or implied.
    </div>
  </div>

  <!-- modal -->
  <form method="POST">
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-content modal-sm" >
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title"><?php echo $lang['SETTINGS']; ?></h4>
        </div>
        <div class="modal-body">
          <label><?php echo $lang['PASSWORD_CURRENT']?></label>
          <input type="password" class="form-control" name="passwordCurrent" ><br>
          <label><?php echo $lang['NEW_PASSWORD']?></label>
          <input type="password" class="form-control" name="password" ><br>
          <label><?php echo $lang['NEW_PASSWORD_CONFIRM']?></label>
          <input type="password" class="form-control" name="passwordConfirm" ><br><br>
          <?php if($masterPasswordHash): ?>
          <label><?php echo $lang['MASTER_PASSWORD']?> (Experimentell)</label>
          <input type="password" class="form-control" name="passwordMaster"><br>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" name="savePAS"><?php echo $lang['SAVE']; ?></button>
        </div>
      </div>
    </div>
  </form>
  <!-- /modal -->
  <?php if($enableSocialMedia == 'TRUE' && $canUseSocialMedia == 'TRUE'): ?>
    <!-- social settings modal -->
    <form method="post" enctype="multipart/form-data">
      <div class="modal fade" id="socialSettings" tabindex="-1" role="dialog" aria-labelledby="socialSettingsLabel">
          <div class="modal-dialog" role="form">
              <div class="modal-content">
                  <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <h4 class="modal-title" id="socialSettingsLabel"><?php echo $lang['SOCIAL_PROFILE_SETTINGS']; ?></h4>
                  </div>
                  <br>
                  <div class="modal-body">
                      <!-- modal body -->
                      <img src='<?php echo $profilePicture; ?>' style='width:30%;height:30%;' class='img-circle center-block' alt='Profile Picture'>
                      <br>
                      <label class="btn btn-default">
                          <?php echo $lang['SOCIAL_UPLOAD_PICTURE']; ?>
                          <input type="file" name="profilePictureUpload" style="display:none">
                      </label>
                      <div class="checkbox">
                          <label>
                              <input type="checkbox" name="social_isAvailable" <?php if ($social_isAvailable == 'TRUE') { echo 'checked';} ?>><?php echo $lang['SOCIAL_AVAILABLE']; ?>
                          </label>
                          <br>
                      </div>
                      <label for="social_status"> <?php echo $lang['SOCIAL_STATUS'] ?> </label>
                      <input type="text" class="form-control" name="social_status" placeholder="<?php echo $lang['SOCIAL_STATUS_EXAMPLE'] ?>" value="<?php echo $social_status; ?>">
                      <!-- /modal body -->
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                      <button type="submit" class="btn btn-warning" name="saveSocial"><?php echo $lang['SAVE']; ?></button>
                  </div>
              </div>
          </div>
      </div>
  </form>
  <!-- /social settings modal -->
  <?php endif; ?>
<?php
$showProjectBookingLink = $cd = $diff = 0;
$disabled = '';

$result = mysqli_query($conn, "SELECT * FROM $configTable");
if ($result && ($row = $result->fetch_assoc())) {
  $cd = $row['cooldownTimer'];
  $bookingTimeBuffer = $row['bookingTimeBuffer'];
} else {
  $bookingTimeBuffer = 5;
}
//display checkin or checkout + disabled
$result = mysqli_query($conn,  "SELECT * FROM $logTable WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID");
if($result && ($row = $result->fetch_assoc())) { //checkout
  $buttonVal = $lang['CHECK_OUT'];
  $buttonNam = 'stampOut';
  //<img width="15px" height="15px" src="images/emji1.png"> 
  $showProjectBookingLink = TRUE;
  $diff = timeDiff_Hours($row['time'], getCurrentTimestamp());
  if($diff < $cd / 60) { $disabled = 'disabled'; }
  $buttonEmoji = '<div class="btn-group btn-group-xs btn-ckin" style="display:block;">
  <button type="submit" '.$disabled.' class="btn btn-emji emji1" name="stampOut" value="1" title="'.$lang['EMOJI_TOSTRING'][1].'"></button>
  <button type="submit" '.$disabled.' class="btn btn-emji emji2" name="stampOut" value="2" title="'.$lang['EMOJI_TOSTRING'][2].'"></button>
  <button type="submit" '.$disabled.' class="btn btn-emji emji3" name="stampOut" value="3" title="'.$lang['EMOJI_TOSTRING'][3].'"></button>
  <button type="submit" '.$disabled.' class="btn btn-emji emji4" name="stampOut" value="4" title="'.$lang['EMOJI_TOSTRING'][4].'"></button>
  <button type="submit" '.$disabled.' class="btn btn-emji emji5" name="stampOut" value="5" title="'.$lang['EMOJI_TOSTRING'][5].'"></button></div>
  <a data-toggle="modal" data-target="#explain-emji" style="position:relative;top:-7px;"><i class="fa fa-question-circle-o"></i></a>';
} else {
  $buttonVal = $lang['CHECK_IN'];
  $buttonNam = 'stampIn';
  $buttonEmoji = '';
  $today = getCurrentTimestamp();
  $result = mysqli_query($conn, "SELECT * FROM $logTable WHERE userID = $userID AND time LIKE '".substr($today, 0, 10)." %' AND status = '0'");
  if($result && ($row = $result->fetch_assoc())){
    $diff = timeDiff_Hours($row['timeEnd'], $today) + 0.0003;
    if($diff < $cd/60){ $disabled = 'disabled'; }
  }
}
$checkInButton = "<button $disabled type='submit' class='btn btn-warning btn-ckin' name='$buttonNam'>$buttonVal</button>";
?>

<div id="explain-emji" class="modal fade">
  <div class="modal-dialog modal-content modal-sm">
    <div class="modal-header h4">Bewerte deinen Tag!</div>
    <div class="modal-body">Bevor du ausstempelst, kannst du dabei auch gleichzeitig ein kurzes Feedback abgeben.<br><br>
    <div class="btn-group btn-group-xs" style="padding-left:25%">
    <button type="button" class="btn btn-emji emji1" title="Schrecklich">1</button>
    <button type="button" class="btn btn-emji emji2" title="Enttäuschend">2</button>
    <button type="button" class="btn btn-emji emji3" title="Neutral">3</button>
    <button type="button" class="btn btn-emji emji4" title="Gut">4</button>
    <button type="button" class="btn btn-emji emji5" title="Exzellent">5</button></div>
    <br><br> Drücke dafür statt "Ausstempeln" einfach auf eine Zahl von 1 für "Schrecklich" bis 5 für "Ausgezeichnet".
    <br><br>Damit wirst du ausgestempelt und lässt den Admin wissen, wie dein Tag war.
    <br><br>Bewertest du deinen Tag öfters, wird der Mittelwert deiner Bewertungen herangezogen.
    <br><br>Möchtest du gar kein Statement abgeben, kannst du auch wie gewohnt auf "Ausstempeln" drücken.</div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">OK</button></div>
  </div>
</div>

<!-- side menu -->
<div id="sidemenu" class="affix-sidebar sidebar-nav">
  <div class="inner">
    <div class="navbar navbar-default" role="navigation">
      <ul class="nav navbar-nav" id="sidenav01">
        <?php if($canStamp == 'TRUE'): ?>
          <li>
            <div class='container-fluid'>
              <form method='post' action="../user/home"><br>
                <?php
                echo $checkInButton;
                if($diff > 0){
                  echo '<div class="clock-counter" style="display:inline">';
                  echo "&nbsp;<span id='hours'>".sprintf("%02d",$diff)."</span>:<span id='minutes'>".sprintf("%02d",($diff * 60) % 60)."</span>:<span id='seconds'>".sprintf("%02d",($diff * 3600) % 60)."</span>";
                  echo '</div>';
                }
                echo '<br>'.$buttonEmoji;
                ?>
              </form><br>
            </div>
          </li>
          <!-- User-Section: BASIC -->
          <li><a <?php if($this_page =='home.php'){echo $setActiveLink;}?> href="../user/home"><i class="fa fa-home"></i> <span><?php echo $lang['OVERVIEW']; ?></span></a></li>
          <li><a <?php if($this_page =='timeCalcTable.php'){echo $setActiveLink;}?> href="../user/time"><i class="fa fa-clock-o"></i> <span><?php echo $lang['VIEW_TIMESTAMPS']; ?></span></a></li>
          <li><a <?php if($this_page =='makeRequest.php'){echo $setActiveLink;}?> href="../user/request"><i class="fa fa-calendar-plus-o"></i> <span><?php echo $lang['REQUESTS']; ?></span></a></li>

          <!-- User-Section: BOOKING -->
          <?php if($canBook == 'TRUE' && $showProjectBookingLink): //a user cannot do projects if he cannot checkin m8 ?>
            <li><a <?php if($this_page =='userProjecting.php'){echo $setActiveLink;} ?> href="../user/book"><i class="fa fa-bookmark"></i><span> <?php echo $lang['BOOK_PROJECTS']; ?></span></a></li>
          <?php endif; ?>
        <?php endif; //endif(canStamp)?>
      </ul>
    </div>
    <div class="panel-group" id="sidebar-accordion">
      <!-- Section One: CORE -->
      <?php if($isCoreAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab" id="headingCore">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-core"  id="adminOption_CORE">
            <i class="fa fa-gear"></i> <?php echo $lang['ADMIN_CORE_OPTIONS']; ?><i class="fa fa-caret-down pull-right"></i>
            </a>
          </div>
          <div id="collapse-core" role="tabpanel" class="panel-collapse collapse"  aria-labelledby="headingCore">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li>
                  <a id="coreUserToggle" href="#" data-toggle="collapse" data-target="#toggleUsers" data-parent="#sidenav01" class="collapse in">
                    <span><?php echo $lang['USERS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleUsers" style="height: 0px;">
                    <ul class="nav nav-list">
                      <li><a <?php if($this_page =='editUsers.php'){echo $setActiveLink;}?> href="../system/users"><?php echo $lang['EDIT_USERS']; ?></a></li>
                      <li><a <?php if($this_page =='admin_saldoview.php'){echo $setActiveLink;}?> href="../system/saldo"><?php echo $lang['USERS']; ?> Saldo</a></li>
                      <li><a <?php if($this_page =='register.php'){echo $setActiveLink;}?> href="../system/register"><?php echo $lang['REGISTER']; ?></a></li>
                      <li><a <?php if($this_page =='deactivatedUsers.php'){echo $setActiveLink;}?> href="../system/deactivated"><?php echo $lang['USER_INACTIVE']; ?></a></li>
                      <li><a <?php if($this_page =='checkinLogs.php'){echo $setActiveLink;} ?> href="../system/checkinLogs">Checkin Logs</a></li>
                    </ul>
                  </div>
                </li>
                <li>
                  <a id="coreCompanyToggle" <?php if($this_page =='editCompanies.php'){echo $setActiveLink;}?> href="#" data-toggle="collapse" data-target="#toggleCompany" data-parent="#sidenav01" class="collapse in">
                    <span><?php echo $lang['COMPANIES']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleCompany" style="height: 0px;">
                    <ul class="nav nav-list">
                      <?php
                      $result = $conn->query("SELECT * FROM $companyTable");
                      while($result && ($row = $result->fetch_assoc())){
                        if(in_array($row['id'], $available_companies)){
                          echo "<li><a href='../system/company?cmp=".$row['id']."'>".$row['name']."</a></li>";
                        }
                      }
                      ?>
                      <li><a <?php if($this_page =='new_Companies.php'){echo $setActiveLink;}?> href="../system/new"><?php echo $lang['CREATE_NEW_COMPANY']; ?></a></li>
                    </ul>
                  </div>
                </li>
                <li><a <?php if($this_page =='editCustomers.php'){echo $setActiveLink;}?> href="../system/clients"><span><?php echo $lang['CLIENTS']; ?></span></a></li>
                <li><a <?php if($this_page =='teamConfig.php'){echo $setActiveLink;}?> href="../system/teams">Teams</a></li>
                <li>
                  <a id="coreSettingsToggle" href="#" data-toggle="collapse" data-target="#toggleSettings" data-parent="#sidenav01" class="collapsed">
                    <span><?php echo $lang['SETTINGS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleSettings" style="height: 0px;">
                    <ul class="nav nav-list">
                      <li><a <?php if($this_page =='editHolidays.php'){echo $setActiveLink;}?> href="../system/holidays"><span><?php echo $lang['HOLIDAYS']; ?></span></a></li>
                      <li><a <?php if($this_page =='advancedOptions.php'){echo $setActiveLink;}?> href="../system/advanced"><span><?php echo $lang['ADVANCED_OPTIONS']; ?></span></a></li>
                      <li><a <?php if($this_page =='passwordOptions.php'){echo $setActiveLink;}?> href="../system/password"><span><?php echo $lang['PASSWORD'].' '.$lang['OPTIONS']; ?></span></a></li>
                      <li><a <?php if($this_page =='reportOptions.php'){echo $setActiveLink;}?> href="../system/email"><span> E-mail <?php echo $lang['OPTIONS']; ?> </span></a></li>
                      <li><a <?php if($this_page =='taskScheduler.php'){echo $setActiveLink;}?> href="../system/tasks"><span><?php echo $lang['TASK_SCHEDULER']; ?> </span></a></li>
                      <li><a <?php if($this_page =='download_sql.php'){echo $setActiveLink;}?> href="../system/backup"><span> DB Backup</span></a></li>
                      <?php if(!getenv('IS_CONTAINER') && !isset($_SERVER['IS_CONTAINER'])): ?>
                        <li><a <?php if($this_page =='upload_database.php'){echo $setActiveLink;}?> href="../system/restore"><span> <?php echo $lang['DB_RESTORE']; ?></span> </a></li>
                        <li><a <?php if($this_page =='pullGitRepo.php'){echo $setActiveLink;}?> href="../system/update"><span>Git Update</span></a></li>
                      <?php endif; ?>
                      <li><a <?php if($this_page =='resticBackup.php'){echo $setActiveLink;}?> href="../system/restic"><span> Restic Backup</span></a></li>
                    </ul>
                  </div>
                </li>               
              </ul>
            </div>
          </div>
        </div>
        <?php
        if($this_page == "editUsers.php" || $this_page == "admin_saldoview.php" || $this_page == "register.php" || $this_page == "deactivatedUsers.php" || $this_page == "checkinLogs.php" ){
          echo "<script>document.getElementById('coreUserToggle').click();document.getElementById('adminOption_CORE').click();</script>";
        } elseif($this_page == "reportOptions.php" || $this_page == "editHolidays.php" || $this_page == "advancedOptions.php" || $this_page == "taskScheduler.php" || $this_page == "pullGitRepo.php" || $this_page == "passwordOptions.php"){
          echo "<script>document.getElementById('coreSettingsToggle').click();document.getElementById('adminOption_CORE').click();</script>";
        } elseif($this_page == "editCompanies.php" || $this_page == "new_Companies.php"){
          echo "<script>document.getElementById('coreCompanyToggle').click();document.getElementById('adminOption_CORE').click();</script>";
        } elseif($this_page == "download_sql.php" || $this_page == "teamConfig.php" || $this_page == "upload_database.php" || $this_page == "editCustomers.php" || $this_page == "editCustomer_detail.php") {
          echo "<script>document.getElementById('adminOption_CORE').click();</script>";
        }
        ?>
      <?php endif; ?>

      <!-- Section Two: TIME -->
      <?php if($isTimeAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab" id="headingTime">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-time"  id="adminOption_TIME">
            <i class="fa fa-history"></i> <?php echo $lang['ADMIN_TIME_OPTIONS']; ?><i class="fa fa-caret-down pull-right"></i>
            </a>
          </div>
          <div id="collapse-time" class="panel-collapse collapse" role="tabpanel"  aria-labelledby="headingTime">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li><a <?php if($this_page =='getTimeprojects.php'){echo $setActiveLink;}?> href="../time/view"> <span><?php echo $lang['TIMES'].' '.$lang['VIEW']; ?></span></a></li>
                <li><a <?php if($this_page =='bookAdjustments.php'){echo $setActiveLink;}?> href="../time/corrections"><?php echo $lang['CORRECTION']; ?></a></li>
                <li><a <?php if($this_page =='getTravellingExpenses.php'){echo $setActiveLink;}?> href="../time/travels"><?php echo $lang['TRAVEL_FORM']; ?></a></li>
                <li><a <?php if($this_page =='display_vacation.php'){echo $setActiveLink;}?> href="../time/vacations"><?php echo $lang['VACATION']; ?></a></li>
                <li><a <?php if($this_page =='adminTodos.php'){echo $setActiveLink;}?> href="../time/check"><?php echo $lang['CHECKLIST']; ?></a></li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if($this_page == "getTimeprojects.php" || $this_page == "monthlyReport.php" || $this_page == "adminTodos.php" || $this_page == "getTravellingExpenses.php" || $this_page == "bookAdjustments.php" || $this_page == "getTimestamps_select.php" || $this_page == 'display_vacation.php'){
          echo "<script>document.getElementById('adminOption_TIME').click();</script>";
        }
        ?>
      <?php endif; ?>

      <!-- Section Three: PROJECTS -->
      <?php if($isProjectAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab" id="headingProject">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-project"  id="adminOption_PROJECT">
            <i class="fa fa-tags"></i><?php echo $lang['ADMIN_PROJECT_OPTIONS']; ?><i class="fa fa-caret-down pull-right"></i>
            </a>
          </div>
          <div id="collapse-project" class="panel-collapse collapse" role="tabpanel"  aria-labelledby="headingProject">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li><a <?php if($this_page =='editProjects.php'){echo $setActiveLink;}?> href="../project/view"><span><?php echo $lang['STATIC_PROJECTS']; ?></span></a></li>
                <li><a <?php if($this_page =='audit_projectBookings.php'){echo $setActiveLink;}?> href="../project/log"><span><?php echo $lang['PROJECT_LOGS']; ?></span></a></li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if($this_page == "editProjects.php" || $this_page == "audit_projectBookings.php"){
          echo "<script>$('#adminOption_PROJECT').click();</script>";
        }
        ?>
      <?php endif; ?>
      <!-- Section Four: REPORTS -->
      <?php if($isReportAdmin == 'TRUE' || $canEditTemplates == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab" id="headingReport">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-report"  id="adminOption_REPORT">
            <i class="fa fa-bar-chart"></i><?php echo $lang['REPORTS']; ?><i class="fa fa-caret-down pull-right"></i>
            </a>
          </div>
          <div id="collapse-report" class="panel-collapse collapse" role="tabpanel"  aria-labelledby="headingReport">
            <div class="panel-body">
              <ul class="nav navbar-nav">
              <?php if($isReportAdmin == 'TRUE'): ?>
                <li><a target="_blank" href="../report/send"><span> Send E-Mails </span></a></li>
                <li><a <?php if($this_page =='report_productivity.php'){echo $setActiveLink;}?> href="../report/productivity"><span><?php echo $lang['PRODUCTIVITY']; ?></span></a></li>
              <?php endif; ?>
                <li><a <?php if($this_page =='templateSelect.php'){echo $setActiveLink;}?> href="../system/designer"><span>Report Designer</span> </a></li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if($this_page == "report_productivity.php" || $this_page =='templateSelect.php'){
          echo "<script>$('#adminOption_REPORT').click();</script>";
        }
        ?>
      <?php endif; ?>
      <!-- Section Five: ERP -->
      <?php if($isERPAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab" id="headingERP">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-erp"  id="adminOption_ERP"><i class="fa fa-file-text-o"></i> ERP<i class="fa fa-caret-down pull-right"></i></a>
          </div>
          <div id="collapse-erp" class="panel-collapse collapse" role="tabpanel"  aria-labelledby="headingERP">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li><a <?php if($this_page =='offer_proposal_edit.php'){echo $setActiveLink;}?> href="../erp/view"><span><?php echo $lang['PROCESS']; ?></span></a></li>
                <li><a id="erpClients" href="#" data-toggle="collapse" data-target="#toggleERPClients" data-parent="#sidenav01" class="collapsed">
                    <span><?php echo $lang['CLIENTS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleERPClients">
                    <ul class="nav nav-list">
                      <li><a <?php if(isset($_GET['t']) && $_GET['t'] == 'ang') echo $setActiveLink; ?> href="../erp/view?t=ang"><span><?php echo $lang['PROPOSAL_TOSTRING']['ANG']; ?></span></a></li>
                      <li><a href="../erp/view?t=aub"><span><?php echo $lang['PROPOSAL_TOSTRING']['AUB']; ?></span></a></li>
                      <li><a href="../erp/view?t=re"><span><?php echo $lang['PROPOSAL_TOSTRING']['RE']; ?></span></a></li>
                      <li><a href="../erp/view?t=lfs"><span><?php echo $lang['PROPOSAL_TOSTRING']['LFS']; ?></span></a></li>
                      <li><a <?php if($this_page == 'editCustomers.php'){echo $setActiveLink;}?> href="../system/clients?t=1"><span><?php echo $lang['CLIENT_LIST']; ?></span></a></li>
                    </ul>
                  </div>
                </li>
                <li><a id="erpSuppliers" href="#" data-toggle="collapse" data-target="#toggleERPSuppliers" data-parent="#sidenav01" class="collapsed">
                    <span><?php echo $lang['SUPPLIERS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleERPSuppliers">
                    <ul class="nav nav-list">
                      <li><a disabled href=""><span><?php echo $lang['ORDER']; ?></span></a></li>
                      <li><a disabled href=""><span><?php echo $lang['INCOMING_INVOICE']; ?></span></a></li>
                      <li><a <?php if($this_page =='editSuppliers.php'){echo $setActiveLink;}?> href="../erp/suppliers"><span><?php echo $lang['SUPPLIER_LIST']; ?></span></a></li>
                    </ul>
                  </div>
                </li>
                
                <li><a <?php if($this_page =='product_articles.php'){echo $setActiveLink;}?> href="../erp/articles"><span><?php echo $lang['ARTICLE']; ?></span></a></li>
                <li><a <?php if($this_page =='receiptBook.php'){echo $setActiveLink;}?> href="../erp/receipts"><span><?php echo $lang['RECEIPT_BOOK']; ?></span></a></li>
                <li><a disabled href=""><span><?php echo $lang['VACANT_POSITIONS']; ?></span></a></li>
                
                <li><a id="erpSettings" href="#" data-toggle="collapse" data-target="#toggleERPSettings" data-parent="#sidenav01" class="collapsed">
                    <span><?php echo $lang['SETTINGS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleERPSettings">
                    <ul class="nav nav-list">
                      <li><a <?php if($this_page == 'editTaxes.php'){echo $setActiveLink;}?> href="../erp/taxes"><span><?php echo $lang['TAX_RATES']; ?></span></a></li>
                      <li><a <?php if($this_page == 'editUnits.php'){echo $setActiveLink;}?> href="../erp/units"><span><?php echo $lang['UNITS']; ?></span></a></li>
                      <li><a <?php if($this_page == 'editPaymentMethods.php'){echo $setActiveLink;}?> href="../erp/payment"><span><?php echo $lang['PAYMENT_METHODS']; ?></span></a></li>
                      <li><a <?php if($this_page == 'editShippingMethods.php'){echo $setActiveLink;}?> href="../erp/shipping"><span><?php echo $lang['SHIPPING_METHODS']; ?></span></a></li>
                      <li><a <?php if($this_page == 'editRepres.php'){echo $setActiveLink;}?> href="../erp/representatives"><span><?php echo $lang['REPRESENTATIVE']; ?></span></a></li>
                    </ul>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if(isset($_GET['t']) || $this_page == "offer_proposals.php" || $this_page == "offer_proposal_edit.php" ){
          echo "<script>$('#adminOption_ERP').click();$('#erpClients').click();";
          echo '$(document).ready(function() { $("#sidemenu").animate({ scrollTop: $("#sidemenu").prop("scrollHeight")}, 1500); });</script>';
        } elseif($this_page == "editSuppliers.php" ){
          echo "<script>$('#adminOption_ERP').click();$('#erpSuppliers').click();";
          echo '$(document).ready(function() { $("#sidemenu").animate({ scrollTop: $("#sidemenu").prop("scrollHeight")}, 1500); });</script>';
        } elseif($this_page == "editTaxes.php" || $this_page == "editUnits.php" || $this_page == "editPaymentMethods.php" || $this_page == "editShippingMethods.php" || $this_page == "editRepres.php"){
          echo "<script>$('#adminOption_ERP').click();$('#erpSettings').click();";
          echo '$(document).ready(function() { $("#sidemenu").animate({ scrollTop: $("#sidemenu").prop("scrollHeight")}, 1500); });</script>';
        } elseif($this_page == "product_articles.php" || $this_page == "receiptBook.php" ){
          echo "<script>$('#adminOption_ERP').click();";
          echo '$(document).ready(function() { $("#sidemenu").animate({ scrollTop: $("#sidemenu").prop("scrollHeight")}, 1500); });</script>';
        }
        ?>
      <?php endif; ?>
      <!-- Section Six: FINANCES -->
      <?php if($isFinanceAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-finance"  id="adminOption_FINANCE"><i class="fa fa-book"></i><?php echo $lang['FINANCES']; ?><i class="fa fa-caret-down pull-right"></i></a>
          </div>
          <div id="collapse-finance" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <?php
                $result = $conn->query("SELECT id, name FROM $companyTable WHERE id IN (".implode(', ', $available_companies).")");
                while($result && ($row = $result->fetch_assoc())){
                  echo '<li>';                  
                  echo '<a id="finance-click-'.$row['id'].'" href="#" data-toggle="collapse" data-target="#tfinances-'.$row['id'].'" data-parent="#sidenav01" class="collapsed">'.$row['name'].' <i class="fa fa-caret-down"></i></a>';
                  echo '<div class="collapse" id="tfinances-'.$row['id'].'" >';                  
                  echo '<ul class="nav nav-list">';
                  echo '<li><a href="../finance/plan?n='.$row['id'].'">'.$lang['ACCOUNT_PLAN'].'</a></li>';
                  echo '<li><a href="../finance/journal?n='.$row['id'].'">'.$lang['ACCOUNT_JOURNAL'].'</a></li>';
                  $acc_res = $conn->query("SELECT id, name, companyID FROM accounts WHERE manualBooking='TRUE' AND companyID = ".$row['id']);
                  while($acc_res && ($acc_row = $acc_res->fetch_assoc())){
                    echo '<li><a href="../finance/account?v='.$acc_row['id'].'">'.$acc_row['name'].'</a></li>';
                    if($this_page == 'accounting.php' && !empty($_GET['v']) && $_GET['v'] == $acc_row['id']){
                      echo "<script>$('#finance-click-".$acc_row['companyID']."').click();</script>";
                    }
                  }
                  echo '</ul></div></li>';
                }
                ?>
                <li><a <?php if($this_page == 'editTaxes.php'){echo $setActiveLink;}?> href="../erp/taxes"><span><?php echo $lang['TAX_RATES']; ?></span></a></li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if($this_page == "accounting.php" || $this_page == 'accountPlan.php' || $this_page == 'accountJournal.php'){
          echo "<script>$('#adminOption_FINANCE').click();";
          if(isset($_GET['n'])){
            echo "$('#finance-click-".$_GET['n']."').click();";
          }
          echo '$(document).ready(function() { $("#sidemenu").animate({ scrollTop: $("#sidemenu").prop("scrollHeight")}, 1500); });';
          echo '</script>';
        }
        ?>
      <?php endif; ?>
      <!-- Section Six: DSGVO -->
      <?php if($isDSGVOAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-dsgvo"  id="adminOption_DSGVO"><strong style="padding: 0px 6px;"> § </strong>DSGVO<i class="fa fa-caret-down pull-right"></i></a>
          </div>
          <div id="collapse-dsgvo" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <?php
                $result = $conn->query("SELECT id, name FROM $companyTable WHERE id IN (".implode(', ', $available_companies).")");
                while($result && ($row = $result->fetch_assoc())){
                  echo '<li>';
                  echo '<a href="#" data-toggle="collapse" data-target="#tdsgvo-'.$row['id'].'" data-parent="#sidenav01" class="collapsed">'.$row['name'].' <i class="fa fa-caret-down"></i></a>';
                  echo '<div class="collapse" id="tdsgvo-'.$row['id'].'" >';
                  echo '<ul class="nav nav-list">';
                  echo '<li><a href="../dsgvo/documents?n='.$row['id'].'">'.$lang['DOCUMENTS'].'</a></li>';
                  echo '<li><a href="../dsgvo/templates?n='.$row['id'].'">E-Mail Templates</a></li>';
                  echo '</ul></div></li>';
                }
                ?>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if($this_page == "dsgvo_view.php" || $this_page == "dsgvo_edit.php" || $this_page == "dsgvo_mail.php"){
          echo "<script>$('#adminOption_DSGVO').click();";
          if(isset($_GET['n'])){
            echo "$('#tdsgvo-".$_GET['n']."').toggle();";
          }
          echo '$(document).ready(function() { $("#sidemenu").animate({ scrollTop: $("#sidemenu").prop("scrollHeight")}, 1500); });';
          echo '</script>';
        }
        ?>
      <?php endif; ?>
      <br><br>
    </div> <!-- /accordions -->
    <br><br><br>
  </div>
</div>
<!-- /side menu -->

<div id="bodyContent" style="display:none;" >
  <div class="affix-content">
    <div class="container-fluid">
      <span><?php echo $validation_output; ?></span>
      <span><?php echo $error_output; ?></span>

<?php
  $result = $conn->query("SELECT expiration, expirationDuration, expirationType FROM $policyTable"); echo $conn->error;
  $row = $result->fetch_assoc();  
  if($row['expiration'] == 'TRUE'){ //can a password expire?
    $pswDate = date('Y-m-d', strtotime("+".$row['expirationDuration']." months", strtotime($lastPswChange)));
    if(timeDiff_Hours($pswDate, getCurrentTimestamp()) > 0){ //has my password actually expired?
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Your Password has expired. </strong> Please change it by clicking on the gears in the top right corner.</div>';
      if($row['expirationType'] == 'FORCE'){ //force the change
        include 'footer.php';
        die();
      }
    }
  }
  if($masterPasswordHash && !empty($_SESSION['masterpassword'])){
    if(simple_decryption($masterPass_checkSum, $_SESSION['masterpassword']) != 'ABCabc123!') echo '<div class="alert alert-info"><a href="#" data-dismiss="alert" class="close">&times;</a>Das Masterpasswort wurde geändert. </div>';
  }
  $user_agent = $_SERVER["HTTP_USER_AGENT"];
  if(strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7') || strpos($user_agent, 'Edge') ) {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Der Browser den Sie verwenden ist veraltet oder unterstützt wichtige Funktionen nicht. Wenn Sie Probleme mit der Anzeige oder beim Interagieren bekommen, versuchen sie einen anderen Browser. </div>';
  }
?>
