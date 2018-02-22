<?php include dirname(__DIR__) . '/header.php';?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; 

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['addConfig'])){
        $server = $_POST['server'];
        $aKey = $_POST['aKey'];
        $sKey = $_POST['sKey'];
        require dirname(dirname(__DIR__)) . "/misc/useS3Config.php";
        if (isset($_POST['server'])) {
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
                if(!addS3Config($server,$aKey,$sKey)){
                    throw new S3Exception("Ups! Something went wrong");
                }
            } catch(Exception $e) {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
            }
        }
    }
}

$configs = $conn->query("SELECT * FROM archiveconfig");?>
<div class="page-header"><h3 id="title" ><?php echo $lang['OPTIONS'] ?>
    <div class="page-header-button-group">
        <button class="btn btn-default" type="button" id="newConfig" data-target="#new-config" data-toggle="modal"><i class="fa fa-plus"></i></button>
    </div>
  </h3>
</div>
    <table class="table" id="configTable">
        <thead>
            <tr>
                <td><label>Addresse</label></td>
                <td><label>Key</label></td>
                <td><label>Active</label></td>
                <td></td>
            </tr>
        </thead>
        <tbody id="tableContent"><?php 
                while($row = $configs->fetch_assoc()){
                    $checked = '';
                    if($row['isActive']=="TRUE") $checked = 'checked';
                    echo '<tr>';
                    echo '<td>'.$row['endpoint'].'</td>';
                    echo '<td>'.$row['awskey'].'</td>';
                    echo '<td><input type="radio" name="active" value="'.$row['id'].'" '.$checked.'></input></td>';
                    echo '<td></td>';
                    echo '</tr>';
                }
            ?></tbody>
    </table>

<form method="POST">
    <div class="modal fade" id="new-config">
        <div class="modal-dialog modal-content modal-sm">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
            <div class="modal-body">
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
    $("[name='active']").on("click",function(event){
        console.log(event.target.value);
        $.post("ajaxQuery/AJAX_changeActiveS3.php",{
            id: event.target.value
        },function(data){
            console.log(data);
        });
    });
</script>
<?php include dirname(__DIR__) . '/footer.php'; ?>