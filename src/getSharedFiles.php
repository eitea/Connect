<?php

require "vendor/autoload.php";
require __DIR__."/connection.php";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        if(!empty($_POST['groupID'])){
            $groupID = $_POST['groupID'];
            $groupName = $conn->query("SELECT name FROM sharedgroups WHERE id = $groupID");
            if(!$groupName){
                $info = array();
                $info[0] = array('name' => 'FEHLER');
                echo json_encode($info);
                exit();
            }
            $groupName = $groupName->fetch_assoc();
            $groupName = $groupName['name'];
            $result = $conn->query("SELECT id,concat(name,concat('.',type)) AS name, uploaddate FROM sharedFiles  WHERE sharegroup = $groupID");
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
                $info[0] = array('name' => 'FEHLER');
                echo json_encode($info);
            }
            
        }elseif(!empty($_POST['fileID'])){
            $s3 = new Aws\S3\S3Client($s3config);
            try{
            $fileID = $_POST['fileID'];
            $hash = $conn->query("SELECT hashkey FROM sharedFiles WHERE id = $fileID");
            if(!$hash){
                $info = array();
                $info[0] = array('name' => $_POST['fileID']);
                echo json_encode($info);
                exit();
            }
            $hash = $hash->fetch_assoc()['hashkey'];
            $groupID = $conn->query("SELECT sharegroup AS groupID FROM sharedFiles WHERE id = $fileID");
            if(!$groupID){
                $info = array();
                $info[0] = array('name' => 'FEHLER HASH');
                echo json_encode($info);
                exit();
            }
            $groupID = $groupID->fetch_assoc()['groupID'];
            $s3->deleteObject(array(
                'Bucket' => 'sharedFiles',
                'Key' => $hash
            ));
            $conn->query("DELETE FROM sharedFiles WHERE id = $fileID");
            echo $groupID;
            }catch(Exception $e){
                echo $e;
            }
        }
    }

  ?>