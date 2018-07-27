<?php
include dirname(__DIR__) . '/header.php';
require dirname(__DIR__) . "/misc/helpcenter.php";
require_permission("READ","DSGVO","PROCEDURE_DIRECTORY");

if (isset($_GET['n'])) {
	$cmpID = intval($_GET['n']);
} else if (count($available_companies) == 2) {
	$cmpID = $available_companies[1];
	redirect("data-matrix?n=$cmpID");
}

if (isset($_POST['company_id'])) {
	$newCmpID = intval($_POST['company_id']);
	if ($cmpID !== $newCmpID) {
		redirect("data-matrix?n=$newCmpID");
	}
}

function insertVVLog($short,$long){
	global $conn;
	global $userID;
	global $privateKey;
	static $stmt_insert_vv_log = null;
	if($stmt_insert_vv_log == null){
		$stmt_insert_vv_log = $conn->prepare("INSERT INTO dsgvo_vv_logs (user_id,short_description,long_description,scope) VALUES ($userID,?,?,?)");
		showError($conn->error);
		$stmt_insert_vv_log->bind_param("sss", $stmt_insert_vv_log_short_description, $stmt_insert_vv_log_long_description, $stmt_insert_vv_log_scope);
	}
	$stmt_insert_vv_log_short_description = secure_data('DSGVO', $short, 'encrypt', $userID, $privateKey);
	$stmt_insert_vv_log_long_description = secure_data('DSGVO', $long);
	$stmt_insert_vv_log_scope = secure_data('DSGVO', 'VV'); //usefulness: 0
	$stmt_insert_vv_log->execute();
	showError($stmt_insert_vv_log->error);
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
			insertVVLog("INSERT","Create new default matrix with id $matrixID");
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
		}
	} else {
		$matrixID = $result->fetch_assoc()["id"];
	}
	showError($conn->error);
}

if (has_permission("WRITE","DSGVO","PROCEDURE_DIRECTORY") && isset($_POST['add_setting']) && isset($matrixID)) {
	if (!empty($_POST['add_setting']) && !empty($_POST[test_input($_POST['add_setting'])])) {
		$setting = test_input($_POST['add_setting']);
		$descr = test_input($_POST[$setting]);
		insertVVLog("INSERT","Add a new field (name: $setting, description: $descr) to matrix $matrixID");
		$conn->query("INSERT INTO dsgvo_vv_data_matrix_settings (matrixID, opt_name, opt_descr) VALUES ($matrixID, '$setting', '$descr')");
		if ($conn->error) {
			showError($conn->error . __LINE__);
		} else {
			showSuccess($lang['OK_ADD']);
		}
	}
}

if (has_permission("WRITE","DSGVO","PROCEDURE_DIRECTORY") && isset($_POST['delete_setting']) && isset($matrixID)) {
	if (!empty($_POST['delete_setting'])) {
		$setting = test_input($_POST['delete_setting']);
		$conn->query("DELETE FROM dsgvo_vv_data_matrix_settings WHERE matrixID = $matrixID AND opt_name = '$setting'");
		insertVVLog("DELETE","Delete a field (name: $setting) in matrix $matrixID");
		if ($conn->error) {
			showError($conn->error);
		} else {
			showInfo($lang['OK_DELETE']);
		}
	}
}

