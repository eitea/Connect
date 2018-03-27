<?php include dirname(__DIR__) . '/header.php'; enableToDSGVO($userID);?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
if(empty($_GET['n']) || !in_array($_GET['n'], $available_companies)){ //eventually STRIKE
    $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Invalid Access.</strong> '.$lang['ERROR_STRIKE'].'</div>';
    include dirname(__DIR__) . '/footer.php';
    die();
}?>
<div class="page-header-fixed">
<div class="page-header"><h3><?php echo $lang['PROCEDURE_DIRECTORY']; ?>
<div class="page-header-button-group">
    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#add-app">+</button>
    <button type="button" class="btn btn-default" id="listTemplates"><?php echo $lang['MANAGE_TEMPLATES'] ?></button>
</div>
</h3></div>
</div>
<div class="page-content-fixed-130"> 
<?php
$cmpID = intval($_GET['n']);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!empty($_POST['delete_template'])){
        $val = intval($_POST['delete_template']);
        $conn->query("DELETE FROM dsgvo_vv_templates WHERE id = $val");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
        }        
    }
    if(!empty($_POST['duplicate_template']) && !empty($_POST['duplicate_template_name'])){
        $val = intval($_POST['duplicate_template']);
        $name = test_input($_POST['duplicate_template_name']);
        $conn->query("INSERT INTO dsgvo_vv_templates (companyID, name, type) SELECT companyID, '$name', type FROM dsgvo_vv_templates WHERE id = $val");
        $templateID = $conn->insert_id;        
        $conn->query("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr, opt_status) SELECT $templateID, opt_name, opt_descr, opt_status FROM dsgvo_vv_template_settings WHERE templateID = $val");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
        }
    }
    if(isset($_POST['add_template']) && !empty($_POST['add_name']) && $_POST['add_name'] != 'Default' && !empty($_POST['add_type'])){
        $type = ($_POST['add_type']);
        $name = test_input($_POST['add_name']);
        if($type == 'base' || $type == 'app'){
            $conn->query("INSERT INTO dsgvo_vv_templates (companyID, name, type) VALUES($cmpID, '$name', '$type')");
            $templateID = $conn->insert_id;
            $conn->query("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr) VALUES($templateID, 'DESCRIPTION', '')");
            if($conn->error){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            } else {
                echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
            }
        } else {
            $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Invalid Access.</strong> '.$lang['ERROR_STRIKE'].'</div>';
        }
    } elseif(isset($_POST['add_template'])) {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'. Default nicht erlaubt.</div>';
    }
}

if(isset($_POST['add_app']) && !empty($_POST['add_app_name']) && !empty($_POST['add_app_template'])){
    $name = test_input($_POST['add_app_name']);
    $val = intval($_POST['add_app_template']);
    if($name && $val){
        $conn->query("INSERT INTO dsgvo_vv(templateID, name) VALUES ($val, '$name') ");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
        }
    } else {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_CHARACTER'].'</div>';
    }
}

if(isset($_POST["change_template"])){
    $vv_id = intval($_POST["vv_id"]);
    $template_id = intval($_POST["template_id"]);
    if($vv_id && $template_id){
        $conn->query("UPDATE dsgvo_vv SET templateID = $template_id WHERE id = $vv_id");
        if($conn->error){
            showError($conn->error);
        }else{
            showSuccess($lang['OK_SAVE']);
        }
    }else{
        showError($lang['ERROR_INVALID_CHARACTER']);
    }
}

