<?php include dirname(__DIR__) . '/header.php'; enableToDSGVO($userID);?>
<?php
if(empty($_GET['v'])){
    echo "Invalid Access.";
    include dirname(__DIR__) . '/footer.php';
    die();
}
$vvID = intval($_GET['v']);

$result = $conn->query("SELECT dsgvo_vv.name, companyID, dsgvo_vv_templates.type, dsgvo_vv_templates.id AS templateID
FROM dsgvo_vv, dsgvo_vv_templates WHERE dsgvo_vv.id = $vvID AND dsgvo_vv_templates.id = templateID");
if(!$result || $result->num_rows < 1 || !($vv_row = $result->fetch_assoc()) || !in_array($vv_row['companyID'], $available_companies)){
    echo $conn->error;
    echo "<br>Invalid Access.";
    include dirname(__DIR__) . '/footer.php';
    die();
}
$templateID = $vv_row['templateID'];
$doc_type = 'BASE';
if($vv_row['type'] == 'app'){ $doc_type = 'APP'; }

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $stmt_update_setting = $conn->prepare("UPDATE dsgvo_vv_settings SET setting = ? WHERE id = ?");
    $stmt_update_setting->bind_param("si", $setting_encrypt, $valID);
    $stmt_insert_setting = $conn->prepare("INSERT INTO dsgvo_vv_settings(vv_id, setting_id, setting, category) VALUES($vvID, ?, ?, ?)");
    $stmt_insert_setting->bind_param("iss", $setID, $setting_encrypt, $cat);
}
function getSettings($like, $mults = false){
    global $conn;
    global $vvID;
    global $templateID;
    global $userID;
    global $privateKey;
    $result = $conn->query("SELECT setting, opt_name, opt_descr, category, dsgvo_vv_template_settings.id, dsgvo_vv_settings.id AS valID
    FROM dsgvo_vv_template_settings LEFT JOIN dsgvo_vv_settings ON setting_id = dsgvo_vv_template_settings.id AND vv_ID = $vvID WHERE opt_name LIKE '$like' AND templateID = $templateID");
    echo $conn->error;
    $settings = array();
    while($row = $result->fetch_assoc()){
        $settings[$row['opt_name']]['descr'] = $row['opt_descr'];
        $settings[$row['opt_name']]['id'] = $row['id'];
        if($mults){
            $settings[$row['opt_name']]['setting'][] = secure_data('DSGVO', $row['setting'], 'decrypt', $userID, $privateKey);
            $settings[$row['opt_name']]['valID'][] = $row['valID'];
            $settings[$row['opt_name']]['category'][] = $row['category'];
        } else {
            $settings[$row['opt_name']]['setting'] = secure_data('DSGVO', $row['setting'], 'decrypt', $userID, $privateKey);
            $settings[$row['opt_name']]['valID'] = $row['valID'];
        }
    }
    return $settings;
}
?>

<form method="POST">
<div class="page-header"><h3><?php echo $vv_row['name'].' '.$lang['PROCEDURE_DIRECTORY']; ?><div class="page-header-button-group">
<button type="submit" class="btn btn-default blinking"><i class="fa fa-floppy-o"></i></button>
</div></h3></div>

<?php
$settings = getSettings('DESCRIPTION');
if(isset($settings['DESCRIPTION'])):
    if(isset($_POST['DESCRIPTION'])){
        $setting = strip_tags($_POST['DESCRIPTION']);
        $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
        $valID = $settings['DESCRIPTION']['valID'];
        if($valID){
            $stmt_update_setting->execute();
        } else {
            $setID = $settings['DESCRIPTION']['id'];
            $stmt_insert_setting->execute();
        }
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            $settings['DESCRIPTION']['setting'] = $setting;
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        }
    }
