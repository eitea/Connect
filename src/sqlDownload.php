<?php
session_start();
if(empty($_SESSION['userid'])){
  die('Please <a href="login.php">login</a> first.');
}
$userID = $_SESSION['userid'];
require_once 'validate.php';
enableToCore($userID);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['start_Download'])){
    require 'connection_config.php';
    if(isset($_POST['setPassword']) && !empty($_POST['password'])){
      Export_Database($servername,$username,$password,$dbName,$_POST['password']);
    } else {
      Export_Database($servername,$username,$password,$dbName);
    }
  }
}

function Export_Database($host, $user, $pass, $dbName, $password=false){
  // changes here have to be copied to resticBackup.php 
  $content = '';
  exec("mysqldump --user=$user --password=$pass --host=$host $dbName", $content);

  $backup_name = $dbName."_".date('dmY_Hi', time());
  $zip_name = $backup_name.".zip";
  $backup_name = $backup_name. ".sql";

  $zip = new ZipArchive();
  if ($zip->open($zip_name, ZIPARCHIVE::CREATE) === false) {
    exit("cannot open $zip_name \n");
  }
  $zip->addFromString($backup_name, $content);
  $zip->close();

  if($password){
    //$zip->setEncryptionName($backup_name, ZipArchive::EM_AES_256); //php 7.2. update
    system("zip -P $password $zip_name $zip_name");
  }

  header('Content-Type: application/octet-stream');
  header("Content-Transfer-Encoding: Binary");
  header("Content-Disposition: attachment; filename=\"".$zip_name."\"");
  clearstatcache();
  header("Content-Length: ".filesize($zip_name));
  readfile($zip_name);
  unlink($zip_name);
  exit;
}
?>
