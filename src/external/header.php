<?php
session_start();
if (empty($_SESSION['external_id'])) {
    die('Please <a href="login">login</a> first.');
}
$userID = $_SESSION['external_id'];
$timeToUTC = $_SESSION['external_timeToUTC'];
$privateKey = $_SESSION['external_private'];

require dirname(__DIR__) . DIRECTORY_SEPARATOR.'connection.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR.'language.php';

$result = $conn->query("SELECT publicKey, lastPswChange FROM external_users WHERE id = $userID");
if ($result) {
    $external_data = $result->fetch_assoc();
} else {
    die($conn->error);
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

    <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
    <link href="plugins/homeMenu/homeMenu_metro.css" rel="stylesheet" />
</head>
<body>
<!-- navbar -->
<nav id="fixed-navbar-header" class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header hidden-xs">
            <a class="navbar-brand" href="home" style="width:230px;">CONNECT <span style="font-size:9pt">(Beta)</span></a>
        </div>
        <div class="navbar-right" style="margin-right:10px;">
            <a class="btn navbar-btn navbar-link hidden-xs" data-toggle="modal" data-target="#infoDiv_collapse"><i class="fa fa-info"></i></a>
            <a class="btn navbar-btn navbar-link" id="header-gears" data-toggle="modal" data-target="#passwordModal"><i class="fa fa-gears"></i></a>
            <a class="btn navbar-btn navbar-link" href="login" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </div>
</nav>
<!-- /navbar -->
<?php
if(isset($_POST['savePAS']) && !empty($_POST['passwordCurrent']) && !empty($_POST['password']) && !empty($_POST['passwordConfirm']) && crypt($_POST['passwordCurrent'], $external_data['login_pw']) == $external_data['login_pw']){
    $password = $_POST['password'];
    $output = '';
    if(strcmp($password, $_POST['passwordConfirm']) == 0 && match_passwordpolicy($password, $output)){
        $userPasswordHash = password_hash($password, PASSWORD_BCRYPT);
        $private_encrypted = simple_encryption($privateKey, $password);
        $conn->query("UPDATE external_users SET login_pw = '$userPasswordHash', lastPswChange = UTC_TIMESTAMP, privatePGPKey = '$private_encrypted' WHERE id = '$userID';");
        if(!$conn->error){
            $validation_output  = '<div class="alert alert-success fade in"><a href="#" class="close" data-dismiss="alert" >&times;</a><strong>Success! </strong>Password successfully changed. </div>';
        } else {
            $validation_output = '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        }
    } else {
        $validation_output = '<div class="alert alert-danger fade in"><a href="" class="close" data-dismiss="alert" >&times;</a>'.$output.'</div>';
    }
}
?>
<!-- modal -->
<div id="infoDiv_collapse" class="modal fade">
    <div class="modal-dialog modal-content modal-sm">
        <div class="modal-header h4">Information</div>
        <div class="modal-body">
            <a target="_blank" href='http://www.eitea.at'> EI-TEA Partner GmbH </a><br><br>
            The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
            the Software will perform substantially in accordance with the functional specifications set forth in the documentation. The software is provided "as is", without warranty of any kind, express or implied.
            <br><br>
            LIZENZHINWEIS<br>
            Composer: aws, cssToInlineStyles, csvParser, dompdf, mysqldump, phpmailer, hackzilla, http-message; Other: bootstrap, charts.js, dataTables, datetimepicker, font-awesome, fpdf, fullCalendar, imap-client, jquery, jsCookie, maskEdit, select2, tinyMCE, restic, rtf.js
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['BACK'] ?></button>
        </div>
    </div>
</div>

<div class="modal fade" id="passwordModal">
    <div class="modal-dialog modal-content modal-md">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title"><?php echo $lang['SETTINGS']; ?></h4>
        </div>
        <div class="modal-body">
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#myModalPassword">Passwort</a></li>
                <li><a id="myModalPGPtab" data-toggle="tab" href="#myModalPGP">Security</a></li>
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
                <div id="myModalPGP" class="tab-pane fade"><br>
                    <form method="POST">
                        <div class="col-md-12">
                            <label>Public Key</label>
                            <br><?php echo $external_data['publicKey']; ?><br><br>
                        </div>
                        <div class="col-md-12">
                            <?php if(!empty($_POST['unlockKeyDownload']) && crypt($_POST['unlockKeyDownload'], $external_data['login_pw']) == $external_data['login_pw']): ?>
                                <label>Keypair download</label>
                                <input type="hidden" name="personal" value="<?php echo $privateKey."\n".$external_data['publicKey']; ?>" /><br>
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
            </div>
        </div>
    </div>
</div>