$result = $conn->query("SELECT dsgvo_vv.id FROM dsgvo_vv, dsgvo_vv_templates WHERE dsgvo_vv.name='Basis' AND templateID = dsgvo_vv_templates.id AND dsgvo_vv_templates.companyID = $cmpID");
$row = $result->fetch_assoc();
?>
<div class="panel panel-default" style="margin:0 15px">
    <form method="POST">
        <div class="row">
            <div class="col-sm-6"><a href="vDetail?v=<?php echo $row['id']; ?>" class="btn btn-link"> Stammblatt </a></div>
            <div class="col-sm-5">
                <select name="change_basic_template" class="js-example-basic-single">
                <?php
                $res = $conn->query("SELECT id, name FROM dsgvo_vv_templates WHERE companyID = $cmpID AND type = 'base' ");
                while($temp_row = $res->fetch_assoc()){
                    echo '<option value="'.$temp_row['id'].'">'.$temp_row['name'].'</option>';
                }
                ?>
                </select>
            </div>
            <div class="col-xs-1">
                <button type="submit" class="btn btn-warning"><i class="fa fa-floppy-o"></i></button>
            </div>
        </div>
    </form>
</div>

<div class="row">
    <div class="col-md-11 col-md-offset-1">
        <div class="row">
            <?php
                $result = $conn->query("SELECT dsgvo_vv.id, dsgvo_vv.name, dsgvo_vv.templateID FROM dsgvo_vv, dsgvo_vv_templates WHERE dsgvo_vv_templates.type='app' AND templateID = dsgvo_vv_templates.id AND dsgvo_vv_templates.companyID = $cmpID ORDER BY dsgvo_vv.id");
                while($row = $result->fetch_assoc()){
                    $id = $row['id'];
                    $name = $row['name'];
                    $templateID = $row['templateID'];
                    ?>
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="row">
                                    <div class="col-md-7">
                                        <a href="vDetail?v=<?php echo $id ?>" class="btn btn-link"><?php echo $name; ?></a>
                                    </div>
                                    <div class="col-md-5">
                                        <form method="POST">
                                            <input type="hidden" name="vv_id" value="<?php echo $id; ?>" />
                                            <div class="input-group">
                                                <select name="template_id" class="js-example-basic-single">
                                                    <?php
                                                    $select_result = $conn->query("SELECT id, name FROM dsgvo_vv_templates WHERE companyID = $cmpID AND type = 'app'");
                                                    while($select_row = $select_result->fetch_assoc()){
                                                        $select_id = $select_row["id"];
                                                        $select_name = $select_row["name"];
                                                        $selected = $select_id == $templateID?"selected":"";
                                                        echo "<option $selected value='$select_id'>$select_name</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <div class="input-group-btn">
                                                    <button type="submit" class="btn btn-default" name="change_template" value="true"><i class="fa fa-floppy-o"></i></button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            ?>
        </div>
    </div>
</div>

<div id="add-app" class="modal fade">
  <div class="modal-dialog modal-content modal-sm">
    <form method="POST">
        <div class="modal-header h4">Application Hinzufügen</div>
        <div class="modal-body">
            <div class="row">
                <label>Name</label>
                <input type="text" name="add_app_name" class="form-control" />
                <br>
                <label>Template</label>
                <select name="add_app_template" class="js-example-basic-single">
                <?php
                $res = $conn->query("SELECT id, name FROM dsgvo_vv_templates WHERE companyID = $cmpID AND type = 'app' ");
                while($temp_row = $res->fetch_assoc()){
                    echo '<option value="'.$temp_row['id'].'">'.$temp_row['name'].'</option>';
                }
                ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning" name="add_app"><?php echo $lang['SAVE']; ?></button>
        </div>
    </form>
  </div>
</div>
</div>
<div id="currentAjaxModal"></div>
<script>
    function setCurrentModal(data, type, url){
        $.ajax({
            url: url,
            data: data,
            type: type,
            success : function(resp){
                $("#currentAjaxModal").html(resp);
            },
            error : function(resp){console.error(resp)},
            complete: function(resp){
                onModalLoad();
                $("#currentAjaxModal .toggle-on-load-modal").modal('show');
            }
        });
    }
    $("#listTemplates").click(function(){
        setCurrentModal({n:<?php echo $cmpID; ?>},'get', 'ajaxQuery/AJAX_dsgvo_vv_templates.php')
    })
    function onModalLoad(){
        onPageLoad()
    }
</script>
<?php include dirname(__DIR__) . '/footer.php'; ?>