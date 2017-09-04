<?php
session_start();
if(empty($_SESSION['userid'])){
  die('Please <a href="login.php">login</a> first.');
}
$userID = $_SESSION['userid'];
require_once 'validate.php';
enableToCore($userID);

require 'connection_config.php';

$backup_name = $dbName."_".date('dmY_Hi', time());

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['start_Download'])){
    if(isset($_POST['setPassword']) && !empty($_POST['password'])){
      Export_Database($servername,$username,$password,$dbName,$tables=false,$backup_name,$_POST['password']);
    } else {
      Export_Database($servername,$username,$password,$dbName,$tables=false,$backup_name);
    }
  }
}

function Export_Database($host,$user,$pass,$name,$tables=false,$backup_name='backup',$password=false){
  // changes here have to be copied to resticBackup.php
  $mysqli = new mysqli($host,$user,$pass,$name);
  $mysqli->select_db($name);
  $mysqli->query("SET NAMES 'utf8'");
  $queryTables    = $mysqli->query("SHOW TABLES");
  while($row = $queryTables->fetch_row()){
    $target_tables[] = $row['0']; //put each table name into array
  }
  if($tables){
    $target_tables = array_intersect($target_tables, $tables);
  }
  foreach($target_tables as $table){
    $result         =   $mysqli->query('SELECT * FROM '.$table);
    $fields_amount  =   $result->field_count;
    $rows_num       =   $mysqli->affected_rows;
    $res            =   $mysqli->query('SHOW CREATE TABLE '.$table);
    $TableMLine     =   $res->fetch_row();
    $content        = (!isset($content) ?  '' : $content) . "\n".$TableMLine[1].";\n";
    for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0){
      while($row = $result->fetch_row()){
        //when started (and every after 100 command cycle):
        if ($st_counter%100 == 0 || $st_counter == 0 ){
          $content .= "\nINSERT INTO ".$table." VALUES";
        }
        $content .= "\n(";
        for($j=0; $j<$fields_amount; $j++){
          $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) );
          if ($row[$j] || $row[$j] === "0"){
            $content .= '"'.$row[$j].'"' ;
          } else {
            $content .= 'NULL';
          }
          if ($j<($fields_amount-1)){
            $content.= ',';
          }
        }
        $content .=")";
        //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
        if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num){
          $content .= ";";
        } else {
          $content .= ",";
        }
        $st_counter=$st_counter+1;
      }
    } $content .="\n\n";
  }
  /*
  $events = $mysqli->query("SHOW EVENTS");
  while($events && ($row = $events->fetch_row())){
  $res = $mysqli->query("SHOW CREATE EVENT ".$row[0].'.'.$row[1]);
  $TableMLine = $res->fetch_row();
  $content .= "\n\n".$TableMLine[3].";\n\n";
}
*/

$zip_name = $backup_name.".zip";
$backup_name = $backup_name. ".sql";

/*
$tempnam = tempnam(sys_get_temp_dir(), $backup_name);
$temp = fopen($tempnam, 'w');
fwrite($temp, $content);
*/

$zip = new ZipArchive();
if ($zip->open($zip_name, ZIPARCHIVE::CREATE)!==TRUE) {
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
