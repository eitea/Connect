<?php

require dirname(__DIR__) . "/utilities.php";
require dirname(__DIR__)."/connection.php";

  $s3 = getS3Object();

  $result = $conn->query("SELECT id FROM identification LIMIT 1");
  $row = $result->fetch_assoc();
  $bucket = $row['id'].'_sharedFiles';

  $hashkey = test_input($_GET['n'],1);
  $proc_agent = $_SERVER['HTTP_USER_AGENT'];
  $access = true;

  $result = $conn->query("SELECT * FROM sharedfiles WHERE hashkey='$hashkey'");
  if($result){
      $row = $result->fetch_assoc();
      $object= $s3->getObject(array(
          'Bucket' => $bucket,
          'Key' => $hashkey,
      ));
  }

header( "Cache-Control: public" );
header( "Content-Description: File Transfer" );
header( "Content-Disposition: attachment; filename=" . $row['name'] . "." . $row['type'] );
header( "Content-Type: {$object['ContentType']}" );
echo $object[ 'Body' ];

?>
