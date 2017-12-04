<?php
if(empty($_GET['n'])){
    echo "Invalid Access.";
    die();
}
require __DIR__."/connection.php";
function clean($string) {
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
}
$processID = clean($_GET['n']);
$proc_agent = $_SERVER['HTTP_USER_AGENT'];

$stmt = $conn->prepare("INSERT INTO documentProcessHistory (processID, activity, info, userAgent) VALUES('$processID', ?, ?, '$proc_agent')");
$stmt->bind_param("ss", $proc_activity, $proc_info);

$proc_activity = 'visit';
$proc_info = '';
//$stmt->execute();

$result = $conn->query("SELECT password, txt, documentProcess.id, documents.name AS docName, companyData.* FROM documentProcess
LEFT JOIN documents ON documents.id = docID
LEFT JOIN companyData ON companyID = companyData.id
WHERE documentProcess.id = '$processID'");

if(!$result || $result->num_rows < 1){
    $proc_activity = 'invalid access';
    $proc_info = 'ID in Link is invalid';
    $stmt->execute();
    echo "Invalid Access.";
    die();
}
$stmt->close();
$document_row = $result->fetch_assoc();

if($document_row['password']){
    //TODO: we want a password and an access denial
}
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
            <div class="col-sm-4 h2">
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
        <div class="col-md-8 col-md-offset-2" style="overflow:auto; max-height:60vh;"><?php echo $document_row['txt'];?></div>
    </div>

    <?php
        $processHistory = array();
        $result = $conn->query("SELECT activity, info FROM documentProcessHistory WHERE processID = '$processID'");
        while($row = $result->fetch_assoc()){
            $processHistory[$row['activity']][] = $row['info'];
        }
    ?>

</body>