<?php

require dirname(__DIR__) . "/misc/useS3Config.php";
require dirname(__DIR__) . "/connection.php";
if (empty($_POST['groupID'])) {
    echo "Invalid Access.";
    die();
}

$s3 = new Aws\S3\S3Client(getS3Config());
$groupID = $_POST['groupID'];

try {
    $result = $conn->query("SELECT * FROM sharedfiles WHERE sharegroup=" . $groupID . "");
    while ($row = $result->fetch_assoc()) {

        $s3->deleteObject(array(
            'Bucket' => $s3SharedFiles,
            'Key' => $row['hashkey']
        ));
        $conn->query("DELETE FROM sharedfiles WHERE id=" . $row['id']);
    }

    $conn->query("DELETE FROM sharedgroups WHERE id=" . $groupID);
} catch (Exception $e) {
    echo $e;
    die();
}
return;
?>
