<?php
require dirname(dirname(__DIR__)) . "\src\misc\useS3Config.php";
require dirname(__DIR__)."\connection.php";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $s3 = new Aws\S3\S3Client(getS3Config());
        if($_POST['function']==="addFile"){
            try{
            $buckets = $s3->listBuckets();
            $thereisabucket = false;
            foreach($buckets['Buckets'] as $bucket){
              if($bucket['Name']===$s3privateFiles) $thereisabucket=true;
            }
            if(!$thereisabucket){
                $s3->createBucket( array('Bucket' => $s3privateFiles ) ); 
            }
            $folder = intval($_POST['folderid']);
            $amount = $_POST['amount'];
            for($i = 0;$i<$amount;$i++){
                $filename = pathinfo($_FILES['file'.$i]['name'], PATHINFO_FILENAME);
                $filetype = pathinfo($_FILES['file'.$i]['name'], PATHINFO_EXTENSION);
                $filesize = $_FILES['file'.$i]['size'];
                $hashkey = hash('md5',random_bytes(10));
                $conn->query("INSERT INTO archive_savedfiles VALUES (null,'$filename', '$filetype', $folder, ".$_POST['userID'].", '$hashkey', $filesize, null)");
                $s3->putObject(array(
                    'Bucket' => $s3privateFiles,
                    'Key' => $hashkey,
                    'SourceFile' => $_FILES['file'.$i]['tmp_name']
                ));
            }
              }catch(Exception $e){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
              }
                if($conn->error){
                  echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
                } else {
                  echo $folder;
                }

            }elseif($_POST['function']==="deleteFile"){
                try{
                    $fileID = $_POST['id'];
                    $hash = $conn->query("SELECT hashkey FROM archive_savedfiles WHERE id = $fileID");
                    if(!$hash){
                        $info = array();
                        $info[0] = array('name' => $_POST['fileID']);
                        echo json_encode($info);
                        exit();
                    }
                    $hash = $hash->fetch_assoc()['hashkey'];
                    $s3->deleteObject(array(
                        'Bucket' => $s3privateFiles,
                        'Key' => $hash
                    ));
                    $conn->query("DELETE FROM archive_savedfiles WHERE id = $fileID");
                    echo $groupID;
                    }catch(Exception $e){
                        echo $e;
                    }
            }elseif($_POST['function']==="getFileData"){
                try{
                    $id = $_POST['id'];
                    $result = $conn->query("SELECT f.name AS name, e.body AS body FROM archive_savedfiles f JOIN archive_editfiles e ON f.hashkey = e.hashid WHERE id = $id");
                    $row = $result->fetch_assoc();
                    $data = array();
                    $data[0] = $row['name'];
                    $data[1] = $row['body'];
                    echo json_encode($data);
                }catch(Exception $e){
                    echo $e;
                }
            }

        }elseif(!empty($_GET['n'])){
            $s3 = new Aws\S3\S3Client(getS3Config());
            $hashkey = $_GET['n'];
            $result = $conn->query("SELECT * FROM archive_savedfiles WHERE hashkey='$hashkey'");
            if($result){
                $row = $result->fetch_assoc();
                $object= $s3->getObject(array(
                    'Bucket' => $s3privateFiles,
                    'Key' => $hashkey,
                ));
            }
          
          header( "Cache-Control: public" );
          header( "Content-Description: File Transfer" );
          header( "Content-Disposition: attachment; filename=" . $row['name'] . "." . $row['type'] );
          header( "Content-Type: {$object['ContentType']}" );
          echo $object[ 'Body' ];
        }

?>