<?php
require 'vendor/autoload.php';
require __DIR__."/connection.php";
if(empty($_GET['n'])){
    echo "Invalid Access.";
    die();
}

$s3 = new Aws\S3\S3Client($s3config);
$groupID = $_GET['n'];

try{
$result = $conn->query("SELECT * FROM sharedfiles WHERE sharegroup=".$groupID."");


while($row = $result->fetch_assoc()){
    
    $s3->deleteObject(array(
        'Bucket' => $s3SharedFiles,
        'Key' => $row['hashkey']
    ));
    $conn->query("DELETE FROM sharedfiles WHERE id=".$row['id']);
}

$conn->query("DELETE FROM sharedgroups WHERE id=".$groupID);
}catch(Exception $e){
    echo $e;
    die();
    header('Location: ../archive/share');
}
header('Location: ../archive/share');

?>