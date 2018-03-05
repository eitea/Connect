<?php
    require dirname(dirname(__DIR__)) . "/plugins/aws/autoload.php";
    function getS3Config(){
        require dirname(__DIR__) . "/connection.php";
        $config = $conn->query("SELECT * FROM archiveconfig WHERE isActive = 'TRUE'");
        if(isset($config)){
            $row = $config->fetch_assoc();
            if(isset($row['endpoint'])){
                $credentials = array('key' => $row['awskey'], 'secret' => $row['secret']);
                $s3config = array('version' => 'latest','region' => '','endpoint' => $row['endpoint'],'use_path_style_endpoint' => true,'credentials' => $credentials);
                return $s3config;
            }
        }
        return false;
    }

    function addS3Config($server,$key,$secret,$name){
        require dirname(__DIR__) . "/connection.php";
        try{
            $active = 'FALSE';
            $result = $conn->query("SELECT id FROM archiveconfig");
            if($result->num_rows<1) $active = 'TRUE';
            if(empty($name)) $name = $server;
            $conn->query("INSERT INTO archiveconfig (endpoint,awskey,secret,isActive,name) VALUES ('$server','$key','$secret','$active','$name')");
            return true;
        }catch(Exception $e){
            return false;
        }
    }

    function clearS3Config(){
        require dirname(__DIR__) . "/connection.php";
        try{
            $conn->query("DELETE FROM archiveconfig");
            return true;
        }catch(Exception $e){
            return false;
        }
    }
?>