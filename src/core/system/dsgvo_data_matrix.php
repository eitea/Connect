<?php
include dirname(dirname(__DIR__)) . '/header.php';
enableToCore($userID);
require dirname(dirname(__DIR__)) . "/misc/helpcenter.php";

if (isset($_GET['n'])) {
    $cmpID = intval($_GET['n']);
} else if (count($available_companies) == 2) {
    $cmpID = $available_companies[1];
    redirect("../system/data-matrix?n=$cmpID");
}

if (isset($_POST['company_id'])) {
    $newCmpID = intval($_POST['company_id']);
    if ($cmpID !== $newCmpID) {
        redirect("../system/data-matrix?n=$cmpID");
    }
}

if (isset($cmpID)) {
    $result = $conn->query("SELECT id FROM dsgvo_vv_data_matrix WHERE companyID = $cmpID");
    if (!$result || $result->num_rows === 0) {
        $conn->query("INSERT INTO dsgvo_vv_data_matrix (companyID) VALUES($cmpID)");
        $result = $conn->query("SELECT id FROM dsgvo_vv_data_matrix WHERE companyID = $cmpID");
        if ($result) {
            // Company has no matrix, create a new one
            $stmt = $conn->prepare("INSERT INTO dsgvo_vv_data_matrix_settings (matrixID, opt_name, opt_descr) VALUES(?, ?, ?)");
            $stmt->bind_param("iss", $matrixID, $opt, $descr);
            $matrixID = $result->fetch_assoc()["id"];
            $opt = 'APP_GROUP_1';
            $descr = 'Kunde';
            $stmt->execute();
            $opt = 'APP_GROUP_2';
            $descr = 'Lieferanten und Partner';
            $stmt->execute();
            $opt = 'APP_GROUP_3';
            $descr = 'Mitarbeiter';
            $stmt->execute();
            $i = 1;
            $cat_descr = array('', 'Firmenname', 'Ansprechpartner, E-Mail, Telefon', 'Straße', 'Ort', 'Bankverbindung', 'Zahlungsdaten', 'UID', 'Firmenbuchnummer');
            while ($i < count($cat_descr)) { //Kunde
                $opt = 'APP_CAT_1_' . $i;
                $descr = $cat_descr[$i];
                $stmt->execute();
                $i++;
            }
            $i = 1;
            while ($i < 9) { //Lieferanten und Partner
                $opt = 'APP_CAT_2_' . $i;
                $descr = $cat_descr[$i];
                $stmt->execute();
                $i++;
            }
            $cat_descr = array('', 'Nachname', 'Vorname', 'PLZ', 'Ort', 'Telefon', 'Geb. Datum', 'Lohn und Gehaltsdaten', 'Religion', 'Gewerkschaftszugehörigkeit', 'Familienstand',
                'Anwesenheitsdaten', 'Bankverbindung', 'Sozialversicherungsnummer', 'Beschäftigt als', 'Staatsbürgerschaft', 'Geschlecht', 'Name, Geb. Datum und Sozialversicherungsnummer des Ehegatten',
                'Name, Geb. Datum und Sozialversicherungsnummer der Kinder', 'Personalausweis, Führerschein', 'Abwesenheitsdaten', 'Kennung');
            $i = 1;
            while ($i < count($cat_descr)) { //Mitarbeiter
                $opt = 'APP_CAT_3_' . $i;
                $descr = $cat_descr[$i];
                $stmt->execute();
                $i++;
            }
            $stmt->close();
            // /new matrix
        }
    } else {
        $matrixID = $result->fetch_assoc()["id"];
    }
    showError($conn->error);
}

if (isset($_POST['add_setting']) && isset($matrixID)) {
    if (!empty($_POST['add_setting']) && !empty($_POST[test_input($_POST['add_setting'])])) {
        $setting = test_input($_POST['add_setting']);
        $descr = test_input($_POST[$setting]);
        $conn->query("INSERT INTO dsgvo_vv_data_matrix_settings (matrixID, opt_name, opt_descr) VALUES ($matrixID, '$setting', '$descr')");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang['OK_ADD']);
        }
    }
}

if (isset($_POST['delete_setting']) && isset($matrixID)) {
    if (!empty($_POST['delete_setting'])) {
        $setting = test_input($_POST['delete_setting']);
        $conn->query("DELETE FROM dsgvo_vv_data_matrix_settings WHERE matrixID = $matrixID AND opt_name = '$setting'");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showInfo($lang['OK_DELETE']);
        }
    }
}

if (isset($_POST['save_all']) && isset($matrixID)) {
    $stmt = $conn->prepare("UPDATE dsgvo_vv_data_matrix_settings SET opt_descr = ? WHERE matrixID = $matrixID AND opt_name = ?");
    showError($conn->error);
    $stmt->bind_param("ss", $descr, $setting);
    $affected_rows = 0;
    foreach ($_POST as $name => $value) {
        $setting = test_input($name);
        $descr = test_input($value);
        if (util_starts_with($setting, "APP_GROUP_") || util_starts_with($setting, "APP_CAT_")) {
            if (!empty($descr)) {
                $stmt->execute();
                $affected_rows += $stmt->affected_rows;
                showError($stmt->error);
            }
        }
    }
    if ($affected_rows > 0) {
        showSuccess("$affected_rows Felder aktualisiert");
    }
    $stmt->close();
}

