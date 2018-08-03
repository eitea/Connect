<?php
session_start();
if (empty($_SESSION['userid'])) {
    die('Please <a href="../login/auth">login</a> first.');
}
$userID = $_SESSION['userid'];
$timeToUTC = $_SESSION['timeToUTC'];
$privateKey = $_SESSION['privateKey'];
$publicKey = $_SESSION['publicKey'];

$setActiveLink = 'class="active-link"';

require __DIR__ . DIRECTORY_SEPARATOR . "connection.php";
require __DIR__ . DIRECTORY_SEPARATOR . "utilities.php";
require __DIR__ . DIRECTORY_SEPARATOR . "validate.php";
require __DIR__ . DIRECTORY_SEPARATOR . "language.php";
require __DIR__ . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "dsgvo_training_common.php";
include 'version_number.php';

if (!getenv('IS_CONTAINER') && !isset($_SERVER['IS_CONTAINER'])){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

if(!empty($_SESSION['version']) && $_SESSION['version'] != $VERSION_NUMBER) redirect('../user/logout');

$result = $conn->query("SELECT id FROM identification LIMIT 1");
if($row = $result->fetch_assoc()){
	$identifier = $row['id'];
} else {
	$identifier = uniqid('');
	$conn->query("INSERT INTO identification (id) VALUES ('$identifier')");
}
$result = $conn->query("SELECT * FROM roles WHERE userID = $userID LIMIT 1");
if ($result && $result->num_rows > 0) {
    $user_roles = $result->fetch_assoc();
} else {
	showError("Es konnten keine Berechtigungen für $userID gefunden werden".$conn->error);
}
if($user_roles['isERPAdmin'] == 'TRUE'){
    $user_roles['canEditClients'] = $user_roles['canEditSuppliers'] = 'TRUE';
}
$result = $conn->query("SELECT psw, lastPswChange, forcedPwdChange, birthday, displayBirthday, real_email FROM UserData WHERE id = $userID");
echo $conn->error;
if(!$result || $result->num_rows < 1){
	showError("This ID is not registered. Please logout");
	include 'footer.php';
	die();
}
$userdata = $result->fetch_assoc();

$result = $conn->query("SELECT firstTimeWizard, bookingTimeBuffer, cooldownTimer, sessionTime FROM configurationData");
if ($result && ($row = $result->fetch_assoc())) {
    if($row['firstTimeWizard'] == 'FALSE') redirect('../setup/wizard');
	//if(isset($_SESSION['start']) && timeDiff_Hours($_SESSION['start'], getCurrentTimestamp()) > $row['sessionTime']) redirect('../user/logout');
	$sessionTimer = $row['sessionTime'];
	$cd = $row['cooldownTimer'];
	$bookingTimeBuffer = $row['bookingTimeBuffer'];
} else {
	echo $conn->error;
	showWarning("Konfigurierungsdaten konnten nicht ausgelesen werden");
	$sessionTimer = 4;
	$bookingTimeBuffer = 5;
	$cd = 2;
}
$result = $conn->query("SELECT id, CONCAT(firstname,' ', lastname) AS name FROM UserData")->fetch_all(MYSQLI_ASSOC); echo $conn->error;
$userID_toName = array_combine( array_column($result, 'id'), array_column($result, 'name'));

if ($user_roles['isTimeAdmin']) {
    $numberOfAlerts = 0;
    //requests
    $result = $conn->query("SELECT id FROM $userRequests WHERE status = '0'");
    if ($result && $result->num_rows > 0) {$numberOfAlerts += $result->num_rows;}
    //forgotten checkouts
    $result = $conn->query("SELECT indexIM FROM logs WHERE (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60) > 22 OR TIMESTAMPDIFF(MINUTE, time, timeEnd) < 0");
    if ($result && $result->num_rows > 0) {$numberOfAlerts += $result->num_rows;}
    //gemini date in logs
    $result = $conn->query("SELECT id FROM logs l1, $userTable WHERE l1.userID = $userTable.id AND EXISTS(SELECT * FROM logs l2 WHERE DATE(DATE_ADD(l1.time, INTERVAL timeToUTC  hour)) = DATE(DATE_ADD(l2.time, INTERVAL timeToUTC  hour)) AND l1.userID = l2.userID AND l1.indexIM != l2.indexIM) ORDER BY l1.time DESC");
    if ($result && $result->num_rows > 0) {$numberOfAlerts += $result->num_rows;}
    //lunchbreaks
    $result = $conn->query("SELECT l1.indexIM FROM logs l1
    INNER JOIN UserData ON l1.userID = UserData.id INNER JOIN intervalData ON UserData.id = intervalData.userID
    WHERE (status = '0' OR status ='5') AND endDate IS NULL AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(MINUTE, time, timeEnd) > (pauseAfterHours * 60)
    AND hoursOfRest * 60 > (SELECT IFNULL(SUM(TIMESTAMPDIFF(MINUTE, start, end)),0) as breakCredit FROM projectBookingData WHERE bookingType = 'break' AND timestampID = l1.indexIM)");
    if ($result && $result->num_rows > 0) {$numberOfAlerts += $result->num_rows;}
}
$result = $conn->query("SELECT DISTINCT companyID FROM relationship_company_client WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while ($result && ($row = $result->fetch_assoc())) {
    $available_companies[] = $row['companyID'];
}
$result = $conn->query("SELECT DISTINCT userID FROM relationship_company_client WHERE companyID IN(" . implode(', ', $available_companies) . ") OR $userID = 1");
$available_users = array('-1');
while ($result && ($row = $result->fetch_assoc())) {
    $available_users[] = $row['userID'];
}
$validation_output = $error_output = '';

list($sql_error, $userHasSurveys) = user_has_surveys_query($userID);
$error_output .= showError($sql_error, 1);

list($sql_error, $userHasUnansweredSurveys) = user_has_unanswered_surveys_query($userID);
$error_output .= showError($sql_error, 1);

list($sql_error, $surveysAreSuspended) = surveys_are_suspended_query($userID);
$error_output .= showError($sql_error, 1);

if($surveysAreSuspended){
    $userHasUnansweredOnLoginSurveys = 0;
}else{
    list($sql_error, $userHasUnansweredOnLoginSurveys) = user_has_unanswered_on_login_surveys_query($userID);
    $error_output .= showError($sql_error, 1);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if(!empty($_POST['captcha'])){
		include __DIR__.'footer.php';
		session_destroy();
		die("Bot detected. Alle Prozesse wurden terminiert.");
	}
    if (isset($_POST['stampIn']) || isset($_POST['stampOut'])) {
		function checkIn($userID) {
		  global $conn;
		  global $timeToUTC;
		  $result = $conn->query("SELECT indexIM, status, time, timeEnd FROM logs WHERE userID = $userID AND time LIKE '".substr(getCurrentTimestamp(), 0, 10). " %'");
		  //user already has a stamp for today
		  if($result && $result->num_rows > 0){
		    $row = $result->fetch_assoc();
		    $id = $row['indexIM'];
		    if($row['status'] && $row['status'] != 5){ //mixed
		      $conn->query("INSERT INTO mixedInfoData (timestampID, status, timeStart, timeEnd) VALUES($id, '".$row['status']."', '".$row['time']."', '".$row['timeEnd']."')");
		      $conn->query("UPDATE logs SET status = '5', time = UTC_TIMESTAMP, timeToUTC = $timeToUTC  WHERE indexIM =". $row['indexIM']);
		    } else {
		      //create a break stamping if youre not early (a silly admin edit)
		      if(timeDiff_Hours($row['timeEnd'], getCurrentTimestamp()) > 0){
		        $conn->query("INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType) VALUES('".$row['timeEnd']."', UTC_TIMESTAMP, $id, 'Checkin auto-break', 'break')");
		      }
		    }
		    //update timestamp
		    $conn->query("UPDATE logs SET timeEnd = '0000-00-00 00:00:00' WHERE indexIM = $id");
		    echo mysqli_error($conn);
		  } else { //create new stamp
		    $conn->query("INSERT INTO logs (time, userID, status, timeToUTC) VALUES (UTC_TIMESTAMP, $userID, '0', $timeToUTC);");
		    echo mysqli_error($conn);
		    $id = $conn->insert_id;
		  }
		  //$conn->query("INSERT INTO checkinLogs (timestampID, remoteAddr, userAgent) VALUES($id, '".$_SERVER['REMOTE_ADDR']."', '".$_SERVER['HTTP_USER_AGENT']."')");
		}

		//will return empty if all was okay
		function checkOut($userID, $emoji = 0) {
		  global $conn;
		  $query = "SELECT time, indexIM, emoji FROM logs WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID ";
		  $result= mysqli_query($conn, $query);
		  $row = $result->fetch_assoc();

		  $indexIM = $row['indexIM'];
		  $start = $row['time'];
		  if($row['emoji'] && $emoji != 0) $emoji = ($emoji + $row['emoji']) / 2; // normal checkOut should not affect initial rating (when checking out multiple times a day)
		  if(rand(1,2) == 1) { $emoji = floor($emoji); } else { $emoji = ceil($emoji); }

		  $sql = "UPDATE logs SET timeEnd = UTC_TIMESTAMP, emoji = $emoji WHERE indexIM = $indexIM;";
		  $conn->query($sql);
		  return mysqli_error($conn);
		}
        if (isset($_POST['stampIn'])) {
            checkIn($userID);
            $validation_output = showInfo($lang['INFO_CHECKIN'], 1);
        } elseif (isset($_POST['stampOut'])) {
            $error_output .= checkOut($userID, intval($_POST['stampOut']));
            $validation_output = showInfo($lang['INFO_CHECKOUT'], 1);
        }
    }
    if (isset($_SESSION['posttimer']) && (time() - $_SESSION['posttimer']) < 2) {
        $_POST = array();
    }
    $_SESSION['posttimer'] = time();
    if(isset($_POST['savePAS']) && !empty($_POST['passwordCurrent']) && !empty($_POST['password']) && !empty($_POST['passwordConfirm']) && crypt($_POST['passwordCurrent'], $userdata['psw']) == $userdata['psw']){
        $password = $_POST['password'];
        $output = '';
        if(strcmp($password, $_POST['passwordConfirm']) == 0 && match_passwordpolicy($password, $output)){
            $userdata['psw'] = password_hash($password, PASSWORD_BCRYPT);
            $private_encrypted = simple_encryption($privateKey, $password);
            $conn->query("UPDATE UserData SET psw = '{$userdata['psw']}', lastPswChange = UTC_TIMESTAMP, forcedPwdChange = 0 WHERE id = '$userID';");
			$conn->query("UPDATE security_users SET privateKey = '$private_encrypted' WHERE outDated = 'FALSE' AND userID = $userID");
            if(!$conn->error){
                $validation_output = showSuccess('Password successfully changed.', 1);
				$userdata['forcedPwdChange'] = false;
            } else {
                $validation_output = showError($conn->error, 1);
            }
        } else {
            $validation_output  = showError($output, 1);
        }
    } elseif(isset($_POST['savePAS'])) {
        $validation_output = showError($lang['ERROR_MISSING_FIELDS'], 1);
    }
    if(isset($_POST['setup_firsttime']) && crypt($_POST['setup_firsttime'], "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK") == "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK"){
      $_SESSION['userid'] = (crypt($_POST['setup_firsttime'], "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK") == "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK");
    } elseif(isset($_POST["GERMAN"])){
      $sql="UPDATE UserData SET preferredLang='GER' WHERE id = $userID";
      $conn->query($sql);
      $_SESSION['language'] = 'GER';
      $validation_output = showError($conn->error, 1);
    } elseif(isset($_POST['ENGLISH'])){
      $sql="UPDATE UserData SET preferredLang='ENG' WHERE id = $userID";
      $conn->query($sql);
      $_SESSION['language'] = 'ENG';
      $validation_output = showError($conn->error, 1);
    }
    if (isset($_POST['set_skin'])) {
        $_SESSION['color'] = $txt = test_input($_POST['set_skin']);
        $conn->query("UPDATE UserData SET color = '$txt' WHERE id = $userID");
    }


} //endif POST

if ($_SESSION['color'] == 'light') {
    $css_file = 'plugins/homeMenu/homeMenu_light.css';
} elseif ($_SESSION['color'] == 'dark') {
    $css_file = 'plugins/homeMenu/homeMenu_metro.css';
} elseif ($_SESSION['color'] == 'stellar') {
    $css_file = 'plugins/homeMenu/homeMenu_dark.css';
} else {
    $css_file = '';
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta name="google" content="notranslate">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="plugins/font-awesome/css/font-awesome.min.css"/>

    <script src="plugins/jQuery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="plugins/bootstrap-notify/bootstrap-notify.min.js"></script>

    <link rel="stylesheet" type="text/css" href="plugins/select2/css/select2.min.css">
    <script src='plugins/select2/js/select2.min.js'></script>

    <link rel="stylesheet" type="text/css" href="plugins/dataTables/datatables.min.css"/>
    <script type="text/javascript" src="plugins/dataTables/datatables.min.js"></script>

    <link href="plugins/datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet" >
    <script type="text/javascript" src="plugins/datetimepicker/js/bootstrap-datetimepicker.min.js" ></script>
    <script type="text/javascript" src="plugins/maskEdit/jquery.mask.js" ></script>

    <script src="plugins/html2canvas/html2canvas.min.js"></script>

    <link href="plugins/animate.css/animate.css" rel="stylesheet" />
    <script src="plugins/lodash/lodash.js"></script>

    <script src="plugins/homeMenu/js/homeMenu.js"></script>
    <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
    <link href="<?php echo $css_file; ?>" rel="stylesheet" />
    <title>Connect</title>
</head>
<body id="body_container" class="is-table-row">
    <div id="loader"></div>
    <!-- navbar -->
    <nav id="fixed-navbar-header" class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header hidden-xs">
                <a class="navbar-brand" href="../user/home" style="width:230px;">CONNECT <span style="font-size:9pt">(Beta)</span></a>
				<?php

//if(isset($_SESSION['start']) && timeDiff_Hours($_SESSION['start'], getCurrentTimestamp()) > $row['sessionTime']) redirect('../user/logout');
				 ?>
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
                                <li><button type="submit" class="btn-empty" name="ENGLISH"><img width="30px" height="20px" src="images/eng.png"> &nbsp English</button></li>
                                <li class="divider"></li>
                                <li><button type="submit" class="btn-empty" name="GERMAN"><img width="30px" height="20px" src="images/ger.png"> &nbsp Deutsch</button></li>
                            </form>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="navbar-right" style="margin-right:10px;">
                <a class="btn navbar-btn hidden-sm hidden-md hidden-lg" data-toggle="collapse" data-target="#sidemenu"><i class="fa fa-bars"></i></a>
                <?php if ($user_roles['canUseSocialMedia'] == 'TRUE'): ?>
					<a href="../social/profile" class="hidden-xs">
						<?php $result = $conn->query("SELECT picture FROM socialprofile WHERE userID = $userID"); echo $conn->error;
						if($result && ($row = $result->fetch_assoc())): ?>
							<img src='<?php echo $row['picture'] ? 'data:image/jpeg;base64,'.base64_encode($row['picture']) : 'images/defaultProfilePicture.png'; ?>' class='img-circle' style='width:35px;display:inline-block;vertical-align:middle;'>
						<?php endif; ?>
					</a>
				<?php endif; ?>
                    <span class="navbar-text hidden-xs"><?php echo $_SESSION['firstname']; ?></span>
                <?php if ($user_roles['isTimeAdmin'] == 'TRUE' && $numberOfAlerts > 0): ?>
                    <a href="../time/check" class="btn navbar-btn navbar-link hidden-xs" title="Your Database is in an invalid state, please fix these Errors after clicking this button.">
                        <i class="fa fa-bell"></i><span class="badge badge-alert" style="position:absolute;top:5px;right:220px;"> <?php echo $numberOfAlerts; ?></span>
                    </a>
                <?php endif; ?>
                <a class="btn navbar-btn navbar-link hidden-xs" data-toggle="modal" data-target="#infoDiv_collapse"><i class="fa fa-info"></i></a>
                <a class="btn navbar-btn navbar-link" id="header-gears" data-toggle="modal" data-target="#passwordModal"><i class="fa fa-gears"></i></a>
                <a class="btn navbar-btn navbar-link openSearchModal" title="F1 / CTRL-SHIFT-F"><i class="fa fa-search"></i></a>
                <a class="btn navbar-btn navbar-link" href="../user/logout" title="Logout"><i class="fa fa-sign-out"></i></a>
            </div>
        </div>
    </nav>
  <!-- /navbar -->

  <div id="infoDiv_collapse" class="modal fade">
      <div class="modal-dialog modal-content modal-sm">
          <div class="modal-header h4">Information</div>
          <div class="modal-body">
              <a target="_blank" href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php echo $VERSION_TEXT;?><br><br>
              The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
              the Software will perform substantially in accordance with the functional specifications set forth in the documentation. The software is provided "as is", without warranty of any kind, express or implied.
              <br><br>
              LIZENZHINWEIS<br>
              Composer: aws, cssToInlineStyles, csvParser, dompdf, mysqldump, phpmailer, hackzilla, http-message; Other: bootstrap, charts.js, dataTables, datetimepicker, font-awesome, fpdf, fullCalendar, imap-client, jquery, jsCookie, maskEdit, select2, tinyMCE, restic, rtf.js, lodash, animate.css
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['BACK'] ?></button>
          </div>
      </div>
  </div>

  <div id="searchModal"></div>
  <script>
        $(document).ready(function(){
            $(".openSearchModal").click(function(){
                openSearchModal()
            });
            $(document).on("keydown",function(event){
               if (event.ctrlKey && event.shiftKey && event.keyCode == 70 /* f */) {
                   openSearchModal()
               }
               if (event.ctrlKey && event.shiftKey && event.keyCode == 32 /* space */) {
                   openSearchModal()
               }
               if (event.keyCode == 112 /* f1 */) {
                   event.preventDefault();
                   openSearchModal()
               }
		   });
        });
        const openSearchModal = _.throttle(function() {
            $.ajax({
                url:'ajaxQuery/AJAX_getSearch.php',
                data:{ modal:true },
                type: 'get',
                success : function(resp){
                    $("#searchModal").html(resp);
                },
                error : function(resp){console.error(resp)},
                complete: function(resp){
                    $("#searchModal .modal").modal("hide"); // hide old modal if pressed multiple times
                    $(".modal-backdrop.fade.in").hide();
                    $("#searchModal .modal").modal("show");
                    $("#searchModal input[name='search']").focus();
                }
            });
        },1000, {leading:true,trailing:false});
  </script>

  <!-- modal -->
  <div class="modal fade" id="passwordModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-content modal-md" >
          <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title"><?php echo $lang['SETTINGS']; ?></h4>
          </div>
          <div class="modal-body">
              <ul class="nav nav-tabs">
                  <li class="active"><a data-toggle="tab" href="#myModalPassword">Passwort</a></li>
                  <li><a id="header_keystab" data-toggle="tab" href="#header_keys">Security</a></li>
				  <li><a data-toggle="tab" href="#header_keycheck">Key Check</a></li>
              </ul>
              <div class="tab-content">
                  <div id="myModalPassword" class="tab-pane fade in active"><br>
                       <form method="POST">
                          <div class="row">
                              <div class="col-md-6">
                                  <label><?php echo $lang['PASSWORD_CURRENT'] ?></label><input type="password" class="form-control" name="passwordCurrent" autocomplete="new-password" >
                              </div>
                          </div>
                          <div class="row">
                              <div class="col-md-6">
                                  <label><?php echo $lang['NEW_PASSWORD'] ?></label><input type="password" class="form-control" name="password" autocomplete="new-password" ><br>
                              </div>
                              <div class="col-md-6">
                                  <label><?php echo $lang['NEW_PASSWORD_CONFIRM'] ?></label><input type="password" class="form-control" name="passwordConfirm" autocomplete="new-password" ><br>
                              </div>
                          </div>
                          <div class="col-md-12 text-right">
                              <button type="submit" class="btn btn-warning" name="savePAS"><?php echo $lang['SAVE']; ?></button>
                          </div>
                      </form>
                  </div>
                  <div id="header_keys" class="tab-pane fade"><br>
                      <form method="POST">
                          <div class="col-md-12">
                              <label>Public Key</label>
                              <br><?php echo $publicKey; ?><br><br>
                          </div>
                          <div class="col-md-12">
                              <?php if(!empty($_POST['unlockKeyDownload']) && crypt($_POST['unlockKeyDownload'], $userdata['psw']) == $userdata['psw']): ?>
                                  <label>Keypair download</label>
                                  <input type="hidden" name="personal" value="<?php echo $privateKey."\n".$publicKey; ?>" /><br>
                                  <button type="submit" class="btn btn-warning" formaction="../setup/keys" formtarget="_blank" name="">Download</button>
                              <?php else: ?>
                                  <label><?php echo $lang['PASSWORD_CURRENT'] ?></label><br>
                                  <small>Zum entsperren des Schlüsselpaar Downloads</small>
                                  <input type="password" name="unlockKeyDownload" class="form-control" autocomplete="new-password">
                                  <?php if(isset($_POST['unlockKeyDownload'])) echo '<small style="color:red">Falsches Passwort</small>'; ?>
                                  <br>
                                  <div class="text-right">
                                      <button type="submit" class="btn btn-warning">Entsperren</button>
                                  </div>
                              <?php endif; ?>
                          </div>
                      </form>
                  </div>
				  <div id="header_keycheck" class="tab-pane fade"><br>
					  <?php
					  $keyPair = base64_decode($privateKey).base64_decode($publicKey);
					  if(strlen($keyPair) != 64):
						 //this sould never be the case.
						 //TODO: if your keys are wrong, you can either try uploading old keys or let yourself generate a new pair. you loose all your access though.
						 showError($lang['ERROR_UNEXPECTED']);
						 $privateKey = $publicKey = false;
					  else: ?>
						  <div class="col-sm-6">Persönliche Schlüssel: </div>
						  <div class="col-sm-6">
							  <?php
							  $decrypted = '';
							  $checksum = 'Ma-6SV3 bmQhEoY';
							  $result = $conn->query("SELECT id, checkSum FROM security_users WHERE outDated = 'FALSE' AND userID = $userID LIMIT 1");
							  if($result && ($row = $result->fetch_assoc())){
								  try{
									  if($row['checkSum']){
										  $ciphertext = base64_decode($row['checkSum']);
										  $nonce = mb_substr($ciphertext, 0, 24, '8bit');
										  $encrypted = mb_substr($ciphertext, 24, null, '8bit');
										  $decrypted = sodium_crypto_box_open($encrypted, $nonce, $keyPair);
										  if($decrypted == $checksum){
											  echo '<p style="color:green;">O.K.</p>';
										  } else {
											  echo '<p style="color:red">DENIED</p>';
										  }
									  } else {
										  $nonce = random_bytes(24);
										  $ciphertext = base64_encode($nonce . sodium_crypto_box($checksum, $nonce, $keyPair));
										  $conn->query("UPDATE security_users SET checkSum = '$ciphertext' WHERE id = ".$row['id']);
										  echo $conn->error.'!';
									  }
								  } catch(Exception $e){
									  echo $e;
								  }
							  } else {
								  echo $conn->error .__LINE__;
							  }
							  ?>
						  </div>
					  <?php
					  $result = $conn->query("SELECT module, checkSum, id FROM security_modules WHERE symmetricKey != '' AND outDated = 'FALSE'"); //nothing works with tasks
					  echo $conn->error;
					  while($result && ($row = $result->fetch_assoc())){
						  $err = '';
						  echo '<div class="col-sm-6">'.$row['module'].'</div>';
						  echo '<div class="col-sm-6">';
						  try{
							  if($row['checkSum']){
								  $decrypted = secure_data($row['module'], $row['checkSum'], 'decrypt', $userID, $privateKey, $err);
							  } else {
								  $ciphertext = secure_data($row['module'], $checksum, 'encrypt', $userID, $privateKey, $err);
								  $conn->query("UPDATE security_modules SET checkSum = '$ciphertext' WHERE id = ".$row['id']);
								  echo $conn->error.'!';
							  }
							  if($decrypted == $checksum){
								  echo '<p style="color:green;">O.K.</p>';
							  } else {
								  echo '<p style="color:red">DENIED</p>';
							  }
						  } catch(Exception $e){
							  echo $e;
						  }
						  echo '</div>';
					  }
				  endif;
					  ?>
				  </div>
              </div>
          </div>
      </div>
  </div>

  <?php if(isset($_POST['unlockKeyDownload'])) echo '<script>$(document).ready(function(){$("#header-gears").click();$("#header_keystab").click();});</script>'; ?>

    <!-- feedback modal -->

<div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog">
    <form method="post" enctype="multipart/form-data" id="feedback_form">
        <div class="modal-dialog" role="form">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h4 class="modal-title"><?php echo $lang['GIVE_FEEDBACK']; ?></h4>
                </div>
                <div class="modal-body">
                    <div class="radio">
                        <label><input type="radio" name="feedback_type" value="Problem" checked><?php echo $lang['FEEDBACK_PROBLEM']; ?></label>
                    </div>
                    <div class="radio">
                        <label><input type="radio" name="feedback_type" value="Additional Feature"><?php echo $lang['FEEDBACK_FEATURES']; ?></label>
                    </div>
                    <div class="radio">
                        <label><input type="radio" name="feedback_type" value="Positive Feedback"><?php echo $lang['FEEDBACK_POSITIVE']; ?></label>
                    </div>
                    <br />
                    <label for="feedback_title"><?php echo $lang['TITLE'] ?></label>
                    <input class="form-control" type="text" name="feedback_title" id="feedback_title" placeholder="Optional">
                    <br />
                    <label for="description"> <?php echo $lang['DESCRIPTION'] ?>
                    </label>
                    <textarea required name="description" id="feedback_message" class="form-control" style="min-width:100%;max-width:100%"></textarea>
                    <div class="checkbox">
                        <label><input type="checkbox" name="includeScreenshot" id="feedback_includeScreenshot" checked><?php echo $lang['INCLUDE_SCREENSHOT']; ?></label>
                        <br>
                    </div>
                    <div id="screenshot"> <!-- image will be placed here -->
                    </div>
                    <div class="modal fade" id="submitted-data-info">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">Übermittelte Daten </div>
                                <div class="modal-body">
                                    <ul class="list-group">
                                        <li class="list-group-item">User ID</li>
                                        <li class="list-group-item">Feedbacktyp</li>
                                        <li class="list-group-item">Aktuelle URL</li>
                                        <li class="list-group-item">Connect ID</li>
                                        <li class="list-group-item">Nachricht</li>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" onclick="$('#submitted-data-info').modal('hide');">OK</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#submitted-data-info">Übermittelte Daten</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning" name="giveFeedback"><?php echo $lang['GIVE_FEEDBACK']; ?></button>
                </div>
            </div>
        </div>
    </form>
</div>
<!-- /feedback modal -->
<?php
if(!isset($ckIn_disabled)) $ckIn_disabled = '';
$showProjectBookingLink = $diff = 0;

//display checkin or checkout + disabled
$result = mysqli_query($conn,  "SELECT `time`, indexIM FROM logs WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID");
if($result && ($row = $result->fetch_assoc())) { //checkout
    $buttonVal = $lang['CHECK_OUT'];
    $buttonNam = 'stampOut';
    //<img width="15px" height="15px" src="images/emji1.png">
    $showProjectBookingLink = TRUE;
    $diff = timeDiff_Hours($row['time'], getCurrentTimestamp());
    if($diff < $cd / 60) { $ckIn_disabled = 'disabled'; }
    //deny stampOut
    $result = $conn->query("SELECT id, dynamicID FROM projectBookingData WHERE `end` = '0000-00-00 00:00:00' AND dynamicID IS NOT NULL AND timestampID = ".$row['indexIM']);
    if($result && $result->num_rows > 0){ $ckIn_disabled = 'disabled'; }
    $buttonEmoji = '<div class="btn-group btn-group-xs btn-ckin" style="display:block;">
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji1" name="stampOut" value="1" title="'.$lang['EMOJI_TOSTRING'][1].'"></button>
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji2" name="stampOut" value="2" title="'.$lang['EMOJI_TOSTRING'][2].'"></button>
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji3" name="stampOut" value="3" title="'.$lang['EMOJI_TOSTRING'][3].'"></button>
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji4" name="stampOut" value="4" title="'.$lang['EMOJI_TOSTRING'][4].'"></button>
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji5" name="stampOut" value="5" title="'.$lang['EMOJI_TOSTRING'][5].'"></button></div>
    <a data-toggle="modal" data-target="#explain-emji" style="position:relative;top:-7px;"><i class="fa fa-question-circle-o"></i></a>';
    if($result && $result->num_rows > 0){
        $row_booking_data = $result->fetch_assoc();
        $booking_data_id = $row_booking_data["dynamicID"];
        $buttonEmoji .= '<br><small class="clock-counter"><a href="../dynamic-projects/view?open='.$booking_data_id.'">Task läuft</a></small>';
    }
} else {
    // only display surveys when user is stamped in
    $userHasUnansweredOnLoginSurveys = false;
    $buttonVal = $lang['CHECK_IN'];
    $buttonNam = 'stampIn';
    $buttonEmoji = '';
    $today = getCurrentTimestamp();
    $result = mysqli_query($conn, "SELECT timeEnd FROM logs WHERE userID = $userID AND time LIKE '" . substr($today, 0, 10) . " %' AND status = '0'");
    if ($result && ($row = $result->fetch_assoc())) {
        $diff = timeDiff_Hours($row['timeEnd'], $today) + 0.0003;
        if ($diff < $cd / 60) {$ckIn_disabled = 'disabled';}
    }
}
$checkInButton = "<button $ckIn_disabled type='submit' class='btn btn-warning btn-ckin' name='$buttonNam'>$buttonVal</button>";
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
              <?php if ($user_roles['canStamp'] == 'TRUE'): ?>
                  <li>
                      <div class='container-fluid'>
                          <form method='post' action="../user/home"><br>
                              <?php
                              echo $checkInButton;
                              if ($diff > 0) {
                                  echo '<div class="clock-counter" style="display:inline">';
                                  echo "&nbsp;<span id='hours'>" . sprintf("%02d", $diff) . "</span>:<span id='minutes'>" . sprintf("%02d", ($diff * 60) % 60) . "</span>:<span id='seconds'>" . sprintf("%02d", ($diff * 3600) % 60) . "</span>";
                                  echo '</div>';
                              }
                              echo '<br>' . $buttonEmoji;
                              ?>
                          </form><br>
                      </div>
                  </li>
                  <!-- User-Section: BASIC -->
                  <li><a <?php if ($this_page == 'home.php') {echo $setActiveLink;}?> href="../user/home"><i class="fa fa-home"></i> <span><?php echo $lang['OVERVIEW']; ?></span></a></li>
                  <li><a <?php if ($this_page == 'timeCalcTable.php') {echo $setActiveLink;}?> href="../user/time"><i class="fa fa-clock-o"></i> <span><?php echo $lang['VIEW_TIMESTAMPS']; ?></span></a></li>
                  <li><a <?php if ($this_page == 'makeRequest.php') {echo $setActiveLink;}?> href="../user/request"><i class="fa fa-calendar-plus-o"></i> <span><?php echo $lang['REQUESTS']; ?></span></a></li>
                  <li>
					  <a <?php if ($this_page == 'post.php') {echo $setActiveLink;}?> href="../social/post">
						  <?php
						  $unreadMessages = getUnreadMessages();
						  if($unreadMessages) echo '<span class="pull-right"><small id="chat_unread_message">',$unreadMessages,'</small></span>';
						  ?>
						  <i class="fa fa-commenting-o"></i><?php echo $lang['MESSAGING']; ?>
					  </a>
					  <script>
					  var firstTime = true;
                      function displayNotificationIfEnabled(title, body){
                        var notificationEnabled = <?php $result = $conn->query("SELECT new_message_notification FROM socialprofile WHERE userID = $userID AND new_message_notification = 'TRUE'"); echo $result && $result->num_rows > 0?"true":"false" ?>;
                        if(notificationEnabled && window.Notification){
                            var unreadMessageNotification = new Notification(title,{
                                body: body,
                                requireInteraction: true
                            })
                        }
                      }
                      setInterval(function(){
						  $.ajax({
							  url: 'ajaxQuery/AJAX_db_utility.php',
							  type: 'POST',
							  data: {function: 'getUnreadMessages', userid: <?php echo $userID; ?> },
							  success: function (response) {
								  $('#chat_unread_message').html(response);
								  if(response){ //annoy
									  showInfo('Sie haben eine neue Nachricht erhalten. Sie finden Sie in Ihrer Post.',110000);
									  var audioElement = document.createElement('audio');
									  audioElement.setAttribute('src', 'http://www.soundjay.com/misc/sounds/bell-ringing-04.mp3');
									  audioElement.play();
								  }
								  if(response && firstTime){
                                      displayNotificationIfEnabled("Sie haben eine neue Nachricht erhalten","Sie finden Sie in Ihrer Post")
                                      firstTime = false;
									  var newTitle = 'Ungelese Nachricht';
									  setInterval(function(){
										  var oldTitle = $(document).find('title').text();
										  document.title = newTitle;
										  newTitle = oldTitle;
									  }, 1000);
								  }
							  }
						  });
					  }, 120000); //120 seconds, should not cause request overflow
					  </script>
                  </li>
				  <?php
				  $result = $conn->query("SELECT projectID FROM relationship_project_user WHERE userID = $userID AND (expirationDate = '0000-00-00' OR DATE(expirationDate) > CURRENT_TIMESTAMP )");
				  echo $conn->error;
				  if($result && $result->num_rows > 0){
					  echo '<li><a href="../project/public"';
					  if ($this_page == 'project_public.php') {echo $setActiveLink;}
					  echo '><span class="pull-right"><small>'.$result->num_rows.'</small></span><i class="fa fa-tags"></i>'.$lang['PROJECTS'].'</a></li>';
				  }
				  ?>

                  <!-- User-Section: BOOKING -->
                  <?php if ($user_roles['canBook'] == 'TRUE' && $showProjectBookingLink): ?>
                      <li><a <?php if ($this_page == 'userProjecting.php') {echo $setActiveLink;}?> href="../user/book"><i class="fa fa-bookmark"></i><span> <?php echo $lang['BOOK_PROJECTS']; ?></span></a></li>
                  <?php endif;?>
                  <?php
                  $result = $conn->query("SELECT DISTINCT d.projectid FROM dynamicprojects d
					  LEFT JOIN dynamicprojectsemployees de ON de.projectid = d.projectid AND de.userid = $userID AND de.position != 'owner'
                      LEFT JOIN dynamicprojectsteams ON dynamicprojectsteams.projectid = d.projectid
					  LEFT JOIN relationship_team_user rtu ON rtu.teamID = dynamicprojectsteams.teamid AND rtu.userID = $userID
                      WHERE d.isTemplate = 'FALSE' AND d.companyid IN (0, ".implode(', ', $available_companies).")
					  AND d.projectstatus = 'ACTIVE' AND (de.userid IS NOT NULL OR rtu.userID IS NOT NULL)");
                      if (($result && $result->num_rows > 0) || $userHasSurveys || $user_roles['isDynamicProjectsAdmin'] || $user_roles['canCreateTasks']): ?>
                      <li><a <?php if ($this_page == 'dynamicProjects.php') {echo $setActiveLink;}?> href="../dynamic-projects/view">
						  <?php  echo $conn->error; if($result->num_rows > 0) echo '<span class="pull-right"><small>'.$result->num_rows.'</small></span>'; ?>
                          <i class="fa fa-tasks"></i><?php echo $lang['DYNAMIC_PROJECTS']; ?>
                          <span title="Unread messages" id="projectMessagingBadge" class="badge pull-right" style="display: none; margin-right: 15px;"></span> <!-- separate badge for unread messages -->
                      </a></li>
                  <?php endif; ?>
              <?php endif; //endif(canStamp) ?>
            <?php if ($user_roles['canUseClients'] == 'TRUE' || $user_roles['canEditClients'] == 'TRUE' || $user_roles['canUseSuppliers'] == 'TRUE' || $user_roles['canEditSuppliers'] == 'TRUE'): ?>
            <li><a <?php if ($this_page == 'editCustomers.php') {echo $setActiveLink; }?> href="../system/clients"><i class="fa fa-file-text-o"></i><span><?php echo $lang['ADDRESS_BOOK']; ?></span></a></li>
            <?php endif;//canuseClients ?>
          </ul>
      </div>
    <div class="panel-group" id="sidebar-accordion">
      <!-- Section One: CORE -->
      <?php if (has_permission("READ", "CORE")): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-core"  id="adminOption_CORE"><i class="fa fa-caret-down pull-right"></i>
                <i class="fa fa-gear"></i> <?php echo $lang['ADMIN_CORE_OPTIONS']; ?>
            </a>
          </div>
          <div id="collapse-core" role="tabpanel" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
              <?php if(has_permission("READ","CORE","SECURITY")): ?><li><a <?php if ($this_page == 'securitySettings.php') {echo $setActiveLink;}?> href="../system/security">Security</a></li><? endif ?>
                  <li>
                      <a id="coreUserToggle" href="#" data-toggle="collapse" data-target="#toggleUsers" data-parent="#sidenav01" class="collapse in">
                          <span><?php echo $lang['USERS']; ?></span> <i class="fa fa-caret-down"></i>
                      </a>
                      <div class="collapse" id="toggleUsers" style="height: 0px;">
                          <ul class="nav nav-list">
                              <li><a <?php if ($this_page == 'editUsers.php') {echo $setActiveLink;}?> href="../system/users"><?php echo $lang['EDIT_USERS']; ?></a></li>
                              <li><a <?php if ($this_page == 'admin_saldoview.php') {echo $setActiveLink;}?> href="../system/saldo"><?php echo $lang['USERS']; ?> Saldo</a></li>
                              <li><a <?php if ($this_page == 'register.php') {echo $setActiveLink;}?> href="../system/register"><?php echo $lang['REGISTER']; ?></a></li>
                              <li><a <?php if ($this_page == 'deactivatedUsers.php') {echo $setActiveLink;}?> href="../system/deactivated"><?php echo $lang['USER_INACTIVE']; ?></a></li>
                              <li><a <?php if ($this_page == 'checkinLogs.php') {echo $setActiveLink;}?> href="../system/checkinLogs">Checkin Logs</a></li>
                          </ul>
                      </div>
                  </li>
                <li>
                    <a id="coreCompanyToggle" <?php if ($this_page == 'editCompanies.php') {echo $setActiveLink;}?> href="#" data-toggle="collapse" data-target="#toggleCompany" data-parent="#sidenav01" class="collapse in">
                        <span><?php echo $lang['COMPANIES']; ?></span> <i class="fa fa-caret-down"></i>
                    </a>
                    <div class="collapse" id="toggleCompany" style="height: 0px;">
                        <ul class="nav nav-list">
                            <?php
                            $result = $conn->query("SELECT * FROM $companyTable");
                            while ($result && ($row = $result->fetch_assoc())) {
                                if (in_array($row['id'], $available_companies)) {
                                    echo "<li><a href='../system/company?cmp=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
                                }
                            }
                            ?>
                            <li><a <?php if ($this_page == 'new_Companies.php') {echo $setActiveLink;}?> href="../system/new"><?php echo $lang['CREATE_NEW_COMPANY']; ?></a></li>
                        </ul>
                    </div>
                </li>
                <li><a <?php if ($this_page == 'teamConfig.php') {echo $setActiveLink;}?> href="../system/teams">Teams</a></li>
                <li>
                    <a id="coreSettingsToggle" href="#" data-toggle="collapse" data-target="#toggleSettings" data-parent="#sidenav01" class="collapsed">
                        <span><?php echo $lang['SETTINGS']; ?></span> <i class="fa fa-caret-down"></i>
                    </a>
                    <div class="collapse" id="toggleSettings" style="height: 0px;">
                        <ul class="nav nav-list">
                            <li><a <?php if ($this_page == 'editHolidays.php') {echo $setActiveLink;}?> href="../system/holidays"><span><?php echo $lang['HOLIDAYS']; ?></span></a></li>
                            <li><a <?php if ($this_page == 'options_advanced.php') {echo $setActiveLink;}?> href="../system/advanced"><span><?php echo $lang['ADVANCED_OPTIONS']; ?></span></a></li>
                            <li><a <?php if ($this_page == 'options_password.php') {echo $setActiveLink;}?> href="../system/password"><span><?php echo $lang['PASSWORD'] . ' ' . $lang['OPTIONS']; ?></span></a></li>
                            <li><a <?php if ($this_page == 'options_report.php') {echo $setActiveLink;}?> href="../system/email"><span> E-mail <?php echo $lang['OPTIONS']; ?> </span></a></li>
                            <li><a <?php if ($this_page == 'options_archive.php') {echo $setActiveLink;}?> href="../system/archive"><span><?php echo $lang['ARCHIVE'] . ' ' . $lang['OPTIONS'] ?></span></a></li>
                            <li><a <?php if ($this_page == 'taskScheduler.php') {echo $setActiveLink;}?> href="../system/tasks"><span><?php echo $lang['TASK_SCHEDULER']; ?> </span></a></li>
                            <li><a <?php if ($this_page == 'download_sql.php') {echo $setActiveLink;}?> href="../system/backup"><span> DB Backup</span></a></li>
                            <li><a <?php if ($this_page == 'templateSelect.php') {echo $setActiveLink;}?> href="../report/designer"><span>Report Designer</span> </a></li>
                            <?php if (!getenv('IS_CONTAINER') && !isset($_SERVER['IS_CONTAINER'])): ?>
                                <li><a <?php if ($this_page == 'upload_database.php') {echo $setActiveLink;}?> href="../system/restore"><span> <?php echo $lang['DB_RESTORE']; ?></span> </a></li>
                                <li><a <?php if ($this_page == 'pullGitRepo.php') {echo $setActiveLink;}?> href="../system/update"><span>Git Update</span></a></li>
                            <?php endif;?>
                            <li><a <?php if ($this_page == 'resticBackup.php') {echo $setActiveLink;}?> href="../system/restic"><span> Restic Backup</span></a></li>
							<li><a <?php if($this_page == 'options_tags.php') {echo $setActiveLink;}?> href="../system/tags"><span> Tags</span></a> </li>
                        </ul>
                    </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if($this_page == "editUsers.php" || $this_page == "admin_saldoview.php" || $this_page == "register.php" || $this_page == "deactivatedUsers.php" || $this_page == "checkinLogs.php"){
          echo "<script>document.getElementById('coreUserToggle').click();document.getElementById('adminOption_CORE').click();</script>";
        } elseif($this_page == "options_report.php" || $this_page == "editHolidays.php" || $this_page == "options_advanced.php" || $this_page == "taskScheduler.php"
        || $this_page == "pullGitRepo.php" || $this_page == "options_password.php" || $this_page == 'options_archive.php' || $this_page == 'resticBackup.php' || $this_page == 'templateSelect.php' || $this_page == 'options_tags.php' ){
          echo "<script>document.getElementById('coreSettingsToggle').click();document.getElementById('adminOption_CORE').click();</script>";
        } elseif($this_page == "editCompanies.php" || $this_page == "new_Companies.php"){
          echo "<script>document.getElementById('coreCompanyToggle').click();document.getElementById('adminOption_CORE').click();</script>";
        } elseif($this_page == "download_sql.php" || $this_page == "teamConfig.php" || $this_page == "upload_database.php" || $this_page == "securitySettings.php") {
          echo "<script>document.getElementById('adminOption_CORE').click();</script>";
        }
        ?>
      <?php endif; ?>

      <!-- Section Two: TIME -->
      <?php if ($user_roles['isTimeAdmin'] == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-time"  id="adminOption_TIME"><i class="fa fa-caret-down pull-right"></i>
            <i class="fa fa-history"></i> <?php echo $lang['ADMIN_TIME_OPTIONS']; ?>
            </a>
          </div>
          <div id="collapse-time" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li><a <?php if ($this_page == 'getTimeprojects.php') {echo $setActiveLink;}?> href="../time/view"> <span><?php echo $lang['TIMES'] . ' ' . $lang['VIEW']; ?></span></a></li>
                <li><a <?php if ($this_page == 'bookAdjustments.php') {echo $setActiveLink;}?> href="../time/corrections"><?php echo $lang['CORRECTION']; ?></a></li>
                <li><a <?php if ($this_page == 'getTravellingExpenses.php') {echo $setActiveLink;}?> href="../time/travels"><?php echo $lang['TRAVEL_FORM']; ?></a></li>
                <li><a <?php if ($this_page == 'display_vacation.php') {echo $setActiveLink;}?> href="../time/vacations"><?php echo $lang['VACATION']; ?></a></li>
                <li><a <?php if ($this_page == 'adminTodos.php') {echo $setActiveLink;}?> href="../time/check"><?php echo $lang['CHECKLIST']; ?></a></li>
              </ul>
            </div>
          </div>
        </div>
        <?php if ($this_page == "getTimeprojects.php" || $this_page == "monthlyReport.php" || $this_page == "adminTodos.php" || $this_page == "getTravellingExpenses.php" || $this_page == "bookAdjustments.php" || $this_page == "getTimestamps_select.php" || $this_page == 'display_vacation.php') {
            echo "<script>document.getElementById('adminOption_TIME').click();</script>";
        } ?>
      <?php endif;?>

      <!-- Section Three: PROJECTS -->
      <?php if ($user_roles['isProjectAdmin'] == 'TRUE' || $user_roles['canUseWorkflow'] == 'TRUE'): ?>
          <div class="panel panel-default panel-borderless">
              <div class="panel-heading" role="tab">
                  <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-project"  id="adminOption_PROJECT"><i class="fa fa-caret-down pull-right"></i>
                      <i class="fa fa-tags"></i><?php echo $lang['PROJECTS']; ?>
                  </a>
              </div>
              <div id="collapse-project" class="panel-collapse collapse" role="tabpanel">
                  <div class="panel-body">
                      <ul class="nav navbar-nav">
                          <?php if ($user_roles['isProjectAdmin'] == 'TRUE'): ?>
                              <li><a <?php if ($this_page == 'project_public.php') {echo $setActiveLink;}?> href="../project/view"><span><?php echo $lang['PROJECTS']; ?></span></a></li>
                              <li><a <?php if ($this_page == 'audit_projectBookings.php') {echo $setActiveLink;}?> href="../project/log"><span><?php echo $lang['PROJECT_LOGS']; ?></span></a></li>
                          <?php endif; ?>
                          <li><a <?php if ($this_page == 'options.php') {echo $setActiveLink;}?> href="../project/options"><span>Workflow</span></a></li>
                      </ul>
                  </div>
              </div>
          </div>
          <?php if ($this_page == 'project_detail.php' || $this_page == "audit_projectBookings.php" || $this_page == "options.php") {
              echo "<script>$('#adminOption_PROJECT').click();</script>";
          } ?>
      <?php endif; ?>

      <!-- Section Four: REPORTS    !! REMOVED 5aa0dafd9fbf0 !! -->

      <!-- Section Five: ERP -->
      <?php if (has_permission("READ", "ERP")): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-erp"  id="adminOption_ERP"><i class="fa fa-caret-down pull-right"></i><i class="fa fa-file-text-o"></i> ERP</a>
          </div>
          <div id="collapse-erp" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li><a <?php if ($this_page == 'offer_proposal_edit.php') {echo $setActiveLink;}?> href="../erp/view"><span><?php echo $lang['PROCESS']; ?></span></a></li>
                <li><a id="erpClients" href="#" data-toggle="collapse" data-target="#toggleERPClients" data-parent="#sidenav01" class="collapsed">
                    <span><?php echo $lang['CLIENTS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleERPClients">
                      <ul class="nav nav-list">
                          <li><a <?php if (isset($_GET['t']) && $_GET['t'] == 'ang') { echo $setActiveLink; } ?> href="../erp/view?t=ang"><span><?php echo $lang['PROPOSAL_TOSTRING']['ANG']; ?></span></a></li>
                          <li><a href="../erp/view?t=aub"><span><?php echo $lang['PROPOSAL_TOSTRING']['AUB']; ?></span></a></li>
                          <li><a href="../erp/view?t=re"><span><?php echo $lang['PROPOSAL_TOSTRING']['RE']; ?></span></a></li>
                          <li><a href="../erp/view?t=lfs"><span><?php echo $lang['PROPOSAL_TOSTRING']['LFS']; ?></span></a></li>
                          <li><a href="../system/clients"><span><?php echo $lang['ADDRESS_BOOK']; ?></span></a></li>
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
                      <li><a href="../system/clients"><span><?php echo $lang['ADDRESS_BOOK']; ?></span></a></li>
                    </ul>
                  </div>
                </li>
                <li>
                    <a id="articleToggle" <?php if($this_page =='product_articles.php'){echo $setActiveLink;}?> href="#" data-toggle="collapse" data-target="#toggleArticle" data-parent="#sidenav01" class="collapse in">
                        <span><?php echo $lang['ARTICLE']; ?></span> <i class="fa fa-caret-down"></i>
                    </a>
                    <div class="collapse" id="toggleArticle" style="height: 0px;">
                        <ul class="nav nav-list">
                            <?php
                            $result = $conn->query("SELECT * FROM $companyTable");
                            while ($result && ($row = $result->fetch_assoc())) {
                                if (in_array($row['id'], $available_companies)) {
                                    echo "<li><a href='../erp/articles?cmp=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </li>
                <li><a <?php if($this_page =='receiptBook.php'){echo $setActiveLink;}?> href="../erp/receipts"><span><?php echo $lang['RECEIPT_BOOK']; ?></span></a></li>
                <li><a disabled href=""><span><?php echo $lang['VACANT_POSITIONS']; ?></span></a></li>

                <li><a id="erpSettings" href="#" data-toggle="collapse" data-target="#toggleERPSettings" data-parent="#sidenav01" class="collapsed">
                    <span><?php echo $lang['SETTINGS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleERPSettings">
                    <ul class="nav nav-list">
                      <li><a <?php if ($this_page == 'editTaxes.php') {echo $setActiveLink;}?> href="../erp/taxes"><span><?php echo $lang['TAX_RATES']; ?></span></a></li>
                      <li><a <?php if ($this_page == 'editUnits.php') {echo $setActiveLink;}?> href="../erp/units"><span><?php echo $lang['UNITS']; ?></span></a></li>
                      <li><a <?php if ($this_page == 'editPaymentMethods.php') {echo $setActiveLink;}?> href="../erp/payment"><span><?php echo $lang['PAYMENT_METHODS']; ?></span></a></li>
                      <li><a <?php if ($this_page == 'editShippingMethods.php') {echo $setActiveLink;}?> href="../erp/shipping"><span><?php echo $lang['SHIPPING_METHODS']; ?></span></a></li>
                      <li><a <?php if ($this_page == 'editRepres.php') {echo $setActiveLink;}?> href="../erp/representatives"><span><?php echo $lang['REPRESENTATIVE']; ?></span></a></li>
                    </ul>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if ($this_page == "erp_view.php" || $this_page == "erp_process.php") {
            echo "<script>$('#adminOption_ERP').click();$('#erpClients').click();</script>";
        } elseif ($this_page == "editTaxes.php" || $this_page == "editUnits.php" || $this_page == "editPaymentMethods.php" || $this_page == "editShippingMethods.php" || $this_page == "editRepres.php") {
            echo "<script>$('#adminOption_ERP').click();$('#erpSettings').click();</script>";
        } elseif ($this_page == "product_articles.php" || $this_page == "receiptBook.php") {
            echo "<script>document.getElementById('articleToggle').click();$('#adminOption_ERP').click();</script>";
        }
        ?>
      <?php endif;?>
      <!-- Section Six: FINANCES -->
      <?php if ($user_roles['isFinanceAdmin'] == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-finance"  id="adminOption_FINANCE"><i class="fa fa-caret-down pull-right"></i><i class="fa fa-book"></i><?php echo $lang['FINANCES']; ?></a>
          </div>
          <div id="collapse-finance" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <?php
                if(count($available_companies) == 2){
                    echo '<li><a href="../finance/plan?n='.$available_companies[1].'">'.$lang['ACCOUNT_PLAN'].'</a></li>';
                    echo '<li><a href="../finance/journal?n='.$available_companies[1].'">'.$lang['ACCOUNT_JOURNAL'].'</a></li>';
                    $acc_res = $conn->query("SELECT id, name, companyID FROM accounts WHERE manualBooking='TRUE' AND companyID = ".$available_companies[1]);
                    while($acc_res && ($acc_row = $acc_res->fetch_assoc())){
                        echo '<li><a href="../finance/account?v='.$acc_row['id'].'">'.$acc_row['name'].'</a></li>';
                        if($this_page == 'accounting.php' && !empty($_GET['v']) && $_GET['v'] == $acc_row['id']){
                            echo "<script>$('#finance-click-".$acc_row['companyID']."').click();</script>";
                        }
                    }
                } else {
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
                }
                ?>
                <li><a <?php if($this_page == 'editTaxes.php'){echo $setActiveLink;}?> href="../erp/taxes"><span><?php echo $lang['TAX_RATES']; ?></span></a></li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if ($this_page == "accounting.php" || $this_page == 'accountPlan.php' || $this_page == 'accountJournal.php') {
            echo "<script>$('#adminOption_FINANCE').click();";
            if (isset($_GET['n'])) {
                echo "$('#finance-click-" . $_GET['n'] . "').click();";
            }
            echo '</script>';
        }
        ?>
      <?php endif;?>
      <!-- Section Six: DSGVO -->
      <?php if (has_permission("READ", "DSGVO") /* any read (or write) permission in dsgvo */): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-dsgvo"  id="adminOption_DSGVO"><i class="fa fa-caret-down pull-right"></i><strong style="padding: 0px 6px;"> § </strong>DSGVO</a>
          </div>
          <div id="collapse-dsgvo" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <?php
                if(count($available_companies) == 2){
                  $isActivePanel = true;
                  $isActive = ($isActivePanel && $this_page == 'dsgvo_view.php') ? $setActiveLink : "";
                  if(has_permission("READ","DSGVO","AGREEMENTS")) echo '<li><a '.$isActive.' href="../dsgvo/documents?n='.$available_companies[1].'">'.$lang['DOCUMENTS'].'</a></li>';
                  $isActive = ($isActivePanel && ($this_page == 'dsgvo_vv.php' || $this_page == "dsgvo_edit.php" || $this_page == "dsgvo_vv_detail.php" || $this_page == "dsgvo_vv_templates.php" || $this_page == "dsgvo_vv_template_edit.php" || $this_page == 'dsgvo_data_matrix.php')) ? $setActiveLink : "";
                  if(has_permission("READ","DSGVO","PROCEDURE_DIRECTORY")) echo '<li><a '.$isActive.' href="../dsgvo/vv?n='.$available_companies[1].'" >'.$lang['PROCEDURE_DIRECTORY'].'</a></li>';
                  $isActive = ($isActivePanel && $this_page == 'dsgvo_mail.php') ? $setActiveLink : "";
                  if(has_permission("READ","DSGVO","EMAIL_TEMPLATES")) echo '<li><a '.$isActive.' href="../dsgvo/templates?n='.$available_companies[1].'">' .$lang['EMAIL_TEMPLATES']. '</a></li>';
                  $isActive = ($isActivePanel && $this_page == 'dsgvo_training.php') ? $setActiveLink : "";
                  if(has_permission("READ","DSGVO","TRAINING")) echo '<li><a '.$isActive.' href="../dsgvo/training?n='.$available_companies[1].'" >' .$lang["TRAINING"]. '</a></li>';
                  $isActive = ($isActivePanel && $this_page == 'dsgvo_log.php') ? $setActiveLink : "";
                  if(has_permission("READ","DSGVO","LOGS")) echo '<li><a '.$isActive.' href="../dsgvo/log?n='.$row['id'].'" >Logs</a></li>';
                } else {
                  $result = $conn->query("SELECT id, name FROM $companyTable WHERE id IN (".implode(', ', $available_companies).")");
                  while($result && ($row = $result->fetch_assoc())){
                    $isActivePanel = isset($_GET['n']) && ($row['id'] ==  $_GET['n']); //only highlight the current page in the tab for the current company
                    echo '<li>';
                    echo '<a href="#" data-toggle="collapse" data-target="#tdsgvo-'.$row['id'].'" data-parent="#sidenav01" class="collapsed">'.$row['name'].' <i class="fa fa-caret-down"></i></a>';
                    echo '<div class="collapse" id="tdsgvo-'.$row['id'].'" >';
                    echo '<ul class="nav nav-list">';
                    $isActive = ($isActivePanel && $this_page == 'dsgvo_view.php') ? $setActiveLink : "";
                    if(has_permission("READ","DSGVO","AGREEMENTS")) echo '<li><a '.$isActive.' href="../dsgvo/documents?n='.$row['id'].'">'.$lang['DOCUMENTS'].'</a></li>';
                    $isActive = ($isActivePanel && ($this_page == 'dsgvo_vv.php' || $this_page == "dsgvo_edit.php" || $this_page == "dsgvo_vv_detail.php" || $this_page == "dsgvo_vv_templates.php" || $this_page == "dsgvo_vv_template_edit.php" || $this_page == 'dsgvo_data_matrix.php')) ? $setActiveLink : "";
                    if(has_permission("READ","DSGVO","PROCEDURE_DIRECTORY")) echo '<li><a '.$isActive.' href="../dsgvo/vv?n='.$row['id'].'" >'.$lang['PROCEDURE_DIRECTORY'].'</a></li>';
                    $isActive = ($isActivePanel && $this_page == 'dsgvo_mail.php') ? $setActiveLink : "";
                    if(has_permission("READ","DSGVO","EMAIL_TEMPLATES")) echo '<li><a '.$isActive.' href="../dsgvo/templates?n='.$row['id'].'">' .$lang['EMAIL_TEMPLATES']. '</a></li>';
                    $isActive = ($isActivePanel && $this_page == 'dsgvo_training.php') ? $setActiveLink : "";
                    if(has_permission("READ","DSGVO","TRAINING")) echo '<li><a '.$isActive.' href="../dsgvo/training?n='.$row['id'].'" >' .$lang['TRAINING']. '</a></li>';
                    $isActive = ($isActivePanel && $this_page == 'dsgvo_log.php') ? $setActiveLink : "";
                    if(has_permission("READ","DSGVO","LOGS")) echo '<li><a '.$isActive.' href="../dsgvo/log?n='.$row['id'].'" >Logs</a></li>';
                    echo '</ul></div></li>';
                  }
                }
                ?>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if ($this_page == "dsgvo_view.php" || $this_page == "dsgvo_edit.php" || $this_page == "dsgvo_mail.php" || $this_page == "dsgvo_vv.php"
            || $this_page == "dsgvo_vv_detail.php" || $this_page == "dsgvo_vv_templates.php" || $this_page == "dsgvo_vv_template_edit.php"
            || $this_page == "dsgvo_training.php" || $this_page == "dsgvo_log.php" || $this_page == 'dsgvo_data_matrix.php') {
            echo "<script>$('#adminOption_DSGVO').click();";
            if (isset($_GET['n'])) {
                echo "$('#tdsgvo-" . $_GET['n'] . "').toggle();";
            }
            echo '</script>';
        }
        ?>
      <?php endif;?>
      <!-- Section Seven: ARCHIVE -->
      <?php if ($user_roles['canUseArchive'] == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-archives"  id="adminOption_ARCHIVE"><i class="fa fa-caret-down pull-right"></i><i class="fa fa-folder-open-o"></i><?php echo $lang['ARCHIVE'] ?></a>
          </div>
          <div id="collapse-archives" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li><a <?php if ($this_page == 'archive_share.php') {echo $setActiveLink;}?> href="../archive/share" data-parent="#sidenav01" class="collapsed"><?php echo $lang['SHARE'] ?></a></li>
                <li><a <?php if ($this_page == 'private_view.php') {echo $setActiveLink;}?> href="../archive/private" data-parent="#sidenav01" class="collapsed"><?php echo $lang['PRIVATE'] ?></a></li>
              </ul>
            </div>
          </div>
        </div>
      <?php
        if ($this_page == "archive_share.php" || $this_page == "private_view.php") {
            echo "<script>$('#adminOption_ARCHIVE').click();</script>";
        }
        ?>
      <?php endif;?>
      <!-- END SECTIONS -->
      <br><br>
    </div> <!-- /accordions -->
    <br><br><br>
  </div>
</div>
<!-- /side menu -->

 <script>
 //5b3f2f5618fd2
 //note: this will never return false. it will keep the session alive unless user logged out manually or closed his browser (look up usage of session_start())
     // notify the user of a session timeout
    sessionIsAlive = true;
    sessionAliveInterval = setInterval(function(){
        $.ajax({
            url: 'ajaxQuery/AJAX_db_utility.php',
            type: 'POST',
            data: {function: 'isSessionAlive'},
            success: function (response) {
                if(response == "false"){
                    showInfo('Your session expired. <a href="../login/auth">Login</a>',1000*60*60*24 /* 1 day should be enough */);
                    sessionIsAlive = false;
                    clearInterval(sessionAliveInterval)
                }
            }
        });
    }, 1000*200); // 200 seconds, should not cause request overflow
</script>

<div id="bodyContent" style="display:none;" >
  <div class="affix-content">
    <div class="container-fluid">
      <span><?php echo $validation_output; ?></span>
      <span><?php echo $error_output; ?></span>

      <?php
      $result = $conn->query("SELECT expiration, expirationDuration, expirationType FROM policyData"); echo $conn->error;
      $row = $result->fetch_assoc();
      if($row['expiration'] == 'TRUE' || $userdata['forcedPwdChange'] == 1){ //can a password expire?
          $pswDate = date('Y-m-d', strtotime("+".$row['expirationDuration']." months", strtotime($userdata['lastPswChange'])));
          if(timeDiff_Hours($pswDate, getCurrentTimestamp()) > 0 || $userdata['forcedPwdChange'] == 1){ //has my password actually expired?
              showError('<strong>Your Password has expired. </strong> Please change it by clicking on the gears in the top right corner.');
              if($row['expirationType'] == 'FORCE' || $userdata['forcedPwdChange'] == 1){ //force the change
                  include 'footer.php';
                  die();
              }
          }
      }
      $user_agent = $_SERVER["HTTP_USER_AGENT"];
      if (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7') || strpos($user_agent, 'Edge')) {
          showError('Der Browser den Sie verwenden ist veraltet oder unterstützt wichtige Funktionen nicht. Wenn Sie Probleme mit der Anzeige oder beim Interagieren bekommen, versuchen sie einen anderen Browser.');
      }
      ?>
