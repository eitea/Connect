<?php include dirname(__DIR__) . '/header.php'; enableToDSGVO($userID); ?>

<div class="page-header-fixed">
<div class="page-header"><h3>Template <?php echo $lang['EDIT']; ?> <div class="page-header-button-group">
<button type="submit" form="main-form" class="btn btn-default" name="save_all"><i class="fa fa-floppy-o"></i></div></h3></div>
</div>
<div class="page-content-fixed-130">
<?php
if(empty($_GET['t'])){
    echo "Invalid Access.";
    include dirname(__DIR__) . '/footer.php';
    die();
}
$tmpID = intval($_GET['t']);
$result = $conn->query("SELECT name, type, companyID FROM dsgvo_vv_templates WHERE id = $tmpID");
if(!$result || !($row = $result->fetch_assoc()) || !in_array($row['companyID'], $available_companies)){
    echo "Invalid Access.";
    include dirname(__DIR__) . '/footer.php';
    die();
}

$template_name = $row['name'];
$template_type = $row['type'];

$stmt_insert_vv_log = $conn->prepare("INSERT INTO dsgvo_vv_logs (user_id,short_description,long_description) VALUES ($userID,?,?)");
showError($conn->error);
$stmt_insert_vv_log->bind_param("ss", $stmt_insert_vv_log_short_description, $stmt_insert_vv_log_long_description);
function insertVVLog($short,$long){
    global $stmt_insert_vv_log;
    global $stmt_insert_vv_log_short_description;
    global $stmt_insert_vv_log_long_description;
    $stmt_insert_vv_log_short_description = $short;
    $stmt_insert_vv_log_long_description = $long;
    $stmt_insert_vv_log->execute();
    showError($stmt_insert_vv_log->error);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['save_all']) && !empty($_POST['template_name'])){
        $val = test_input($_POST['template_name']);
        $conn->query("UPDATE dsgvo_vv_templates SET name = '$val' WHERE id = $tmpID");
        insertVVLog("UPDATE","Change template name to '$val' in template $tmpID");
    }
    if(!empty($_POST['add_setting']) && !empty($_POST[test_input($_POST['add_setting'])])){
        $setting = test_input($_POST['add_setting']);
        $descr = test_input($_POST[$setting]);
        $conn->query("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr) VALUES ($tmpID, '$setting', '$descr')");
        insertVVLog("INSERT","Add new field (name: '$setting', description: '$descr') in template $tmpID");        
        if($conn->error){
            showError($conn->error);
        } else {
            showSuccess($lang['OK_ADD']);
        }
    }
}
?>

<form method="POST" id="main-form">
<div class="col-sm-6">
    <label>Name</label>
    <input type="text" name="template_name" value="<?php echo $template_name; ?>" class="form-control" /><br><br>
</div>

<div class="col-md-12">
    <label>Einfache Text-Optionen</label><br>
    <div class="panel panel-default">
        <div class="panel-body"><br>
        <?php
        $i = 1;
        $result = $conn->query("SELECT opt_name, opt_descr FROM dsgvo_vv_template_settings WHERE templateID = $tmpID AND opt_name LIKE 'GEN_%'");
        while($row = $result->fetch_assoc()){
            $num = ltrim($row['opt_name'], 'GEN_');
            if($num == $i) $i++;
            echo '<div class="col-sm-6"><input type="text" class="form-control" maxlength="350" name="'.$row['opt_name'].'" value="'.$row['opt_descr'].'"/><br></div>';
        }
        echo '<div class="col-xs-12"><label>Neu</label></div><div class="col-sm-11"><input type="text" class="form-control" maxlength="350" name="GEN_'.$i.'"/></div>
        <div class="col-sm-1"><button type="submit" name="add_setting" value="GEN_'.$i.'" class="btn btn-warning"><i class="fa fa-plus"></i></button></div>';
        ?>
        </div>
    </div><br>
</div>

<div class="col-md-12">
    <label>Multiple-Choice Optionen</label>
    <div class="panel panel-default">
        <div class="panel-heading">Generelle organisatorische und technische Maßnahmen zum Schutz der personenbezogenen Daten</div>
        <div class="panel-body">
        <?php
        $i = 1;
        $result = $conn->query("SELECT opt_name, opt_descr FROM dsgvo_vv_template_settings WHERE templateID = $tmpID AND opt_name LIKE 'MULT_OPT_%'");
        while($row = $result->fetch_assoc()){
            $num = ltrim($row['opt_name'], 'MULT_OPT_');
            if($num == $i) $i++;
            echo '<div class="col-sm-6"><input type="text" class="form-control" maxlength="350" name="'.$row['opt_name'].'" value="'.$row['opt_descr'].'"/><br></div>';
        }
        echo '<div class="col-xs-12"><label>Neu</label></div><div class="col-sm-11"><input type="text" class="form-control" maxlength="350" name="MULT_OPT_'.$i.'"/></div>
        <div class="col-sm-1"><button type="submit" name="add_setting" value="MULT_OPT_'.$i.'" class="btn btn-warning"><i class="fa fa-plus"></i></button></div>';
        ?>
        </div>
    </div>
</div>

<?php if($template_type == 'app'): ?>
    <div class="col-md-12">
        <label>Extra <?php echo $lang['VV_TEMPLATE_TYPES']['app']; ?> Abfragen</label>
        <div class="panel panel-default">
            <div class="panel-body">
            <?php
            $setting = array();
            $result = $conn->query("SELECT opt_name, opt_descr FROM dsgvo_vv_template_settings WHERE templateID = $tmpID AND opt_name LIKE 'EXTRA_%'");
            while($row = $result->fetch_assoc()){
                $setting[$row['opt_name']] = $row['opt_descr'];
            }
            $checked = isset($setting['EXTRA_DVR']) ? 'checked' : '';
            echo '<div class="col-sm-6"><label><input type="checkbox" '.$checked.' name="EXTRA_DVR" value="1" /> Angaben zum Datenverarbeitungsregister (DVR)</label></div>';
            $checked = isset($setting['EXTRA_FOLGE']) ? 'checked' : '';
            echo '<div class="col-sm-6"><label><input type="checkbox" '.$checked.' name="EXTRA_FOLGE" value="1" /> Datenschutz-Folgeabschätzung</label></div>';
            $checked = isset($setting['EXTRA_DOC']) ? 'checked' : '';
            echo '<div class="col-sm-6"><label><input type="checkbox" '.$checked.' name="EXTRA_DOC" value="1" /> Dokumentation dieser Applikation</label></div>';
            ?>
            </div>
        </div><br>
    </div>

    <div class="col-md-12">
        <label>Datenmatrix</label>
        <div class="panel panel-default">
            <div class="panel-heading">Auflistung der verarbeiteten Datenfelder und deren Übermittlung</div>
            <div class="panel-body">
                Aus den <a href="../system/data-matrix"><?php echo $lang['DATA_MATRIX']; ?> Einstellungen</a> übernommen.
            </div>
        </div>
    </div>
<?php endif; ?>
</form>
</div>
<?php include dirname(__DIR__) . '/footer.php'; ?>