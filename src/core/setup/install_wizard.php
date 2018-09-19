<?php
if (empty($_SESSION['userid'])) {
    die('Please <a href="../login/auth">login</a> first.');
}
$userID = $_SESSION['userid'];

require dirname(dirname(__DIR__)) . '/utilities.php';
require dirname(dirname(__DIR__)) . '/connection.php';

$firstTimeWizard = false;
$result = $conn->query("SELECT firstTimeWizard FROM configurationData WHERE firstTimeWizard = 'TRUE'");
if ($result && $result->num_rows > 0) {
	session_destroy();
    redirect('../user/logout');
    $firstTimeWizard = true;  //safety check
}

if(!$firstTimeWizard && $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['accept_licence'])){
	if(getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])){
		$conn->query("UPDATE mailingOptions SET host = 'adminmail', port = 25, username = '', password = '', smtpSecure = '',
		sender = 'noreply@eitea.at', sendername = 'Connect', isDefault = 1");
	}
    if(!empty($_POST['encryption_pass']) && !empty($_POST['encryption_pass_confirm']) && $_POST['encryption_pass'] == $_POST['encryption_pass_confirm']){
        $result = $conn->query("SELECT firstname, lastname, email FROM UserData WHERE id = $userID LIMIT 1");
        if($result && ($row = $result->fetch_assoc())){
			$conn->query("UPDATE security_users SET outDated = 'TRUE'");
            $accept = true;
            $err = $content_personal = $content_company = '';
            //user PAIR
            $keyPair = sodium_crypto_box_keypair();
            $private = base64_encode(sodium_crypto_box_secretkey($keyPair));
            $admin_public = sodium_crypto_box_publickey($keyPair);
            $hash = password_hash($_POST['encryption_pass'], PASSWORD_BCRYPT);
            $content_personal = $private." \n".base64_encode($admin_public);
            $private_encrypt = simple_encryption($private, $_POST['encryption_pass']);
            $_SESSION['privateKey'] = $private;
			$_SESSION['publicKey'] = base64_encode($admin_public);
            $conn->query("UPDATE UserData SET psw = '$hash' WHERE id = $userID");
			$conn->query("INSERT INTO security_users (userID, publicKey, privateKey) VALUES ($userID, '".base64_encode($admin_public)."', '".$private_encrypt."')");
            if($conn->error) $accept = false;

            //company PAIR
            $result = $conn->query("SELECT id FROM companyData LIMIT 1");
            if($accept && $result && ($row = $result->fetch_assoc())){
                $keyPair = sodium_crypto_box_keypair();
                $private = sodium_crypto_box_secretkey($keyPair);
                $public = sodium_crypto_box_publickey($keyPair);
                $content_company = base64_encode($private)." \n".base64_encode($public);
				$symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
				$nonce = random_bytes(24);
				$symmetric_encrypted = $nonce . sodium_crypto_box($symmetric, $nonce, $private.$public);
                $conn->query("INSERT INTO security_company (companyID, publicKey, symmetricKey) VALUES (".$row['id'].", '".base64_encode($public)."', '".base64_encode($symmetric_encrypted)."') ");
                $nonce = random_bytes(24);
                $private_encrypt = $nonce . sodium_crypto_box($private, $nonce, $private.$admin_public);
                $conn->query("INSERT INTO security_access(userID, module, optionalID, privateKey) VALUES ($userID, 'COMPANY',".$row['id']." , '".base64_encode($private_encrypt)."')"); //5ae9e3e1e84e5
                if($conn->error) $accept = false;
            } else {
                $err .= $conn->error;
            }

            if($accept){ //module and access
                $modules = ['TIMES', 'PROJECTS', 'REPORTS', 'ERP', 'FINANCES', 'DSGVO', 'ARCHIVE'];
                foreach($modules as $module){
                    $keyPair = sodium_crypto_box_keypair();
                    $private = sodium_crypto_box_secretkey($keyPair);
                    $public = sodium_crypto_box_publickey($keyPair);
                    $symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
                    $nonce = random_bytes(24);
                    $symmetric_encrypted = $nonce . sodium_crypto_box($symmetric, $nonce, $private.$public);
                    $conn->query("INSERT INTO security_modules(module, publicKey, symmetricKey) VALUES ('$module', '".base64_encode($public)."', '".base64_encode($symmetric_encrypted)."')");
                    if($conn->error){ $accept = false; $err .= $conn->error; }

                    $nonce = random_bytes(24);
                    $private_encrypt = $nonce . sodium_crypto_box($private, $nonce, $private.$admin_public);
                    $conn->query("INSERT INTO security_access(userID, module, privateKey) VALUES ($userID, '$module', '".base64_encode($private_encrypt)."')");
                    if($conn->error){ $accept = false; $err .= $conn->error; }
                }
            } else {
                $err .= $conn->error;
            }

            if($accept){
                $conn->query("UPDATE configurationData SET firstTimeWizard = 'TRUE', activeEncryption = 'TRUE'");
                $firstTimeWizard = true;
            } else {
                $err .= $conn->error;
            }
        } else {
            $err .= $conn->error;
        }
    }
} elseif(empty($_POST['accept_licence'])){
	$err = '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Um fortfahren zu können müssen die Lizenzbedingungen gelesen und akzeptiert werden.</div>';
}
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="max-age=600, must-revalidate">

    <script src="plugins/jQuery/jquery.min.js"></script>
    <link rel="stylesheet" href="plugins/font-awesome/css/font-awesome.min.css"/>

    <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="plugins/bootstrap/js/bootstrap.min.js"></script>

    <link rel="stylesheet" type="text/css" href="plugins/select2/css/select2.min.css">
    <script src='plugins/select2/js/select2.min.js'></script>

    <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
	<link href="plugins/homeMenu/homeMenu_metro.css" rel="stylesheet" />
    <title>Setup Connect</title>
