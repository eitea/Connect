<?php require_once dirname(__DIR__)."/connection.php"; require_once dirname(__DIR__)."/createTimestamps.php"; ?>
<?php require_once dirname(__DIR__).'/utilities.php'; ?>
<?php

function get_database($tables = false){
    // changes here have to be copied to sqlDownload.php
    require dirname(__DIR__).'/connection_config.php';
    $mysqli = new mysqli($servername,$username,$password,$dbName);
    $mysqli->select_db($dbName);
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
        } 
        $content .="\n\n";
    }
    return $content;
}

$resticDir = driname(dirname(__DIR__))."/plugins/restic/";
$row = $conn->query("SELECT * FROM resticconfiguration")->fetch_assoc();

$location = "s3:".$row["location"];
$password = $row["password"];
$awskey = $row["awskey"];
$awssecret = $row["awssecret"];
$path = basename($row["path"]);

putenv("AWS_ACCESS_KEY_ID=$awskey");
putenv("AWS_SECRET_ACCESS_KEY=$awssecret");
putenv("RESTIC_REPOSITORY=$location");
putenv("RESTIC_PASSWORD=$password");

chdir($resticDir);
$cmd = "$path backup --stdin --stdin-filename backup.sql --tag database 2>&1";
$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
);
$process = proc_open($cmd, $descriptorspec, $pipes);
if (is_resource($process)) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout

    fwrite($pipes[0], get_database()); // file_get_contents('php://stdin')
    fclose($pipes[0]);

    $output = array( stream_get_contents($pipes[1]) );
    fclose($pipes[1]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $return_value = proc_close($process);
}
chdir(__DIR__);
