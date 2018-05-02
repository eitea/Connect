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
$company = $vv_row['companyID'];
$matrix_result = $conn->query("SELECT id FROM dsgvo_vv_data_matrix WHERE companyID = $company");
if($matrix_result){
    if($matrix_result->num_rows === 0){
        showError("Diese Firma hat keine Matrix in den Einstellungen. Zum Erstellen <a href='data-matrix'>hier klicken</a>.");
    }
    $matrixID = $matrix_result->fetch_assoc()["id"];
}else{
    showError($conn->error);
}

$stmt_insert_vv_log = $conn->prepare("INSERT INTO dsgvo_vv_logs (user_id,short_description,long_description,scope) VALUES ($userID,?,?,?)");
showError($conn->error);
$last_encryption_error = "";
$stmt_insert_vv_log->bind_param("sss", $stmt_insert_vv_log_short_description, $stmt_insert_vv_log_long_description, $stmt_insert_vv_log_scope);
function insertVVLog($short,$long){
    global $stmt_insert_vv_log;
    global $stmt_insert_vv_log_short_description;
    global $stmt_insert_vv_log_long_description;
    global $stmt_insert_vv_log_scope;
    global $userID;
    global $privateKey;
    global $last_encryption_error;
    $stmt_insert_vv_log_short_description = secure_data('DSGVO', $short, 'encrypt', $userID, $privateKey);
    $stmt_insert_vv_log_long_description = secure_data('DSGVO', $long, 'encrypt', $userID, $privateKey, $encryptionError);
    $stmt_insert_vv_log_scope = secure_data('DSGVO', "VV", 'encrypt', $userID, $privateKey, $encryptionError);
    if($encryptionError){
        $last_encryption_error = showError($encryptionError, true); // only show last error because consecutive errors are usually the same
    }
    $stmt_insert_vv_log->execute();
    showError($stmt_insert_vv_log->error);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $stmt_update_setting = $conn->prepare("UPDATE dsgvo_vv_settings SET setting = ? WHERE id = ?");
    $stmt_update_setting->bind_param("si", $setting_encrypt, $valID);
    $stmt_insert_setting = $conn->prepare("INSERT INTO dsgvo_vv_settings(vv_id, setting_id, setting, category) VALUES($vvID, ?, ?, ?)");
    $stmt_insert_setting->bind_param("iss", $setID, $setting_encrypt, $cat);

    $stmt_insert_setting_client = $conn->prepare("INSERT INTO dsgvo_vv_settings(vv_id, setting_id, setting, category, clientID) VALUES($vvID, ?, ?, ?, ?)");
    $stmt_insert_setting_client->bind_param("issi", $setID, $setting_encrypt, $cat, $clientID);

    $stmt_update_setting_matrix = $conn->prepare("UPDATE dsgvo_vv_settings SET setting = ? WHERE id = ?");
    $stmt_update_setting_matrix->bind_param("si", $setting_encrypt, $valID);
    $stmt_insert_setting_matrix = $conn->prepare("INSERT INTO dsgvo_vv_settings(vv_id, matrix_setting_id, setting, category) VALUES($vvID, ?, ?, ?)");
    $stmt_insert_setting_matrix->bind_param("iss", $setID, $setting_encrypt, $cat);
}
function getSettings($like, $mults = false, $from_matrix = false){
    global $conn;
    global $vvID;
    global $templateID;
    global $userID;
    global $privateKey;
    if($from_matrix){ // from matrix, returned id references a tuple in dsgvo_vv_data_matrix_settings
        global $matrixID;
        $result = $conn->query("SELECT setting, opt_name, opt_descr, category, dsgvo_vv_data_matrix_settings.id, dsgvo_vv_settings.id AS valID, dsgvo_vv_settings.clientID as client
        FROM dsgvo_vv_data_matrix_settings LEFT JOIN dsgvo_vv_settings ON dsgvo_vv_settings.matrix_setting_id = dsgvo_vv_data_matrix_settings.id AND vv_ID = $vvID WHERE opt_name LIKE '$like' AND dsgvo_vv_data_matrix_settings.matrixID = $matrixID");
    }else{ // from template
        $result = $conn->query("SELECT setting, opt_name, opt_descr, category, dsgvo_vv_template_settings.id, dsgvo_vv_settings.id AS valID, dsgvo_vv_settings.clientID as client, dsgvo_vv_settings.setting_id
        FROM dsgvo_vv_template_settings LEFT JOIN dsgvo_vv_settings ON setting_id = dsgvo_vv_template_settings.id AND vv_ID = $vvID WHERE opt_name LIKE '$like' AND templateID = $templateID ORDER BY dsgvo_vv_settings.setting_id");
    }
    showError($conn->error);
    $settings = array();
    while($row = $result->fetch_assoc()){
        $settings[$row['opt_name']]['descr'] = $row['opt_descr'];
        $settings[$row['opt_name']]['id'] = $row['id'];
        if($mults){
            $settings[$row['opt_name']]['setting'][] = secure_data('DSGVO', $row['setting'], 'decrypt', $userID, $privateKey);
            $settings[$row['opt_name']]['valID'][] = $row['valID'];
            $settings[$row['opt_name']]['category'][] = $row['category'];
            $settings[$row['opt_name']]['client'][] = $row['client'];
            $settings[$row['opt_name']]['setting_id'][] = $row['setting_id'];
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
            if($stmt_update_setting->affected_rows > 0){
                $escaped_setting = test_input($setting);
                insertVVLog("UPDATE","Update description for Procedure Directory $vvID to '$escaped_setting'");
            }
        } else {
            $setID = $settings['DESCRIPTION']['id'];
            $stmt_insert_setting->execute();
            $escaped_setting = test_input($setting);
            insertVVLog("INSERT","Insert description for Procedure Directory $vvID as '$escaped_setting'");
        }
        if($conn->error){
            showError($conn->error);
        } else {
            $settings['DESCRIPTION']['setting'] = $setting;
            showSuccess($lang['OK_SAVE']);
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
                <div class="col-sm-6 bold">Name der Firma</div><div class="col-sm-6 grey"><?php echo $row['name']; ?><br></div>
                <div class="col-sm-6 bold">Straße</div><div class="col-sm-6 grey"><?php echo $row['address']; ?><br></div>
                <div class="col-sm-6 bold">Ort</div><div class="col-sm-6 grey"><?php echo $row['companyCity']; ?><br></div>
                <div class="col-sm-6 bold">PLZ</div><div class="col-sm-6 grey"><?php echo $row['companyPostal']; ?><br></div>
                <div class="col-sm-6 bold">Telefon</div><div class="col-sm-6 grey"><?php echo $row['phone']; ?><br></div>
                <div class="col-sm-6 bold">E-Mail</div><div class="col-sm-6 grey"><?php echo $row['mail']; ?><br></div>
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
                    if($stmt_update_setting->affected_rows > 0){
                        $escaped_setting = test_input($setting);                    
                        insertVVLog("UPDATE","Update '$key' for Procedure Directory $vvID to '$escaped_setting'");
                    }
                } else {
                    $setID = $val['id'];
                    $stmt_insert_setting->execute();
                    $escaped_setting = test_input($setting);
                    insertVVLog("INSERT","Insert '$key' for Procedure Directory $vvID as '$escaped_setting'");
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
            <table class="table table-borderless">
                <thead>
                    <tr>
                        <th></th><!-- number -->
                        <th></th><!-- name -->
                        <th>Erfüllt</th>
                        <th>Nicht erfüllt</th>
                        <th>N/A</th>
                        <th>Notizen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $settings = getSettings('MULT_OPT_%');
                    foreach($settings as $key => $val){
                        // numbers for checked radio and text field are saved together, separated by |
                        $textFieldKey = "${key}_TEXTFIELD";
                        if(isset($_POST[$key]) || isset($_POST[$textFieldKey])){
                            $val['setting'] = $setting = (isset($_POST[$key])?intval($_POST[$key]):"")."|".(isset($_POST[$textFieldKey])?test_input($_POST[$textFieldKey]):"");
                            $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                            $valID = $val['valID'];
                            if($valID){
                                $stmt_update_setting->execute();
                                if($stmt_update_setting->affected_rows > 0){
                                    $escaped_setting = test_input($setting);
                                    insertVVLog("UPDATE","Update '$key' for Procedure Directory $vvID to '$escaped_setting'");
                                }
                            } else {
                                $setID = $val['id'];
                                $stmt_insert_setting->execute();
                                $escaped_setting = test_input($setting);
                                insertVVLog("INSERT","Insert '$key' for Procedure Directory $vvID as '$escaped_setting'");
                            }
                        }
                        $arr = explode("|",$val['setting'],2);
                        $radioValue = isset($arr[0])?$arr[0]:"";
                        $textFieldValue = isset($arr[1])?$arr[1]:"";
                        $number = sprintf("A%03d",intval(util_strip_prefix($key,"MULT_OPT_")));
                        echo '<tr>';
                        echo '<td class="text-muted">'.$number.'</td>';
                        echo '<td class="bold">'.$val['descr'].'</td>';
                        $checked = $radioValue == 1 ? 'checked' : '';
                        echo '<td class="grey text-center"><input type="radio" '.$checked.' name="'.$key.'" value="1" /></td>';
                        $checked = $radioValue == 2 ? 'checked' : '';
                        echo '<td class="grey text-center"><input type="radio" '.$checked.' name="'.$key.'" value="2" /></td>';
                        $checked = $radioValue == 3 ? 'checked' : '';
                        echo '<td class="grey text-center"><input type="radio" '.$checked.' name="'.$key.'" value="3" /></td>';
                        echo '<td><input type="text" class="form-control" name="'.$textFieldKey.'" value="'.$textFieldValue.'" /></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$settings = getSettings('EXTRA_%');

function update_or_insert_extra (&$settings, $name){
    global $userID;
    global $privateKey;
    global $stmt_update_setting;
    global $stmt_insert_setting;
    global $valID;
    global $setting_encrypt;
    global $setID;
    global $vvID;
    if(isset($_POST[$name])){
        $settings[$name]['setting'] = $setting = strip_tags($_POST[$name]);
        $valID = $settings[$name]['valID'];
        $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
        if($valID){
            $stmt_update_setting->execute();
            if($stmt_update_setting->affected_rows > 0){
                $escaped_setting = test_input($setting);
                insertVVLog("UPDATE","Update '$name' for Procedure Directory $vvID to '$escaped_setting'");
            }
        }else{
            $setID = $settings[$name]['id'];
            $stmt_insert_setting->execute();
            $escaped_setting = test_input($setting);
            insertVVLog("INSERT","Insert '$name' for Procedure Directory $vvID as '$escaped_setting'");
        }
    }
}

if(isset($settings['EXTRA_DVR'])){
    update_or_insert_extra($settings, "EXTRA_DVR");
    update_or_insert_extra($settings, "EXTRA_DAN");
    echo '<div class="col-md-7">';
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">'.$settings['EXTRA_DVR']['descr'].' '.mc_status().'</div>';
    echo '<div class="row"><div class="col-sm-6 bold">DVR-Nummer</div><div class="col-sm-6"><input type="text" name="EXTRA_DVR" value="'.$settings['EXTRA_DVR']['setting'].'" class="form-control"></div></div>';
    echo '<div class="row"><div class="col-sm-6 bold">DAN-Nummer</div><div class="col-sm-6"><input type="text" name="EXTRA_DAN" value="'.$settings['EXTRA_DAN']['setting'].'" class="form-control"></div></div>';
    echo '</div></div>';
}
if(isset($settings['EXTRA_FOLGE'])){
    update_or_insert_extra($settings, "EXTRA_FOLGE_CHOICE");
    update_or_insert_extra($settings, "EXTRA_FOLGE_DATE");
    update_or_insert_extra($settings, "EXTRA_FOLGE_REASON");
    echo '<div class="col-md-7">';
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">'.$settings['EXTRA_FOLGE']['descr'].' '.mc_status().'</div>';
    echo '<div class="row"><div class="col-sm-2"><input type="radio" name="EXTRA_FOLGE_CHOICE" value="1" '.(intval($settings['EXTRA_FOLGE_CHOICE']['setting']) === 1?"checked":"").'>Ja</div><div class="col-sm-2"><input type="radio" name="EXTRA_FOLGE_CHOICE" value="0" '.(intval($settings['EXTRA_FOLGE_CHOICE']['setting']) === 0?"checked":"").'>Nein</div></div>';
    echo '<div class="row"><div class="col-sm-6 bold">Wenn Ja, wann?</div><div class="col-sm-6"><input type="text" name="EXTRA_FOLGE_DATE" class="form-control datepicker" value="'.$settings['EXTRA_FOLGE_DATE']['setting'].'"></div></div>';
    echo '<div class="row"><div class="col-sm-6 bold">Wenn Nein, warum?</div><div class="col-sm-6"><input type="text" name="EXTRA_FOLGE_REASON" class="form-control" value="'.$settings['EXTRA_FOLGE_REASON']['setting'].'"></div></div>';
    echo '</div></div>';
}
if(isset($settings['EXTRA_DOC'])){
    update_or_insert_extra($settings, "EXTRA_DOC_CHOICE");
    update_or_insert_extra($settings, "EXTRA_DOC");
    echo '<div class="col-md-7">';
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">'.$settings['EXTRA_DOC']['descr'].' '.mc_status().'</div>';
    echo '<div class="row"><div class="col-sm-2"><input type="radio" name="EXTRA_DOC_CHOICE" value="1" '.(intval($settings['EXTRA_DOC_CHOICE']['setting']) === 1?"checked":"").'>Ja</div><div class="col-sm-2"><input type="radio" name="EXTRA_DOC_CHOICE" value="0" '.(intval($settings['EXTRA_DOC_CHOICE']['setting']) === 0?"checked":"").'>Nein</div></div>';
    echo '<div class="row"><div class="col-sm-6 bold">Wo befindet sich diese?</div><div class="col-sm-6"><input type="text" name="EXTRA_DOC" class="form-control" value="'.$settings['EXTRA_DOC']['setting'].'"></div></div>';
    echo '</div></div>';
}
?>

<?php if($doc_type == 'APP'): ?>
<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-heading">Auflistung der verarbeiteten Datenfelder und deren Übermittlung</div>
        <div class="panel-body" style="overflow-x: auto;">
            <?php
            $str_heads = $space = $space_key = '';
            $heading = getSettings('APP_HEAD_%', true);
            foreach($heading as $key => $val){
                if(!empty($_POST['delete_cat']) && $val['valID'][0] == $_POST['delete_cat']){
                    $id = $val['setting_id'][0];
                    $conn->query("DELETE FROM dsgvo_vv_settings WHERE vv_id = $vvID AND setting_id = $id");
                    if($conn->error){
                        showError($conn->error);
                    }else{
                        unset($heading[$key]);
                        showSuccess($lang["OK_DELETE"]);
                        continue;
                    }
                }
                if($val['setting'][0]){
                    $tooltip = "";
                    switch ($val['category'][0]) {
                        case 'heading1':
                            $tooltip = "Übermittlung";
                            break;
                        case 'heading2':
                            $tooltip = "Verarbeitung";
                            break;
                        case 'heading3':
                            $tooltip = "Übermittlung in nicht-EU Ausland";
                            break;
                    }
                    $client = "";
                    if($val['client'] && $val['client'][0]){
                        $clientID = $val['client'][0];
                        $result = $conn->query("SELECT name FROM $clientTable WHERE id = $clientID");
                        if($result && $result->num_rows !== 0){
                            $client = ' (an '.$result->fetch_assoc()["name"].') ';
                        }
                    }
                    $str_heads .= '<th style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width:100px;" data-toggle="tooltip" data-container="body" data-placement="left" title="'.$val['setting'][0].': '.$tooltip.$client.'"><div class="btn-group"><button type="button" class="btn btn-link" data-toggle="dropdown">'.$val['setting'][0].'</button>
                    <ul class="dropdown-menu"><li><button type="submit" class="btn btn-link" name="delete_cat" value="'.$val['valID'][0].'">Löschen</button></li></ul></div></th>';
                } else {
                    $space_key = !$space ? $key : $space_key;
                    $space = !$space ? $val['id'] : $space;
                    unset($heading[$key]);
                }
            }
            // no other sane choice for the backend to be but here
            if($space && isset($_POST['add_category']) && !empty($_POST['add_category_name'])){
                $setID = $space;
                $setting = test_input($_POST['add_category_name']);
                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                $cat = test_input($_POST['add_category_mittlung']);
                $clientID = (isset($_POST['add_category_client']) && intval($_POST['add_category_client'])) ? intval($_POST['add_category_client']) : false;
                if($clientID){
                    $stmt = $stmt_insert_setting_client;
                }else{
                    $stmt = $stmt_insert_setting;
                }
                $stmt->execute();
                $escaped_setting = test_input($setting);                
                insertVVLog("INSERT","Add new category '$escaped_setting' for Procedure Directory $vvID");
                if($stmt->error){
                    showError($stmt->error);
                } else {
                    $tooltip = "";
                    switch ($cat) {
                        case 'heading1':
                            $tooltip = "Übermittlung";
                            break;
                        case 'heading2':
                            $tooltip = "Verarbeitung";
                            break;
                        case 'heading3':
                            $tooltip = "Übermittlung in nicht-EU Ausland";
                            break;
                    }
                    $client = "";
                    if($clientID){
                        $result = $conn->query("SELECT name FROM $clientTable WHERE id = $clientID");
                        if($result && $result->num_rows !== 0){
                            $client = ' (an '.$result->fetch_assoc()["name"].') ';
                        }
                        showError($conn->error);
                    }
                    $heading[$space_key] = array('id' => $stmt->insert_id, 'category' => array());
                    $str_heads .= '<th data-toggle="tooltip" data-container="body" data-placement="left" title="'.$tooltip.$client.'"><div class="btn-group"><button type="button" class="btn btn-link" data-toggle="dropdown">'.$setting.'</button>
                    <ul class="dropdown-menu"><li><button type="submit" class="btn btn-link" name="delete_cat" value="'.$stmt->insert_id.'">Löschen</button></li></ul></div></th>';
                }
            } elseif(!$space){
                showWarning("Kein Platz mehr");
            }
            ?>

            <?php  if($matrixID){ ?>
            <table class="table table-condensed">
            <thead><tr>
            <th>Gruppierung</th>
            <th>Nr.</th>
            <th>Datenkategorien der gesammelten personenbezogenen Daten</th>
            <th data-toggle="tooltip" data-container="body" data-placement="left" title='Besondere Datenkategorie iSd Art 9 DSGVO'>Art9</th>
            <?php echo $str_heads; ?>
            <th><a data-toggle="modal" data-target="#add-cate" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></a></th>
            </tr></thead>
            <?php
                $settings = getSettings('APP_GROUP_%', false, true); // settings from matrix
                $fieldID = 0;
                foreach($settings as $key => $val){
                    $i = 1;
                    $cats = getSettings('APP_CAT_'.util_strip_prefix($key, 'APP_GROUP_').'_%', false, true);
                    echo '<tr><td rowspan="'.(count($cats) +1).'" >'.$val['descr'].'</td></tr>';
                    foreach($cats as $catKey => $catVal){
                        if($_SERVER['REQUEST_METHOD'] == 'POST'){
                            $valID = $catVal['valID'];
                            if(!empty($_POST[$catKey])){
                                $catVal['setting'] = $setting = '1';
                                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                                if($valID){ //update to true if checked and exists
                                    $stmt_update_setting_matrix->execute();
                                    if($stmt_update_setting_matrix->affected_rows > 0){
                                        $escaped_setting = test_input($setting);            
                                        insertVVLog("UPDATE","Update '$key' for Procedure Directory $vvID to '$escaped_setting'");
                                    }
                                } else { //insert with true if checked and not exists
                                    $cat = '';
                                    $setID = $catVal['id'];
                                    $stmt_insert_setting_matrix->execute();
                                    $escaped_setting = test_input($setting);            
                                    insertVVLog("INSERT","Insert '$key' for Procedure Directory $vvID as '$escaped_setting'");
                                }
                            } elseif($valID && $catVal['setting']) { //set to false only if not checked, exists and saved as true (anything else is false anyways)
                                $catVal['setting'] = $setting = '0';
                                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                                $stmt_update_setting_matrix->execute();
                                if($stmt_update_setting_matrix->affected_rows > 0){
                                    $escaped_setting = test_input($setting);
                                    insertVVLog("UPDATE","Update '$key' for Procedure Directory $vvID to '$escaped_setting'");
                                }
                            }
                        }
                        $fieldID ++;
                        echo '<tr>';
                        echo '<td>'.$fieldID.'</td>';
                        echo '<td>'.$catVal['descr'].'</td>';
                        $checked = $catVal['setting'] ? 'checked' : '';
                        echo '<td><input type="checkbox" '.$checked.' name="'.$catKey.'" value="1" /></td>';

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
                                        if($stmt_update_setting->affected_rows > 0){
                                            $escaped_setting = test_input($setting);            
                                            insertVVLog("UPDATE","Update Value '$valID' for Procedure Directory $vvID to '$escaped_setting'");
                                        }
                                    } else {
                                        $cat = $catKey;
                                        $setID = $headVal['id'];
                                        $stmt_insert_setting->execute();
                                        $escaped_setting = test_input($setting);            
                                        insertVVLog("INSERT","Insert Value '$valID' for Procedure Directory $vvID as '$escaped_setting'");
                                    }
                                } elseif($j && $headVal['setting'][$j]){
                                    $valID = $headVal['valID'][$j];
                                    $setting = '0';
                                    $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
                                    $checked = '';
                                    $stmt_update_setting->execute();
                                    if($stmt_update_setting->affected_rows > 0){
                                        $escaped_setting = test_input($setting);            
                                        insertVVLog("UPDATE","Update Value '$valID' for Procedure Directory $vvID to '$escaped_setting'");
                                    }
                                    showError($stmt_update_setting->error);
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
                 <?php
            }else{
                ?>
                Diese Firma hat keine Matrix in den Einstellungen. Zum Erstellen <a href='../dsgvo/data-matrix'>hier klicken</a>.
                <?php
            }
            ?>

        </div>
    </div>
</div>

<div id="add-cate" class="modal fade">
  <div class="modal-dialog modal-content modal-md">
	<div class="modal-header h4">Neue Kategorie Option</div>
	<div class="modal-body">
        <div class="row">
            <div class="col-sm-12">
                <label for="add_category_name">Name</label>
                <input type="text" name="add_category_name" class="form-control" />
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="radio">
                    <label><input type="radio" name="add_category_mittlung" value="heading1" checked />Übermittlung</label>
                </div>
                <div class="radio">
                    <label><input type="radio" name="add_category_mittlung" value="heading3" />Übermittlung in nicht-EU Ausland</label>
                </div>
                <div class="radio">
                    <label><input type="radio" name="add_category_mittlung" value="heading2" />Verarbeitung</label>
                </div>
            </div>
        </div>
        <div class="row" id="mittlung_customer_chooser">
            <div class="col-sm-12">
                <div class="input-group">
                    <select class="form-control" name="add_category_client">
                        <?php
                            $result = mysqli_query($conn, "SELECT id, name FROM $clientTable WHERE isSupplier = 'FALSE' AND companyID=$company");
                        ?>
                        <option value='0'><?php echo $lang['CLIENT'] ?> ...</option>
                        <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $cmpnyID = $row['id'];
                                    $cmpnyName = $row['name'];
                                    echo "<option value='$cmpnyID'>$cmpnyName</option>";
                                }
                            }
                        ?>
                    </select>
                    <div class="input-group-btn">
                        <a class="btn btn-default" href="../system/clients?t=1"><i class="fa fa-external-link text-muted"></i>Neu</a>
                    </div>
                </div>
            </div>
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
<script>
    $('[data-toggle="tooltip"]').tooltip();

    function toggleCustomerChooser(visible){
        if(visible){
            $("#mittlung_customer_chooser").fadeIn();
        } else {
            $("select[name='add_category_client']").val("0");
            $("#mittlung_customer_chooser").fadeOut();
        }
    }

    $("input[name='add_category_mittlung'][value='heading1']").change(function(event){
        toggleCustomerChooser(true);
    })

    $("input[name='add_category_mittlung'][value='heading2']").change(function(event){
        toggleCustomerChooser(false);
    })

    $("input[name='add_category_mittlung'][value='heading3']").change(function(event){
        toggleCustomerChooser(true);
    })
</script>
<?php echo $last_encryption_error; ?>
<?php include dirname(__DIR__) . '/footer.php'; ?>
