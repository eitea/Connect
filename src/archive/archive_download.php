<?php

require dirname(__DIR__) . "/misc/useS3Config.php";
require dirname(__DIR__)."/connection.php";
  $s3 = new Aws\S3\S3Client(getS3Config());
    if(empty($_GET['n'])){
      echo "Invalid Access.";
      die();
  }
  function clean($string) {
      return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
  }
  
  $hashkey = clean($_GET['n']);
  $proc_agent = $_SERVER['HTTP_USER_AGENT'];
  $access = true;

  $result = $conn->query("SELECT * FROM sharedfiles WHERE hashkey='$hashkey'");
  if($result){
      $row = $result->fetch_assoc();
      $object= $s3->getObject(array(
          'Bucket' => $s3SharedFiles,
          'Key' => $hashkey,
      ));
  }

header( "Cache-Control: public" );
header( "Content-Description: File Transfer" );
header( "Content-Disposition: attachment; filename=" . $row['name'] . "." . $row['type'] );
header( "Content-Type: {$object['ContentType']}" );
echo $object[ 'Body' ];

?>