</head>
<body>
    <nav id="fixed-navbar-header" class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header hidden-xs"><a class="navbar-brand" >Connect</a></div>
            <div class="navbar-right">
                <a class="btn navbar-btn navbar-link" data-toggle="collapse" href="#infoDiv_collapse"><strong>info</strong></a>
            </div>
        </div>
    </nav>
    <div class="collapse" id="infoDiv_collapse">
        <div class="well">
            <a href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php include dirname(dirname(__DIR__)).'/version_number.php'; echo $VERSION_TEXT; ?><br>
            The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
            the Software will perform substantially in accordance with the functional specifications set forth in the documentation. The software is provided "as is", without warranty of any kind, express or implied.
        </div>
    </div>
	<div id="sidemenu" class="affix-sidebar sidebar-nav">
	</div>
    <div class="affix-content">
		<div class="container">
	        <div class="page-header h3">Einstellungen</div>
	        <?php if(isset($accept) && $accept): ?>
	            <div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>Schlüssel wurden erstellt</div>
	            <form method="POST" target="_blank" action="keys">
	                <input type="hidden" name="personal" value="<?php echo $content_personal; ?>" >
	                <input type="hidden" name="company" value="<?php echo $content_company; ?>" >
	                <button type="submit" class="btn btn-warning">Schlüssel Herunterladen</button>
	                <br>
	                <small> Diese Datei beinhaltet Sicherheitskopien Ihrer Zugangschlüssel und sollte von Ihnen stets sicher verwahrt werden.<br>
	                Empfohlen wird eine ausgedruckte Version geschützt zu lagern und die digitale Datei dabei im Anschluss zu vernichten. <br>
	                Bei Verlust oder Betrug übernimmt der Provider keine Haftung. </small>
	            </form>
	        <?php elseif(isset($accept)):
	            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$err.'</div>';
	        endif; ?>
	        <?php if($firstTimeWizard): //the wizard has run successfully ?>
	            <div class="row text-right">
	                <a href="../user/home" class="btn btn-warning" >Weiter</a>
	            </div>
	        <?php else: ?>
	            Hallo!<br>
	            Ihre Connect Umgebung steht Ihnen in kürze bereit. Sie müssen nur noch ihr gewünschtes Login-Kennwort eingeben und können dann die Einstellungen überprüfen.<br>
	            Falls Sie hilfe benötigen, suchen sie einfach nach diesem Symbol <i class="fa fa-question-circle-o"></i> um mehr Informationen zu erhalten.<br>
	            Wir wünschen Ihnen viel Erfolg.<br>
	            <br><hr><br>
	            <form method="POST">
	                <div class="row">
	                    <div class="col-md-4">
	                        <label>Neues Passwort</label>
	                        <input type="password" name="encryption_pass" class="form-control" />
	                    </div>
	                    <div class="col-md-4">
	                        <label>Neues Passwort Bestätigen</label>
	                        <input type="password" name="encryption_pass_confirm" class="form-control" />
	                    </div>
	                </div>
	                <br><hr><br>
	                <h4>Verschlüsselung</h4>
	                <div class="row">
	                    <div class="col-md-4">
	                        <label><input type="checkbox" checked name="wizard_encryption" value="1"> Aktivieren</label>
	                    </div>
	                </div>
	                <br><hr><br>
	                <div class="col-sm-12 text-center" style="height:250px; overflow-y:auto;">
	                    <?php echo nl2br(file_get_contents(dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'LICENSE')); ?>
	                </div>
	                <label><input type="checkbox" name="accept_licence" value="1" /> Gelesen und Akzeptiert</label>
	                <br>
	                <div class="row text-right">
	                    <button type="submit" class="btn btn-warning">Weiter</button>
	                </div>
	            </form>
	        <?php endif; ?>
		</div>
    </div>
</body>
