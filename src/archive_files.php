<?php

require dirname(__DIR__) . "\plugins\aws\autoload.php";
require __DIR__ . "/connection.php";
$s3 = new Aws\S3\S3Client($s3config);
if (empty($_GET['n'])) {
    echo "Invalid Access.";
    die();
}
function clean($string) {
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
}

$uri = clean($_GET['n']);
$proc_agent = $_SERVER['HTTP_USER_AGENT'];
$access = true;

function round_up($number, $precision = 2) {
    $fig = (int) str_pad('1', $precision, '0');
    return (ceil($number * $fig) / $fig);
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
  <title>Files</title>
</head>
<body>
    <?php
$company_result = $conn->query("SELECT companyData.* FROM sharedgroups JOIN companyData ON company=companyData.id WHERE uri ='$uri'");
if (!$company_result || $company_result->num_rows < 1) {
    $proc_activity = 'invalid access';
    $proc_info = 'ID in Link is invalid';
    echo "Invalid Access.";
    die();
}
$company_row = $company_result->fetch_assoc();
$result = $conn->query("SELECT * FROM sharedgroups WHERE uri ='$uri'");
if (!$result || $result->num_rows < 1) {
    echo "Invalid Access.";
    die();
}
$group = $result->fetch_assoc();

?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-4">
                <?php if ($company_row['logo']) {echo '<img style="max-width:350px;max-height:200px;" src="data:image/jpeg;base64,' . base64_encode($company_row['logo']) . '"/>';}?>
            </div>
            <div class="col-sm-4 text-center h2">
                <?php echo $group['name']; ?>
            </div>
            <div class="col-sm-4 text-right">
                <p style="margin:0"><?php echo $company_row['cmpDescription']; ?></p>
                <p style="margin:0"><?php echo $company_row['address']; ?></p>
                <p style="margin:0"><?php echo $company_row['companyPostal'] . ' ' . $company_row['companyCity']; ?></p>
                <p style="margin:0"><?php echo $company_row['phone']; ?></p>
                <p style="margin:0"><?php echo $company_row['homepage']; ?></p>
                <p style="margin:0"><?php echo $company_row['mail']; ?></p>
            </div>
        </div>
        <hr>
        <?php
if (isset($processHistory['password_denied']) && $processHistory['password_denied']['count'] > 2) {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Passwort wurde zu oft falsch eingegeben. Dieses Dokument wurde gesperrt.</div></div>';
    die();
}
?>
        <table class="table">
        <thead><tr>
        <th>Name</th>
        <th>Größe</th>
        <th>Erstellungsdatum</th>
        </tr></thead>
        <?php

$groupID = $group['id'];
$result = $conn->query("SELECT * FROM sharedfiles WHERE sharegroup=$groupID");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo "<td>";

        echo "<a target='blank_' href='../archive/download?n=" . $row['hashkey'] . "' >";
        echo $row['name'] . "." . $row['type'];
        echo "</a>";
        echo "</td>";
        echo "<td>";
        echo round_up($row['filesize'] / 1024) . " KB";
        echo "</td>";
        echo "<td>";
        echo date("F j, Y, g:i a", strtotime($row['uploaddate']));
        echo "</td>";
        echo '</tr>';
    }
}
?>

    </div>
</body>
</html>

