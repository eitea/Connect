<?php

require dirname(dirname(__DIR__)) . "/src/misc/useS3Config.php";
require dirname(__DIR__)."/connection.php";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $s3 = new Aws\S3\S3Client(getS3Config());
           //var_dump($_POST);
           //var_dump($_FILES);
           //echo "\n";
           //echo json_encode($_FILES);
           //exit();
          if($_POST['function']==="addGroup"){
            $companyID = $_POST['filterCompany'];
            $name = $_POST['add_groupName'];
            $amount = $_POST['amount'];
            $radio = $_POST['ttl'];
            $url = hash('whirlpool',random_bytes(100));
            try{
                //echo "INSERT INTO sharedgroups VALUES (null,'$name', null, $radio, '$url', ".$_POST['userid'].", NULL, $companyID)";
            $conn->query("INSERT INTO sharedgroups VALUES (null,'$name', null, $radio, '$url', ".$_POST['userid'].", NULL, $companyID)");
            $groupID = $conn->insert_id;
            $conn->query("CREATE EVENT ttl_$groupID ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL $radio DAY DO UPDATE sharedgroups SET uri='' WHERE id=$groupID");
            $buckets = $s3->listBuckets();
            $thereisabucket = false;
            foreach($buckets['Buckets'] as $bucket){
              if($bucket['Name']===$s3SharedFiles) $thereisabucket=true;
            }
            if(!$thereisabucket){
                $s3->createBucket( array('Bucket' => $s3SharedFiles ) ); 
            }
            for($i = 0;$i<$amount;$i++){
              $filename = pathinfo($_FILES['file'.$i]['name'], PATHINFO_FILENAME);
              $filetype = pathinfo($_FILES['file'.$i]['name'], PATHINFO_EXTENSION);
              $filesize = $_FILES['file'.$i]['size'];
              $hashkey = hash('md5',random_bytes(100));
              $conn->query("INSERT INTO sharedfiles VALUES (null,'$filename', '$filetype', ".$_POST['userid'].", $groupID, '$hashkey', $filesize, null)");
              $s3->putObject(array(
                  'Bucket' => $s3SharedFiles,
                  'Key' => $hashkey,
                  'SourceFile' => $_FILES['file'.$i]['tmp_name']
              ));
          }
      
          }catch(Exception $e){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
          }
            if($conn->error){
              echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            }
          }elseif($_POST['function']==='editGroup'&&!empty($_POST['editName'])){
            $name = $_POST['editName'];
            $conn->query("UPDATE sharedgroups SET name = '$name' WHERE id=".$_POST['groupID']);
            if(!empty($_POST['ttl'])){
              $ttl = $_POST['ttl'];
              $conn->query("UPDATE sharedgroups SET ttl = $ttl WHERE id = ".$_POST['groupID']);
              $conn->query("UPDATE sharedgroups SET dateOfBirth=CURRENT_TIMESTAMP WHERE id= ".$_POST['groupID']);
            }
          }if($_POST['function']==='editGroup'){
            $groupID = $_POST['groupID'];
            $groupName = $conn->query("SELECT name FROM sharedgroups WHERE id = $groupID");
            if(!$groupName){
                $info = array();
                $info[0] = array('name' => 'FEHLER1');
                echo json_encode($info);
                exit();
            }
            $groupName = $groupName->fetch_assoc();
            $groupName = $groupName['name'];
            $result = $conn->query("SELECT id,concat(name,concat('.',type)) AS name, uploaddate FROM sharedfiles  WHERE sharegroup = $groupID");
            if($result){
                $info = array();
                $info[0] = array( 'name' => $groupName );
                $i = 1;
                while($row = $result->fetch_assoc()){
                    $info[$i] = $row;
                    $i++;
                }
                //var_dump($info);
                echo json_encode($info);
            }else{
                $info = array();
                $info[0] = array('name' => 'FEHLER2');
                echo json_encode($info);
            }
            
        }elseif($_POST['function']==='deleteFile'){
            $s3 = new Aws\S3\S3Client(getS3Config());
            try{
            $fileID = $_POST['fileID'];
            $hash = $conn->query("SELECT hashkey FROM sharedfiles WHERE id = $fileID");
            if(!$hash){
                $info = array();
                $info[0] = array('name' => $_POST['fileID']);
                echo json_encode($info);
                exit();
            }
            $hash = $hash->fetch_assoc()['hashkey'];
            $groupID = $conn->query("SELECT sharegroup AS groupID FROM sharedfiles WHERE id = $fileID");
            if(!$groupID){
                $info = array();
                $info[0] = array('name' => 'FEHLER HASH');
                echo json_encode($info);
                exit();
            }
            $groupID = $groupID->fetch_assoc()['groupID'];
            $s3->deleteObject(array(
                'Bucket' => $s3SharedFiles,
                'Key' => $hash
            ));
            $conn->query("DELETE FROM sharedfiles WHERE id = $fileID");
            echo $groupID;
            }catch(Exception $e){
                echo $e;
            }
        }elseif($_POST['function']==='sendFiles'){
            $s3 = new Aws\S3\S3Client(getS3Config());
            try{
            $groupID = $_POST['groupID'];
            $amount = $_POST['amount'];
            for($i = 0;$i<$amount;$i++){
                $filename = pathinfo($_FILES['file'.$i]['name'], PATHINFO_FILENAME);
                $filetype = pathinfo($_FILES['file'.$i]['name'], PATHINFO_EXTENSION);
                $filesize = $_FILES['file'.$i]['size'];
                $hashkey = hash('md5',random_bytes(10));
                $conn->query("INSERT INTO sharedfiles VALUES (null,'$filename', '$filetype', ".$_POST['userID'].", $groupID, '$hashkey', $filesize, null)");
                $s3->putObject(array(
                    'Bucket' => $s3SharedFiles,
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
                  echo $groupID;
                }
        }elseif($_POST['function']==="refreshTtl"){
            $ttl = intval($_POST['ttl']);
            $id = intval($_POST['id']);
            $conn->query("UPDATE sharedgroups SET ttl=$ttl, dateOfBirth=CURRENT_TIMESTAMP WHERE id=$id");
            $conn->query("CREATE EVENT ttl_$id ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL $ttl DAY DO UPDATE sharedgroups SET uri='' WHERE id=$id");
        }else{
            return;
        }
        
    }

  ?>