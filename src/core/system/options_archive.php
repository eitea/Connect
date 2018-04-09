<?php include dirname(dirname(__DIR__)) . '/header.php';?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['addConfig'])){
        if (!empty($_POST['server'])) {
            $server = $_POST['server'];
            $aKey = $_POST['aKey'];
            $sKey = $_POST['sKey'];
            $name = test_input($_POST['name']);
            try{
                $credentials = array('key' => $aKey, 'secret' => $sKey);
                $testconfig = array(
                    'version' => 'latest',
                    'region' => '',
                    'endpoint' => $server,
                    'use_path_style_endpoint' => true,
                    'credentials' => $credentials
                );
                $test = new Aws\S3\S3Client($testconfig);
                $test->listBuckets();

                $conn->query("INSERT INTO archiveconfig (endpoint,awskey,secret,isActive,name) VALUES ('$server','$key','$secret','$active','$name')");
            } catch(Exception $e) {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
            }
        }
    } elseif(isset($_POST['deleteConfig'])){
        $id = $_POST['deleteConfig'];
        $isActive = $conn->query("SELECT isActive FROM archiveconfig WHERE id = $id");
        if($isActive && $isActive->fetch_assoc()['isActive']==="TRUE"){
            $conn->query("UPDATE archiveconfig SET isActive = 'TRUE' WHERE isActive = 'FALSE' LIMIT 1;");
        }
        $conn->query("DELETE FROM archiveconfig WHERE id = $id");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        }
    }
}
?>
<div class="page-header"><h3 id="title" ><?php echo $lang['OPTIONS'] ?>
    <div class="page-header-button-group">
        <button class="btn btn-default" type="button" id="newConfig" data-target="#new-config" data-toggle="modal"><i class="fa fa-plus"></i></button>
    </div>
    </h3>
</div>
<table class="table" id="configTable">
    <thead>
        <tr>
            <td><label>Name</label></td>
            <td><label>Addresse</label></td>
            <td><label>Key</label></td>
            <td><label>Active</label></td>
            <td></td>
        </tr>
    </thead>
    <tbody id="tableContent">
        <?php
        $configs = $conn->query("SELECT id, name, endpoint, awskey FROM archiveconfig");
        while($row = $configs->fetch_assoc()){
            $checked = '';
            if($row['isActive']=="TRUE") $checked = 'checked';
            echo '<tr>';
            echo '<td>'.$row['name'].'</td>';
            echo '<td>'.$row['endpoint'].'</td>';
            echo '<td>'.$row['awskey'].'</td>';
            echo '<td><input type="radio" name="active" value="'.$row['id'].'" '.$checked.'></input></td>';
            echo '<td><form method="POST" id="confirmDelete"><button name="deleteConfig" class="btn btn-default" type="submit" value="'.$row['id'].'" ><i class="fa fa-trash" /></button></form></td>';
            echo '</tr>';
        }
        ?>
    </tbody>
</table>

<form method="POST">
    <div class="modal fade" id="new-config">
        <div class="modal-dialog modal-content modal-sm">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
            <div class="modal-body">
                <div class="col-md-12"><label>Name</label><input class="form-control" name="name"><br></div>
                <div class="col-md-12"><label>Server</label><input class="form-control" name="server"><br></div>
                <div class="col-md-12"><label>Access Key</label><input class="form-control" name="aKey"><br></div>
                <div class="col-md-12"><label>Secret Key</label><input class="form-control" name="sKey"><br></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="addConfig"><?php echo $lang['ADD']; ?></button>
            </div>
        </div>
    </div>
</form>

<script>
//havent you people ever heard of anonymous functions?
$('#confirmDelete').on('submit', function(){
    return confirm("Ary you sure you want to delete this Configuration ?");
});
</script>
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
