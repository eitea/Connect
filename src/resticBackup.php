<?php require 'header.php'; enableToCore($userID); ?>

<?php 
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
        $path = $row["path"];

        putenv("AWS_ACCESS_KEY_ID=$awskey");
        putenv("AWS_SECRET_ACCESS_KEY=$awssecret");
        putenv("RESTIC_REPOSITORY=$location");
        putenv("RESTIC_PASSWORD=$password");

        exec("$path init 2>&1",$output,$status);
        var_dump($path,$output,$status);
    }
}
$currentPath = $conn->query("SELECT path FROM resticconfiguration")->fetch_assoc()["path"];
$currentPassword = $conn->query("SELECT password FROM resticconfiguration")->fetch_assoc()["password"];
$currentAwskey = $conn->query("SELECT awskey FROM resticconfiguration")->fetch_assoc()["awskey"];
$currentAwssecret = $conn->query("SELECT awssecret FROM resticconfiguration")->fetch_assoc()["awssecret"];
$currentLocation = $conn->query("SELECT location FROM resticconfiguration")->fetch_assoc()["location"];

?>


<div class="page-header">
  <h3>Restic Einstellungen</h3>
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
                $resticDir = "plugins/restic/";
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
            <label>awskey:</label>
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control" name="awskey" value="<?php echo $currentAwskey;?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <label>awssecret:</label>
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
      </div>
    </div>
    <?php if($output): ?>
    <label for="output">Restic Ausgabe:</label>
    <textarea class="form-control" rows="<?php sizeof($output) ?>" id="output"><?php var_dump($output,$status); ?></textarea>
    <?php endif; ?>
  </div>
</form>
<?php include 'footer.php'; ?>
