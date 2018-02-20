<?php
session_start();
if(empty($_SESSION['userid'])){
  die('Please <a href="login.php">login</a> first.');
}
$userID = $_SESSION['userid'];
require_once dirname(dirname(__DIR__)).'/validate.php';
enableToCore($userID);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['start_Download'])){
    require dirname(dirname(__DIR__)).'/connection_config.php';
    if(!empty($_POST['password'])){
      Export_Database($servername,$username,$password,$dbName,$_POST['password']);
    } else {
      Export_Database($servername,$username,$password,$dbName);
    }
  }
}

//changes here have to be copied to resticBackup.php
function Export_Database($host, $user, $pass, $dbName, $password=false){
  $content = '';
  $backup_name = $dbName."_".date('dmY_Hi', time());
  $zip_name = $backup_name.".zip";
  $backup_name = $backup_name.".sql";

  //exec("mysqldump --user=$user --password=$pass --host=$host $dbName", $content); will not work without mysql installation
  require dirname(dirname(dirname(__DIR__))).'/plugins/mysqldump/Mysqldump.php';
  $dump = new MySQLDump(new mysqli($host, $user, $pass, $dbName));
  $dump->save($backup_name);
  $content = file_get_contents($backup_name);
  unlink($backup_name);

  $zip = new ZipArchive();
  if ($zip->open($zip_name, ZIPARCHIVE::CREATE) === false) {
    exit("cannot open $zip_name \n");
  }
  $zip->addFromString($backup_name, $content);
  if($password){
    $zip->setPassword($password);
    $zip->setEncryptionName($backup_name, ZipArchive::EM_AES_256); //php 7.2. update
    //system("zip -P $password $zip_name $zip_name");
  }
  $zip->close();

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