?>
    <div class="col-md-6">
        <div class="panel panel-default">
            <?php
            if($doc_type == 'BASE'):
            $result = $conn->query("SELECT name, address, phone, mail, companyPostal, companyCity FROM companyData WHERE id = ".$vv_row['companyID']);
            $row = $result->fetch_assoc();
            ?>
            <div class="panel-heading">Firmendaten</div>
            <div class="panel-body">
                <div class="col-sm-6 bold">Name der Firma</div><div class="col-sm-6 grey"><?php echo $row['name']; ?></div>
                <div class="col-sm-6 bold">Straße</div><div class="col-sm-6 grey"><?php echo $row['address']; ?></div>
                <div class="col-sm-6 bold">Ort</div><div class="col-sm-6 grey"><?php echo $row['companyCity']; ?></div>
                <div class="col-sm-6 bold">PLZ</div><div class="col-sm-6 grey"><?php echo $row['companyPostal']; ?></div>
                <div class="col-sm-6 bold">Telefon</div><div class="col-sm-6 grey"><?php echo $row['phone']; ?></div>
                <div class="col-sm-6 bold">E-Mail</div><div class="col-sm-6 grey"><?php echo $row['mail']; ?></div>
            </div>
            <?php else: ?>
            <div class="panel-heading">Kurze Beschreibung der Applikation, bzw. den Zweck dieser Applikation <?php echo mc_status(); ?></div>
            <div class="panel-body">
                <textarea name="DESCRIPTION" style='resize:none' class="form-control" rows="5"><?php echo $settings['DESCRIPTION']['setting']; ?></textarea>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-body">
        <?php
        $settings = getSettings('GEN_%');
        foreach($settings as $key => $val){
            if(isset($_POST[$key])){
                $val['setting'] = $setting = strip_tags($_POST[$key]);
                $valID = $val['valID'];
                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                if($valID){
                    $stmt_update_setting->execute();
                } else {
                    $setID = $val['id'];
                    $stmt_insert_setting->execute();
                }
            }
            echo '<div class="row">';
            echo '<div class="col-sm-6 bold">'.$val['descr'].' '.mc_status().'</div>';
            echo '<div class="col-sm-6 grey"><input type="text" class="form-control" maxlength="700" name="'.$key.'" value="'.$val['setting'].'"/></div>';
            echo '</div>';
        }
        ?>
        </div>
    </div>
