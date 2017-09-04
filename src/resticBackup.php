<?php require 'header.php'; enableToCore($userID); ?>

<?php 
$resticDir =dirname(__DIR__)."/plugins/restic/";
function get_database(){
    // changes here have to be copied to sqlDownload.php
    require 'connection_config.php';
    $mysqli = new mysqli($servername,$username,$password,$dbName);
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
        } 
        $content .="\n\n";
    }
    return $content;
}
function set_database($content){
    require 'connection_config.php';
    if($conn->query("DROP DATABASE $dbName")){
        $conn->query("CREATE DATABASE $dbName");
    } else {
        die(mysqli_error($conn));
    }
    $conn->close();
    $conn = new mysqli($servername, $username, $password, $dbName);

    $conn->query("SET FOREIGN_KEY_CHECKS=0;");
    $templine = '';
    while(($line = fgets($content)) !== false){
        $line = utf8_decode($line);
        //Skip comments
        if (substr($line, 0, 2) == '--' || $line == '') continue;

        $templine .= $line;
        //semicolon at the end = end of the query
        if(substr(trim($line), -1, 1) == ';'){
        $conn->query($templine) or print(mysqli_error($conn));
        $templine = '';
        }
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=1;");
    if(!mysqli_error($conn)){
        redirect("../user/logout");
    } else {
        $error_output = mysqli_error($conn);
    }
}
function check_repo(){
    global $conn, $resticDir;
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
    exec("$path check 2>&1",$check_output,$check_status);
    chdir(__DIR__);
    return $check_status == 0;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["path"])){
        $path = $_POST["path"];
        $stmt = $conn->prepare("UPDATE resticconfiguration SET path = ?");  
        $stmt->bind_param("s",$path);
        $stmt->execute();
    }
    if(isset($_POST["password"])){
        $password = $_POST["password"];
        $stmt = $conn->prepare("UPDATE resticconfiguration SET password = ?");  
        $stmt->bind_param("s",$password);
        $stmt->execute();
    }
    if(isset($_POST["awskey"])){
        $awskey = $_POST["awskey"];
        $stmt = $conn->prepare("UPDATE resticconfiguration SET awskey = ?");  
        $stmt->bind_param("s",$awskey);
        $stmt->execute();
    }
    if(isset($_POST["awssecret"])){
        $awssecret = $_POST["awssecret"];
        $stmt = $conn->prepare("UPDATE resticconfiguration SET awssecret = ?");  
        $stmt->bind_param("s",$awssecret);
        $stmt->execute();
    }
    if(isset($_POST["location"])){
        $location = $_POST["location"];
        $stmt = $conn->prepare("UPDATE resticconfiguration SET location = ?");  
        $stmt->bind_param("s",$location);
        $stmt->execute();
    }
    if(isset($_POST["init"])){
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
        exec("$path init 2>&1",$output,$status);
        chdir(__DIR__);
    }
    if(isset($_POST["backup"])){
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
        $cmd = "$path backup --stdin --stdin-filename backup.sql --tag connect 2>&1";
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => null,
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
    }
    if(isset($_POST["restore"])){
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
        //exec("$path restore latest . 2>&1",$output,$status);
        chdir(__DIR__);
    }
}
$currentPath = $conn->query("SELECT path FROM resticconfiguration")->fetch_assoc()["path"];
$currentPassword = $conn->query("SELECT password FROM resticconfiguration")->fetch_assoc()["password"];
$currentAwskey = $conn->query("SELECT awskey FROM resticconfiguration")->fetch_assoc()["awskey"];
$currentAwssecret = $conn->query("SELECT awssecret FROM resticconfiguration")->fetch_assoc()["awssecret"];
$currentLocation = $conn->query("SELECT location FROM resticconfiguration")->fetch_assoc()["location"];

$repositoryValid = check_repo();
$validSymbol = $repositoryValid ? "<i class='fa fa-check text-success' title='Gültige Konfiguration'></i>" : "<i class='fa fa-warning text-danger' title='Ungültige Konfiguration'></i>";
?>


<div class="page-header">
  <h3>Restic Einstellungen<?php echo $validSymbol; ?></h3>
</div>

<form method="POST" autocomplete="off">
  <div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <label>Restic Version:</label>
        </div>
        <div class="col-md-6">
            <select class="js-example-basic-single btn-block" name="path">
                <?php
                $entries = scandir($resticDir,SCANDIR_SORT_DESCENDING);
                foreach($entries as $entry){
                    if($entry == "." || $entry == "..")
                        continue;
                    $selected = (basename($currentPath) == $entry) ? "selected":"";
                    echo "<option $selected value='$resticDir/$entry'>$entry</option>";
                }
                ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <label>Restic Passwort:</label>
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control" name="password" value="<?php echo $currentPassword;?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <label>AWS Schlüssel:</label>
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control" name="awskey" value="<?php echo $currentAwskey;?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <label>AWS geheimer Schlüssel:</label>
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control" name="awssecret" value="<?php echo $currentAwssecret;?>">
        </div>
    </div>
        <div class="row">
        <div class="col-md-4">
            <label>Host:</label>
        </div>
        <div class="col-md-6">
            <select class="btn-block select2-tagging" name="location">
                <option selected value="<?php echo $currentLocation;?>"><?php echo $currentLocation;?></option>
                <option value="s3.amazonaws.com/bucket_name">s3.amazonaws.com/bucket_name</option>
                <option value="http://localhost:9000/restic">http://localhost:9000/restic</option>
            </select>
        </div>
        <script>
        $(".select2-tagging").select2({
            tags: true
        })
        </script>
    </div>
    <div class="row">
      <div class="col-xs-12">
        <button type="submit" class="btn btn-warning">Übernehmen</button>
        <button type="submit" class="btn btn-warning" name="init" value="true">Init</button>
        <?php if($repositoryValid):?>
        <button type="submit" class="btn btn-warning" name="backup" value="true">Backup</button>
        <?php endif; ?>
      </div>
    </div>
    <div class="<?php echo !$status ? 'has-success' : 'has-error'; ?>">
        <?php if($output ?? false): ?>
        <label for="output">Restic Ausgabe:</label>
        <textarea class="form-control" readonly rows="<?php echo sizeof($output); ?>" id="output"><?php foreach ($output as $num => $row) {echo "$row"; if(sizeof($output)-1!=$num) echo "\n";}?></textarea>
        <?php endif; ?>
    </div>
  </div>
</form>
<?php include 'footer.php'; ?>
