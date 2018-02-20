<?php include dirname(__DIR__) . '/header.php';
enableToDSGVO($userID); ?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
if (empty($_GET['n']) || !in_array($_GET['n'], $available_companies)) { //eventually STRIKE
    $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Invalid Access.</strong> ' . $lang['ERROR_STRIKE'] . '</div>';
    include dirname(__DIR__) . '/footer.php';
    die();
}
?>
<div class="page-header"><h3><?php echo $lang['PROCEDURE_DIRECTORY']; ?>
        <div class="page-header-button-group"><button type="button" class="btn btn-default" data-toggle="modal" data-target="#add-app">+</button></div>
    </h3></div>
<?php
$cmpID = intval($_GET['n']);

if (isset($_POST['add_app']) && !empty($_POST['add_app_name']) && !empty($_POST['add_app_template'])) {
    $name = test_input($_POST['add_app_name']);
    $val = intval($_POST['add_app_template']);
    if ($name && $val) {
        $conn->query("INSERT INTO dsgvo_vv(templateID, name) VALUES ($val, '$name') ");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_ADD'] . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['ERROR_INVALID_CHARACTER'] . '</div>';
    }
}

$result = $conn->query("SELECT dsgvo_vv.id FROM dsgvo_vv, dsgvo_vv_templates WHERE dsgvo_vv.name='Basis' AND templateID = dsgvo_vv_templates.id AND dsgvo_vv_templates.companyID = $cmpID");
$row = $result->fetch_assoc();
?>
<div class="panel panel-default" style="margin:0 15px">
    <form method="POST">
        <div class="row">
            <div class="col-sm-6"><a href="vDetail?v=<?php echo $row['id']; ?>" class="btn btn-link"> Basis </a></div>
            <div class="col-sm-5">
                <select name="change_basic_template" class="js-example-basic-single">
                    <?php
                    $res = $conn->query("SELECT id, name FROM dsgvo_vv_templates WHERE companyID = $cmpID AND type = 'base' ");
                    while ($temp_row = $res->fetch_assoc()) {
                        echo '<option value="' . $temp_row['id'] . '">' . $temp_row['name'] . '</option>';
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
    <?php
    $result = $conn->query("SELECT dsgvo_vv.* FROM dsgvo_vv, dsgvo_vv_templates WHERE dsgvo_vv_templates.type='app' AND templateID = dsgvo_vv_templates.id AND dsgvo_vv_templates.companyID = $cmpID");
    while ($row = $result->fetch_assoc()) {
        echo '<div class="col-md-6"><div class="panel panel-default"><a href="vDetail?v=' . $row['id'] . '" class="btn btn-link">' . $row['name'] . '</a></div></div>';
    }
    ?>
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
                        while ($temp_row = $res->fetch_assoc()) {
                            echo '<option value="' . $temp_row['id'] . '">' . $temp_row['name'] . '</option>';
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
<?php include dirname(__DIR__) . '/footer.php'; ?>