</div>
<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading">Generelle organisatorische und technische Maßnahmen zum Schutz der personenbezogenen Daten</div>
        <div class="panel-body">
            <?php
            $settings = getSettings('MULT_OPT_%');
            foreach($settings as $key => $val){
                if(isset($_POST[$key])){
                    $val['setting'] = $setting = intval($_POST[$key]);
                    $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                    $valID = $val['valID'];
                    if($valID){
                        $stmt_update_setting->execute();
                    } else {
                        $setID = $val['id'];
                        $stmt_insert_setting->execute();
                    }
                }
                echo '<div class="row">';
                echo '<div class="col-sm-6 bold">'.$val['descr'].'</div>';
                $checked = $val['setting'] == 1 ? 'checked' : '';
                echo '<div class="col-sm-2 grey"><label><input type="radio" '.$checked.' name="'.$key.'" value="1" />Erfüllt</label></div>';
                $checked = $val['setting'] == 2 ? 'checked' : '';
                echo '<div class="col-sm-2 grey"><label><input type="radio" '.$checked.' name="'.$key.'" value="2"/>Nicht Erfüllt</label></div>';
                $checked = $val['setting'] == 3 ? 'checked' : '';
                echo '<div class="col-sm-2 grey"><label><input type="radio" '.$checked.' name="'.$key.'" value="3"/>N/A</label></div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>
<?php
$settings = getSettings('EXTRA_%');
if(isset($settings['EXTRA_DVR'])){
    echo '<div class="col-md-7">';
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">'.$settings['EXTRA_DVR']['descr'].' '.mc_status().'</div>';
    echo '<div class="row"><div class="col-sm-6 bold">DVR-Nummer</div><div class="col-sm-6"><input type="text" name="EXTRA_DVR" value="'.$settings['EXTRA_DVR']['setting'].'" class="form-control"></div></div>';
    echo '<div class="row"><div class="col-sm-6 bold">DAN-Nummer</div><div class="col-sm-6"><input type="text" name="EXTRA_DVR" value="'.$settings['EXTRA_DVR']['setting'].'" class="form-control"></div></div>';
    echo '</div></div>';
}
if(isset($settings['EXTRA_FOLGE'])){
    echo '<div class="col-md-7">';
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">'.$settings['EXTRA_FOLGE']['descr'].' '.mc_status().'</div>';
    echo '<div class="row"><div class="col-sm-2"><input type="radio" name="EXTRA_FOLGE_CHOICE" value="1">Ja</div><div class="col-sm-2"><input type="radio" name="EXTRA_FOLGE_CHOICE" value="0">Nein</div></div>';
    echo '<div class="row"><div class="col-sm-6 bold">Wenn Ja, wann?</div><div class="col-sm-6"><input type="text" name="EXTRA_FOLGE_DATE" class="form-control datepicker"></div></div>';
    echo '<div class="row"><div class="col-sm-6 bold">Wenn Nein, warum?</div><div class="col-sm-6"><input type="text" name="EXTRA_FOLGE_REASON" class="form-control"></div></div>';
    echo '</div></div>';
}
if(isset($settings['EXTRA_DOC'])){
    echo '<div class="col-md-7">';
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">'.$settings['EXTRA_DOC']['descr'].' '.mc_status().'</div>';
    echo '<div class="row"><div class="col-sm-2"><input type="radio" name="EXTRA_DOC_CHOICE" value="1">Ja</div><div class="col-sm-2"><input type="radio" name="EXTRA_DOC_CHOICE" value="0">Nein</div></div>';
    echo '<div class="row"><div class="col-sm-6 bold">Wo befindet sich diese?</div><div class="col-sm-6"><input type="text" name="EXTRA_DOC" class="form-control"></div></div>';
    echo '</div></div>';
}
?>

<?php if($doc_type == 'APP'): ?>
<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading">Auflistung der verarbeiteten Datenfelder und deren Übermittlung</div>
        <div class="panel-body">
            <?php
            if(!empty($_POST['delete_cat'])){
                $conn->query("DELETE FROM dsgvo_vv_settings WHERE id = ".intval($_POST['delete_cat']));
                echo $conn->error;
            }
            $str_heads = $space = $space_key = '';
            $heading = getSettings('APP_HEAD_%', true);
            foreach($heading as $key => $val){
                if($val['setting'][0]){
                    $str_heads .= '<th><div class="btn-group"><button type="button" class="btn btn-link" data-toggle="dropdown">'.$val['setting'][0].'</button>
                    <ul class="dropdown-menu"><li><button type="submit" class="btn btn-link" name="delete_cat" value="'.$val['valID'][0].'">Löschen</button></li></ul></div></th>';
                } else {
                    $space_key = !$space ? $key : $space_key;
                    $space = !$space ? $val['id'] : $space;
                    unset($heading[$key]);
                }
            }
            //no other sane choice for the backend to be but here
            if($space && isset($_POST['add_category']) && !empty($_POST['add_category_name'])){
                $setID = $space;
                $setting = test_input($_POST['add_category_name']);
                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                $cat = test_input($_POST['add_category_mittlung']);
                $stmt_insert_setting->execute();
                if($conn->error){
                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
                } else {
                    $heading[$space_key] = array('id' => $conn->insert_id, 'category' => array());
                    $str_heads .= '<th><div class="btn-group"><button type="button" class="btn btn-link" data-toggle="dropdown">'.$setting.'</button>
                    <ul class="dropdown-menu"><li><button type="submit" class="btn btn-link" name="delete_cat" value="'.$val['valID'][0].'">Löschen</button></li></ul></div></th>';
                }
            } elseif(!$space){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Kein Platz mehr.</div>';
            }
            ?>
            <table class="table table-condensed">
            <thead><tr>
            <th>Gruppierung</th>
            <th>Nr.</th>
            <th>Datenkategorien der gesammelten personenbezogenen Daten</th>
            <th>Besondere Datenkategorie iSd Art 9 DSGVO</th>
            <?php echo $str_heads; ?>
            <th><a data-toggle="modal" data-target="#add-cate" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></a></th>
            </tr></thead>
            <?php
            $settings = getSettings('APP_GROUP_%');
            foreach($settings as $key => $val){
                $i = 1;
                $cats = getSettings('APP_CAT_'.ltrim($key, 'APP_GROUP_').'_%');
                echo '<tr><td rowspan="'.(count($cats) +1).'" >'.$val['descr'].'</td></tr>';
                foreach($cats as $catKey => $catVal){
                    if($_SERVER['REQUEST_METHOD'] == 'POST'){
                        $valID = $catVal['valID'];
                        if(!empty($_POST[$catKey])){
                            $catVal['setting'] = $setting = '1';
                            $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                            if($valID){ //update to true if checked and exists
                                $stmt_update_setting->execute();
                            } else { //insert with true if checked and not exists
                                $cat = '';
                                $setID = $catVal['id'];
                                $stmt_insert_setting->execute();
                            }
                        } elseif($valID && $catVal['setting']) { //set to false only if not checked, exists and saved as true (anything else is false anyways)
                            $catVal['setting'] = $setting = '0';
                            $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                            $stmt_update_setting->execute();
                        }
                    }
                    echo '<tr>';
                    echo '<td>'.$i++.'</td>';
                    echo '<td>'.$catVal['descr'].'</td>';
                    $checked = $catVal['setting'] ? 'checked' : '';
                    echo '<td><input type="checkbox" '.$checked.' name="'.$catKey.'" value="1" /> Trifft Zu</td>';

                    foreach($heading as $headKey => $headVal){
                        $j = array_search($catKey, $headVal['category']); //$j = numeric index
                        $checked = ($j && $headVal['setting'][$j]) ? 'checked' : '';
                        if($_SERVER['REQUEST_METHOD'] == 'POST'){
                            if(!empty($_POST[$headKey.'_'.$catKey])){
                                $setting = '1';
                                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                                $checked = 'checked';
                                if($j){
                                    $valID = $headVal['valID'][$j];
                                    $stmt_update_setting->execute();
                                } else {
                                    $cat = $catKey;
                                    $setID = $headVal['id'];
                                    $stmt_insert_setting->execute();
                                }
                            } elseif($j && $headVal['setting'][$j]){
                                $valID = $headVal['valID'][$j];
                                $setting = '0';
                                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                                $checked = '';
                                $stmt_update_setting->execute();
                                echo $stmt_update_setting->error;
                            }
                        }
                        echo '<td class="text-center"><input type="checkbox" '.$checked.' name="'.$headKey.'_'.$catKey.'" value="1" ></td>';
                    }
                    echo '<td></td>';
                    echo '</tr>';
                }
            }
            ?>
            </table>
        </div>
    </div>
</div>

<div id="add-cate" class="modal fade">
  <div class="modal-dialog modal-content modal-md">
	<div class="modal-header h4">Neue Kategorie Option</div>
	<div class="modal-body">
        <label>Überschrift</label>
        <input type="text" name="add_category_name" class="form-control" />
        <br>
        <div class="row">
            <div class="col-sm-4"><label><input type="radio" name="add_category_mittlung" value="heading1" checked />Übermittlung</label></div>
            <div class="col-sm-4"><label><input type="radio" name="add_category_mittlung" value="heading2" />Verarbeitung</label></div>
        </div>
    </div>
	<div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="add_category"><?php echo $lang['SAVE']; ?></button>
	</div>
  </div>
</div>
<?php endif;?>
</form>
<?php include dirname(__DIR__) . '/footer.php'; ?>
