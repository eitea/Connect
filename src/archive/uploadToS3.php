<?php
    require dirname(__DIR__)."/misc/useS3Config.php";
    require dirname(__DIR__)."/connection.php";

    $s3 = new Aws\S3\S3Client(getS3Config());
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        if(!empty($_POST['name']) && !empty($_FILES['file']) && !empty($_POST['note'])){
          $name = $_POST['name'];
          $notes = $_POST['note'];
          try{
            $filename = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
            $filetype = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filesize = $_FILES['file']['size'];
            $hashkey = hash('md5',random_bytes(10));
            $conn->query("INSERT INTO uploadedfiles VALUES (null,'$name', '$filename', '$filetype', '$hashkey', $filesize, null, '$notes')");
            $s3->putObject(array(
                'Bucket' => 'uploadedFiles',
                'Key' => $hashkey,
                'SourceFile' => $_FILES['file']['name']
            ));
        }catch(Exception $e){
          echo "Upps, something went wrong!";
        }
          if($conn->error){
            echo "Upps, something went wrong!";
          } else {
              header('Location: ../archive/upload');
          }
        }
      }
?>