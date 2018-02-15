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
    <title>Setup Connect</title>
</head>
<body>
    <?php
    include dirname(dirname(__DIR__)).'/utilities.php';
    $pgp_keys = pgp_generate_keys();
    var_dump($pgp_keys);

    
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        if(!empty($_POST['encryption_pass']) && !empty($_POST['encryption_pass_confirm']) && $_POST['encryption_pass'] == $_POST['encryption_pass_confirm']){
            include dirname(dirname(__DIR__)).'/utilities.php';
            include dirname(dirname(__DIR__)).'/connection.php';
            $hash = password_hash($_POST['encryption_pass'], PASSWORD_BCRYPT);
            $pgp_keys = pgp_generate_keys();
            var_dump($pgp_keys);
        }
    }
    ?>
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

    <div class="container">
        <div class="page-header h3">Einstellungen</div>
        Hallo!<br>
        Ihre Connect Umgebung steht Ihnen in kürze bereit. Sie müssen nur noch ihr gewünschtes Login-Kennwort eingeben und können dann die Einstellungen überprüfen.<br>
        Falls Sie hilfe benötigen, suchen sie immer nach diesem Symbol <i class="fa fa-question-circle-o"></i> um eine Anleitung und mehr Informationen zu erhalten.<br>
        Wir wünschen Ihnen viel Vergnügen.<br>
        <br><hr><br>
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
    </div>
</body>
