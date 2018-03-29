<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'header.php'; ?>

<?php
if(!isset($_GET['p'])) die('Invalid access');
$result = $conn->query("SELECT p.name, c.name AS clientName  FROM projectData p LEFT JOIN clientData c ON p.clientID = c.id WHERE p.id = ".intval($_GET['p']));
$projectRow = $result->fetch_assoc();
?>

<div class="page-header">
    <h3><?php echo $projectRow['clientName'].' - '.$projectRow['name']; ?></h3>
</div>

<div class="row">
</div>

<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; ?>