if (has_permission("WRITE","DSGVO","PROCEDURE_DIRECTORY") && isset($_POST['save_all']) && isset($matrixID)) {
	$stmt = $conn->prepare("UPDATE dsgvo_vv_data_matrix_settings SET opt_descr = ?, opt_duration = ?, opt_unit = ?, opt_status = ? WHERE matrixID = $matrixID AND opt_name = ?"); echo $conn->error;
	$stmt->bind_param("siiss", $descr, $duration, $unit, $status, $setting);
	$affected_rows = 0;
	foreach ($_POST as $name => $value) {
		if(strpos($name, '_NUMBER') || strpos($name, '_UNIT') || strpos($name, '_SETTING')) continue;
		$setting = test_input($name);
		$descr = test_input($value);
		$status = empty($_POST[$name.'_SETTING']) ? '' : 'ART9'; //heavy misuse of unused column, but you really dont want to meddle with dsgvo_vv_settings in here
		$duration = $unit = 0;
		if(isset($_POST["${name}_NUMBER"],$_POST["${name}_UNIT"])){
			$duration = intval($_POST["${name}_NUMBER"]);
			$unit = intval($_POST["${name}_UNIT"]);
		}
		if (substr($setting, 0, 9) == 'APP_GROUP' || substr($setting, 0, 8) == 'APP_CAT_') {
			if (!empty($descr)) {
				$stmt->execute();
				$affected_rows += $stmt->affected_rows;
				if($stmt->affected_rows && $stmt->affected_rows > 0){
					insertVVLog("UPDATE","Update a field (name: $setting, description: $descr) in matrix $matrixID");
				}
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
<div class="page-header-fixed">
	<div class="page-header">
		<h3><?php echo $lang['DATA_MATRIX']; ?>
			<div class="page-header-button-group">
				<?php if(has_permission("WRITE","DSGVO","PROCEDURE_DIRECTORY")): ?> 
				<button type="submit" form="main-form" class="btn btn-default blinking" name="save_all" value="true"><i class="fa fa-floppy-o"></i></button>
				<?php endif ?>
			</div>
		</h3>
	</div>
</div>

<script>
//TODO: cleaner, easier, html-only solution
$("#company_chooser").change(function () {
	$("#main-form").submit();
})
</script>
<!-- <script>$("#bodyContent").show()</script>debug -->

<form method="POST" id="main-form">

	<div class="page-content-fixed-130">
		<?php if (isset($matrixID)): ?>
			<div class="col-md-12">
				<div class="panel panel-default">
					<div class="panel-heading"><?php echo $lang['DATA_MATRIX_DATA_CATEGORIES_TRANSMISSION'] ?></div>
					<div class="panel-body">
						<?php
						$i = 1;
						$fieldID = 0;
						$result = $conn->query("SELECT opt_name, opt_descr FROM dsgvo_vv_data_matrix_settings WHERE matrixID = $matrixID AND opt_name LIKE 'APP_GROUP_%'");
						while ($result && $row = $result->fetch_assoc()):
							$num = util_strip_prefix($row['opt_name'], 'APP_GROUP_');
							if ($num == $i) {
								$i++;
							}
							?>
							<div class="row">
								<div class="col-sm-6">
									<label><?php echo $lang['GROUP'] ?></label>
									<div class="input-group">
										<input type="text" class="form-control" maxlength="350" name="<?php echo $row['opt_name']; ?>" value="<?php echo $row['opt_descr']; ?>" />
										<span class="input-group-btn">
											<button type="button" onclick="delete_setting('<?php echo $row['opt_name']; ?>')" class="btn btn-danger">
												<i class="fa fa-trash-o"></i>
											</button>
										</span>
									</div>
									<br>
									<label><?php echo $lang['DATA_MATRIX_GROUP_SUBHEADING'] ?></label>
									<br>
								</div>
							</div>
							<div class="col-sm-offset-1">
								<?php
								$j = 1;
								$cat_result = $conn->query("SELECT opt_name, opt_descr, opt_unit, opt_duration, opt_status FROM dsgvo_vv_data_matrix_settings WHERE matrixID = $matrixID AND opt_name LIKE 'APP_CAT_$num%'");
								$jnum = 1;
								while ($cat_row = $cat_result->fetch_assoc()):
									$jnum = util_strip_prefix($cat_row['opt_name'], 'APP_CAT_' . $num . '_');
									// echo $j;
									// if ($jnum == $j) {
									$j++;
									// }
									if(intval($jnum)>=$j){
										$j = intval($jnum)+1;
									}
									$fieldID++;
									?>
									<div class="row form-group">
										<div class="col-md-3">
											<div class="input-group">
												<span class="input-group-addon">
													<?php echo $fieldID; ?>
												</span>
												<input type="text" class="form-control" maxlength="350" name="<?php echo $cat_row['opt_name']; ?>" value="<?php echo $cat_row['opt_descr']; ?>" />
											</div>
										</div>
										<div class="col-md-3">
											<select class="form-control duration-number-select" name="<?php echo $cat_row['opt_name']; ?>_NUMBER" >
												<option value="default"><?php echo $lang['DELETION_PERIOD'] ?> - <?php echo $lang['DESCRIBED_IN_PROCESS'] ?></option>
												<?php
												for ($k = 1;$k<=30;++$k) {
													$selected = (intval($cat_row['opt_duration']) === $k)?"selected":"";
													echo "<option $selected value='$k'>$k</option>";
												}
												?>
											</select>
										</div>
										<div class="col-md-3">
											<select class="form-control duration-unit-select" name="<?php echo $cat_row['opt_name']; ?>_UNIT" >
												<option value="default"><?php echo $lang['DELETION_PERIOD'] ?> - <?php echo $lang['DESCRIBED_IN_PROCESS'] ?></option>
												<option value="1" <?php echo (intval($cat_row['opt_unit']) === 1)?"selected":""; echo '>'; echo $lang['TIME_UNIT_TOSTRING'][1]; ?></option>
												<option value="2" <?php echo (intval($cat_row['opt_unit']) === 2)?"selected":""; echo '>'; echo $lang['TIME_UNIT_TOSTRING'][2]; ?></option>
												<option value="3" <?php echo (intval($cat_row['opt_unit']) === 3)?"selected":""; echo '>'; echo $lang['TIME_UNIT_TOSTRING'][3]; ?></option>
												<option value="4" <?php echo (intval($cat_row['opt_unit']) === 4)?"selected":""; echo '>'; echo $lang['TIME_UNIT_TOSTRING'][4]; ?></option>
											</select>
										</div>
										<div class="col-sm-2">
											<label title="<?php echo $lang['SPECIAL_DATA_CATEGORY'] ?>">
												<input type="checkbox" <?php if($cat_row['opt_status'] == 'ART9') echo 'checked'; ?>  name="<?php echo $cat_row['opt_name']; ?>_SETTING" value="1" /> Art9
											</label>
										</div>
										<div class="col-sm-1">
											<button type="button" onclick="delete_setting('<?php echo $cat_row['opt_name']; ?>')" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>
										</div>
										<br>
									</div>
								<?php endwhile; //cat row ?>
							</div>
							<div class="col-sm-11 col-sm-offset-1">
								<label><?php echo $lang['NEW_DATA_CATEGORY'] ?></label>
								<div class="input-group">
									<input type="text" class="form-control" maxlength="350" name="APP_CAT_<?php echo $num ?>_<?php echo $j ?>" />
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
						<?php endwhile;?>
						<div class="col-sm-12">
							<label><?php echo $lang['NEW_GROUP'] ?></label>
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
		<?php endif; //isset matrixID ?>
	</div>
</form>

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

$(".duration-number-select").change(function(event){
	var name = event.target.name.replace("_NUMBER",""); // eg APP_CAT_1_2
	if(event.target.value == "default"){
		resetBothSelects(name);
	}else{
		// debugger;
		if($("[name="+name+"_UNIT]").val() == "default"){
			$("[name="+name+"_UNIT]").val(1);
		}
	}
})
$(".duration-unit-select").change(function(event){
	var name = event.target.name.replace("_UNIT",""); // eg APP_CAT_1_2
	if(event.target.value == "default"){
		resetBothSelects(name);
	}else{
		if($("[name="+name+"_NUMBER]").val() == "default"){
			$("[name="+name+"_NUMBER]").val(1);
		}
	}
})
function resetBothSelects(baseName){
	$("[name="+baseName+"_NUMBER]").val("default");
	$("[name="+baseName+"_UNIT]").val("default");
}
</script>
<?php if(!has_permission("WRITE","DSGVO","PROCEDURE_DIRECTORY")): ?>
<script>
$('#bodyContent .affix-content input').prop("disabled", true); // disable all input on this page (not header)
$('#bodyContent .affix-content select').prop("disabled", true); // disable all input on this page (not header)
$('#bodyContent .affix-content button').prop("disabled", true); // disable all input on this page (not header)
</script>
<?php endif ?>
<?php include dirname(__DIR__) . '/footer.php';?>
