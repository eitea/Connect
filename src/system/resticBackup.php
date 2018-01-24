<?php include dirname(__DIR__) . '/header.php'; enableToCore($userID); ?>

<?php 
$resticDir =dirname(dirname(__DIR__))."/plugins/restic/";
$exec_modifier = ""; //modifier to execute file in current directory
if(stripos(php_uname("s"),"Windows") === false){
    $exec_modifier = "./";
}
// changes here have to be copied to sqlDownload.php
function get_database($tables = false){
    require dirname(__DIR__) . '/connection_config.php';
    require dirname(dirname(__DIR__)).'/plugins/mysqldump/Mysqldump.php';
    $dump = new MySQLDump(new mysqli($servername,$username,$password,$dbName));
    $dump->save($backup_name);
    $content = file_get_contents($backup_name);
    unlink($backup_name);
    return $content;
}
// changes here have to be copied to upload_database.php
function set_database($filename){
    require dirname(__DIR__) . '/connection_config.php';
    global $conn;
    $file = fopen($filename, 'rb');
    require dirname(dirname(__DIR__)).'/plugins/mysqldump/MySQLImport.php';
    $import = new MySQLImport(new mysqli($servername,$username,$password,$dbName));
    $import->load($file);
    fclose($file);
}
function check_repo(){
    global $conn, $resticDir, $exec_modifier;
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
    $check_status = 1;
    exec("${exec_modifier}$path check 2>&1",$check_output,$check_status);
    chdir(dirname(__DIR__));
    return $check_status == 0 && count($check_output)>0;
}
function list_snapshots(){
    global $conn,$resticDir, $exec_modifier;
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
    exec("${exec_modifier}$path snapshots --json",$snapshot_output,$snapshot_status);
    chdir(dirname(__DIR__));
    $str = "";
    foreach ($snapshot_output as $line) {
        $str.=$line;
    }
    $snapshot_output = json_decode($str,true);
    return $snapshot_output;
}
$snapshots = array_reverse(list_snapshots()??array());

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
        exec("chmod 777 $path");
        exec("${exec_modifier}$path init 2>&1",$output,$status);
        chdir(dirname(__DIR__));
    }
    if(isset($_POST["backup-database"])){
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
        $cmd = "${exec_modifier}$path backup --stdin --stdin-filename backup.sql --tag database 2>&1";
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
        chdir(dirname(__DIR__));
    }
    if(isset($_POST["backup-files"])){
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
        $connectFolder = dirname(dirname(__DIR__));
        set_time_limit(600);
        $connectFolder = escapeshellarg($connectFolder);
        exec("${exec_modifier}$path backup $connectFolder --tag files 2>&1",$output,$status);
        chdir(dirname(__DIR__));
    }
    if(isset($_POST["restore"],$_POST["snapshot"])){
        $row = $conn->query("SELECT * FROM resticconfiguration")->fetch_assoc();
        $snapshot = $_POST["snapshot"];
        $snapshot = preg_replace("[^A-Za-z0-9]", "", $snapshot);

        $location = "s3:".$row["location"];
        $password = $row["password"];
        $awskey = $row["awskey"];
        $awssecret = $row["awssecret"];
        $path = basename($row["path"]);

        putenv("AWS_ACCESS_KEY_ID=$awskey");
        putenv("AWS_SECRET_ACCESS_KEY=$awssecret");
        putenv("RESTIC_REPOSITORY=$location");
        putenv("RESTIC_PASSWORD=$password");

        $tags = array();
        foreach ($snapshots as $snapshotInfo) {
            if($snapshotInfo["id"] == $snapshot){
                $tags = $snapshotInfo["tags"];
            }
        }
        $snapshot = escapeshellarg($snapshot);

        chdir($resticDir);
        if(in_array("database",$tags)){ //Database backup
            exec("${exec_modifier}$path restore $snapshot -t . 2>&1",$output,$status);
            if(stripos(php_uname("s"),"Windows") === false){
                exec("chmod 777 backup.sql");
            }
            set_database("backup.sql");
            unlink("backup.sql");
            redirect("../user/logout");
        }else if(in_array("files",$tags)){ //Full backup
            $connectFolder = dirname(dirname(dirname(__DIR__)));
            set_time_limit(600);
            $connectFolder = escapeshellarg($connectFolder);
            exec("${exec_modifier}$path restore -t $connectFolder $snapshot 2>&1",$output,$status);
        }else{
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . "No matching tag found" . '</div>';
        }
        chdir(dirname(__DIR__));
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
  <h3>Restic Backup<?php echo $validSymbol; ?></h3>
</div>
<form method="POST" autocomplete="off">
  <div class="container-fluid">
  <div class="row"><h4 class="col-xs-12">Einstellungen</h4></div>
    <div class="row">
        <div class="col-md-4">
            <label>Restic Version:</label>
        </div>
        <div class="col-md-6">
            <select class="js-example-basic-single btn-block" name="path">
                <?php
                $entries = scandir($resticDir,SCANDIR_SORT_DESCENDING);
                foreach($entries as $entry){
                    if($entry == "." || $entry == ".." || $entry == "LICENSE")
                        continue;
                    $selected = (basename($currentPath) == $entry) ? "selected":"";
                    echo "<option $selected value='$resticDir/$entry'>".str_replace(".exe","",str_replace("_"," ",$entry))."</option>";
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
        $(function(){
        $(".select2-tagging").select2({
            tags: true
        })})
        </script>
              <div class="col-xs-12">
        <button type="submit" class="btn btn-warning">Übernehmen</button>
        <button type="submit" class="btn btn-warning" name="init" value="true">Init</button>
      </div>
    </div>
    <?php if($repositoryValid): ?>
    <br><hr><br><div class="row"><h4 class="col-xs-12">Backup</h4></div>
    <div class="row">
        <div class="col-xs-12">
            <button type="submit" class="btn btn-warning" name="backup-database" value="true">Backup Database</button>
            <button type="submit" class="btn btn-warning" name="backup-files" value="true">Backup Files (no Database)</button>
        </div>
    </div>
    <br><hr><br><div class="row"><h4 class="col-xs-12">Wiederherstellung</h4></div>
    <div class="row">
        <div class="col-md-4">
            <label>Snapshot:</label>
        </div>
        <div class="col-md-6">
            <select class="btn-block js-example-basic-single select2-file-database" name="snapshot">
                <?php 
                $snapshots = array_reverse(list_snapshots());
                foreach($snapshots as $snapshot){
                    if(in_array("database",$snapshot["tags"])){
                        $type = "database";
                    }else{
                        $type = "folder-open";
                    }
                    $id = $snapshot["id"];
                    $date = $snapshot["time"];
                    $time = explode("T",$date)[1];
                    $time = explode(".",$time)[0];
                    $date = explode("T",$date)[0];
                    
                    echo "<option data-icon='$type' value='$id'> $date $time</option>";
                }
                ?>
            </select>
            <script>
            function formatState (state) {
                if (!state.id) { return state.text; }
                var $state = $(
                    '<span><i class="fa fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
                );
                return $state;
            };
            $(function(){
                $(".select2-file-database").select2({
                templateResult: formatState,
                templateSelection: formatState
                });
            })      
            
            </script>
        </div>
        <div class="col-xs-12">
        <button type="submit" class="btn btn-warning" name="restore" value="true">Wiederherstellen</button>
            </div>
    </div>
    <?php endif;?>
    <div class="<?php echo !$status ? 'has-success' : 'has-error'; ?>">
        <?php if($output ?? false): ?>
        <label for="output">Restic Ausgabe:</label>
        <textarea class="form-control" readonly rows="<?php echo sizeof($output)<10?sizeof($output):10; ?>" id="output"><?php foreach ($output as $num => $row) {echo "$row"; if(sizeof($output)-1!=$num) echo "\n";}?></textarea>
        <?php endif; ?>
    </div>
  </div>
</form>
<?php include dirname(__DIR__) . '/footer.php'; ?>
