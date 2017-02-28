<?php
session_start();
if(empty($_SESSION['userid'])){
  die('Please <a href="login.php">login</a> first.');
}
$userID = $_SESSION['userid'];
require_once 'validate.php';
denyToCloud();
enableToCore($userID);

require 'connection_config.php';

//ENTER THE RELEVANT INFO BELOW
$mysqlUserName      = $username;
$mysqlPassword      = $password;
$mysqlHostName      = $servername;
$DbName             = $dbName;
$backup_name        = "$dbName.sql";
$tables             = array("mytable1","mytable2","mytable3"); //for specific tables

Export_Database($mysqlHostName,$mysqlUserName,$mysqlPassword,$DbName,  $tables=false, $backup_name=false );

function Export_Database($host,$user,$pass,$name,$tables=false,$backup_name=false){
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
    $content        = (!isset($content) ?  '' : $content) . "\n\n".$TableMLine[1].";\n\n";

    for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0){
      while($row = $result->fetch_row()){
        //when started (and every after 100 command cycle):
        if ($st_counter%100 == 0 || $st_counter == 0 ){
          $content .= "\nINSERT INTO ".$table." VALUES";
        }
        $content .= "\n(";
        for($j=0; $j<$fields_amount; $j++){
          $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) );
          if (isset($row[$j])){
            $content .= '"'.$row[$j].'"' ;
          } else {
            $content .= '""';
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
    } $content .="\n\n\n";
  }

  $events = $mysqli->query("SHOW EVENTS");
  while($events && ($row = $events->fetch_row())){
    $res = $mysqli->query("SHOW CREATE EVENT ".$row[0].'.'.$row[1]);
    $TableMLine = $res->fetch_row();
    $content .= "\n\n".$TableMLine[3].";\n\n";
  }

  $backup_name = $backup_name ? $backup_name : $name.".sql";
  header('Content-Type: application/octet-stream');
  header("Content-Transfer-Encoding: Binary");
  header("Content-disposition: attachment; filename=\"".$backup_name."\"");
  echo $content;  exit;
}
?>
