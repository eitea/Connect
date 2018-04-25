<?php
require dirname(dirname(__DIR__)) . '/plugins/aws/autoload.php';
require dirname(__DIR__).DIRECTORY_SEPARATOR.'connection.php';
require dirname(__DIR__).DIRECTORY_SEPARATOR.'utilities.php';

$fileKey = test_input($_POST['download-file']);
$symmetricKey = base64_decode($_POST['symmetricKey']);

$result = $conn->query("SELECT endpoint, awskey, secret FROM archiveconfig WHERE isActive = 'TRUE' LIMIT 1");
if($result && ($row = $result->fetch_assoc())){
    $link_id = (getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])) ? substr($servername, 0, 8) : $identifier;
    try{
        $s3 = new Aws\S3\S3Client(array(
            'version' => 'latest',
            'region' => '',
            'endpoint' => $row['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => array('key' => $row['awskey'], 'secret' => $row['secret'])
        ));

        $object = $s3->getObject(array(
            'Bucket' => $link_id .'-uploads',
            'Key' => $fileKey,
        ));
    } catch(Exception $e){
        echo $e->getMessage();
    }
}

$result = $conn->query("SELECT name, type FROM project_archive WHERE uniqID = '$fileKey' LIMIT 1");
$row = $result->fetch_assoc();

if(isset($object)){
    header( "Cache-Control: public" );
    header( "Content-Description: File Transfer" );
    header( "Content-Disposition: attachment; filename=" . $row['name'] . "." . $row['type'] );
    header( "Content-Type: {$object['ContentType']}" );
    echo simple_decryption($object[ 'Body' ], $symmetricKey);
}
?>
