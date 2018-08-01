<?php //SECURE THIS PAGE LIKE A ROCK
if(empty($_GET['n'])){ // n is not company id
    echo "Invalid Access.";
    die();
}
require dirname(__DIR__) . "/connection.php";
function clean($string) {
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
}

$processID = clean($_GET['n']); // n is not company id
$proc_agent = $_SERVER['HTTP_USER_AGENT'];
$access = true;

//logs
$stmt = $conn->prepare("INSERT INTO documentProcessHistory (processID, activity, info, userAgent) VALUES('$processID', ?, ?, '$proc_agent')");
$stmt->bind_param("ss", $proc_activity, $proc_info);

//document
$result = $conn->query("SELECT password, document_text AS txt, document_headline AS docName, documentProcess.id, companyData.* FROM documentProcess
LEFT JOIN documents ON documents.id = documentProcess.docID
LEFT JOIN companyData ON companyID = companyData.id
WHERE documentProcess.id = '$processID'");

if(!$result || $result->num_rows < 1){
    echo $conn->error;
    $proc_activity = 'invalid access';
    $proc_info = 'ID in Link is invalid';
    $stmt->execute();
    echo "Invalid Access.";
    die();
}
$document_row = $result->fetch_assoc();

//history
$processHistory = array();
$result = $conn->query("SELECT activity, info FROM documentProcessHistory WHERE processID = '$processID'");
while($row = $result->fetch_assoc()){
    if(isset($processHistory[$row['activity']])){
        $processHistory[$row['activity']]['count'] += 1;
    } else {
        $processHistory[$row['activity']][] = $row['info'];
        $processHistory[$row['activity']]['count'] = 1;
    }
}

if($document_row['password']){
    $access = false;
    if(!empty($_POST['enable_access'])){
        if(crypt($_POST['enable_access'], $document_row['password']) == $document_row['password']){
            $access = true;
        } else {
            $proc_activity = 'password_denied';
            $proc_info = clean($_POST['enable_access']);
            $stmt->execute();
            if(isset($processHistory['password_denied'])){
                $processHistory['password_denied']['count'] += 1;
            }
        }
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['action_read']) && !isset($processHistory['action_read'])){
        $proc_activity = 'action_read';
        $proc_info = '';
        $stmt->execute();
        $processHistory['action_read'] = true;
        $access = true;
    }
    if(isset($_POST['action_accept']) && !isset($processHistory['action_accept'])){
        $proc_activity = 'action_accept';
        $proc_info = 'DECLINED';
        if($_POST['action_accept']){
            $proc_info = 'ACCEPTED';
        }
        $stmt->execute();
        $processHistory['action_accept'] = true;
        $access = true;
    }
    if(!empty($_POST['action_sign']) && !isset($processHistory['action_sign'])){
        $proc_activity = 'action_sign';
        $proc_info = htmlspecialchars(strip_tags($_POST['action_sign']));
        $stmt->execute();
        $processHistory['action_sign'] = true;
        $access = true;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="plugins/jQuery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.min.js"></script>
    <link href="plugins/homeMenu/template.css" rel="stylesheet" />
    <title>Connect</title>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-4">
                <?php if($document_row['logo']){echo '<img style="max-width:350px;max-height:200px;" src="data:image/jpeg;base64,'.base64_encode( $document_row['logo'] ).'"/>';} ?>
            </div>
            <div class="col-sm-4 text-center h2">
                <?php echo $document_row['docName']; ?>
            </div>
            <div class="col-sm-4 text-right">
                <p style="margin:0"><?php echo $document_row['cmpDescription']; ?></p>
                <p style="margin:0"><?php echo $document_row['address']; ?></p>
                <p style="margin:0"><?php echo $document_row['companyPostal'].' '.$document_row['companyCity']; ?></p>
                <p style="margin:0"><?php echo $document_row['phone']; ?></p>
                <p style="margin:0"><?php echo $document_row['homepage']; ?></p>
                <p style="margin:0"><?php echo $document_row['mail']; ?></p>
            </div>
        </div>
        <hr>
        <?php
        if(isset($processHistory['password_denied']) && $processHistory['password_denied']['count'] > 2){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Passwort wurde zu oft falsch eingegeben. Dieses Dokument wurde gesperrt.</div></div>';
            die();
        }
        ?>
        <?php if($access): ?>
            <div class="row form-group"><div class="col-md-8 col-md-offset-2" style="overflow:auto; max-height:60vh;"><?php echo $document_row['txt'];?></div></div>
            <form method="POST">
                <div class="row form-group">
                    <?php
                    $show_save = false;
                    echo '<div class="col-sm-3 col-md-offset-2 checkbox radio">';
                    if(isset($processHistory['ENABLE_READ']) && !isset($processHistory['action_read'])){
                        $show_save = true;
                        echo '<label><input type="checkbox" name="action_read" value="1" /> Gelesen</label><br>';
                    }
                    if(isset($processHistory['ENABLE_ACCEPT']) && !isset($processHistory['action_accept'])){
                        $show_save = true;
                        echo '<label><input type="radio" name="action_accept" value="1" /> Akzeptieren</label><br>';
                        echo '<label><input type="radio" name="action_accept" value="0" /> Nicht akzeptieren</label>';
                    }
                    echo '</div>';
                    echo '<div class="col-sm-3">';
                    if(isset($processHistory['ENABLE_SIGN']) && !isset($processHistory['action_sign'])){
                        $show_save = true;
                        echo '<input type="text" class="form-control signed-box" name="action_sign" placeholder="Unterschrift" />';
                    }
                    echo '</div>';
                    if($show_save){
                        echo '<div class="col-sm-2 text-right"><button class="btn btn-default btn-block">Speichern</button></div>';
                    }
                    ?>
                </div>
            </form>
        <?php else: ?>
            <form method="POST">
            <div class="row">
                <div class="col-sm-6 col-sm-offset-3 text-center">
                    <label>Zugang ist Passwort geschützt</label>
                    <div class="input-group">
                        <input type="password" name="enable_access" class="form-control" placeholder="Password"/>
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="submit">OK</button>
                        </span>
                    </div>
                </div>
            </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