?>

    <!-- BODY -->
    <form method="POST" id="main-form">

        <div class="page-header">
            <h3>
                <?php echo $lang['DATA_MATRIX']; ?>
                <div class="page-header-button-group">
                    <button type="submit" class="btn btn-default blinking" name="save_all" value="true"><i class="fa fa-floppy-o"></i></button>
                </div>
                <br />
                <br />
                <div>
                <select class="js-example-basic-single" name="company_id" id="company_chooser">
                    <?php
$result = $conn->query("SELECT id,name FROM $companyTable");
while ($result && ($row = $result->fetch_assoc())) {
    if (in_array($row['id'], $available_companies)) {
        echo "<option value='${row['id']}'>${row['name']}</option>";
    }
}
?>
                </select>
                </div>
            </h3>

        </div>

        <script>
            $("#company_chooser").change(function () {
                $("#main-form").submit();
            })
        </script>



        <!-- <script>$("#bodyContent").show()</script>debug -->

        <?php if (isset($matrixID)): ?>

        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">Auflistung der verarbeiteten Datenfelder und deren Übermittlung</div>
                <div class="panel-body">
                    <?php
$i = 1;
$fieldID = 0;
$result = $conn->query("SELECT opt_name, opt_descr FROM dsgvo_vv_data_matrix_settings WHERE matrixID = $matrixID AND opt_name LIKE 'APP_GROUP_%'");
while ($result && $row = $result->fetch_assoc()) {
    $num = util_strip_prefix($row['opt_name'], 'APP_GROUP_');
    if ($num == $i) {
        $i++;
    }
    ?>
                        <div class="row">
                            <div class="col-sm-6">
                                <label>Gruppierung</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" maxlength="350" name="<?php echo $row['opt_name']; ?>" value="<?php echo $row['opt_descr']; ?>"
                                    />
                                    <span class="input-group-btn">
                                        <button type="button" onclick="delete_setting('<?php echo $row['opt_name']; ?>')" class="btn btn-danger">
                                            <i class="fa fa-trash-o"></i>
                                        </button>
                                    </span>
                                </div>
                                <br>
                                <label>Datenkategorien der gesammelten personenbezogenen Daten</label>
                                <br>
                            </div>
                        </div>
                        <div class="col-sm-offset-1">
                            <?php
$j = 1;
    $cat_result = $conn->query("SELECT opt_name, opt_descr FROM dsgvo_vv_data_matrix_settings WHERE matrixID = $matrixID AND opt_name LIKE 'APP_CAT_$num%'");
    while ($cat_row = $cat_result->fetch_assoc()) {
        $jnum = util_strip_prefix($cat_row['opt_name'], 'APP_CAT_' . $num . '_');
        if ($jnum == $j) {
            $j++;
        }
        $fieldID++;

        ?>
                                <div class="col-sm-6 ">
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <?php echo $fieldID; ?>
                                        </span>
                                        <input type="text" class="form-control" maxlength="350" name="<?php echo $cat_row['opt_name']; ?>" value="<?php echo $cat_row['opt_descr']; ?>"
                                        />
                                        <span class="input-group-btn">
                                            <button type="button" onclick="delete_setting('<?php echo $cat_row['opt_name']; ?>')" class="btn btn-danger">
                                                <i class="fa fa-trash-o"></i>
                                            </button>
                                        </span>
                                    </div>
                                    <br>
                                </div>

                                <?php
}
    ?>
                        </div>
                        <div class="col-sm-11 col-sm-offset-1">
                            <label>Neue Datenkategorie</label>
                            <div class="input-group">
                                <input type="text" class="form-control" maxlength="350" name="APP_CAT_<?php echo $num ?>_<?php echo $j ?>"
                                />
                                <span class="input-group-btn">
                                    <button type="submit" name="add_setting" value="APP_CAT_<?php echo $num ?>_<?php echo $j ?>"
                                        class="btn btn-warning">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            </div>
                            <br>
                            <br>
                        </div>
                        <br>
                        <?php
}
?>
                        <div class="col-sm-12">
                            <label>Neue Gruppierung</label>
                            <div class="input-group">
                                <input type="text" class="form-control" maxlength="350" name="APP_GROUP_<?php echo $i; ?>" />
                                <span class="input-group-btn">
                                    <button type="submit" name="add_setting" value="APP_GROUP_<?php echo $i; ?>" class="btn btn-warning">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                        <br>
                </div>
            </div>
        </div>




        <?php endif;?>

        <script>

            function delete_setting(name) {
                var conf = confirm("Wirklich löschen?");
                if (!conf || conf == "null") return;
                var form = document.createElement("form");
                form.setAttribute("method", "POST");
                var field = document.createElement("input");
                field.setAttribute("type", "hidden");
                field.setAttribute("name", "delete_setting");
                field.setAttribute("value", name);
                form.appendChild(field);
                document.body.appendChild(form);
                form.submit();
            }

        </script>


    </form>
    <!-- /BODY -->
    <?php include dirname(dirname(__DIR__)) . '/footer.php';?>