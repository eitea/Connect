<?php
//requires $isTempalte, $x
if($x){
    $result = $conn->query("SELECT * FROM dynamicprojects WHERE projectid = '$x'"); echo $conn->error;
    $dynrow = $result->fetch_assoc();

    $result = $conn->query("SELECT teamid FROM dynamicprojectsteams WHERE projectid = '$x'"); echo $conn->error;
    $dynrow_teams = array_column($result->fetch_all(MYSQLI_ASSOC), 'teamid');

    $result = $conn->query("SELECT userid, position FROM dynamicprojectsemployees WHERE projectid = '$x'"); echo $conn->error;
    $dynrow_emps = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='$dbName' AND `TABLE_NAME`='dynamicprojects'");
    $dynrow = array_fill_keys(array_column($result->fetch_all(MYSQLI_ASSOC), 'COLUMN_NAME'), '');
    $dynrow['projectcolor'] = '#efefef';
    $dynrow['projectstart'] = date('Y-m-d');
    $dynrow['projectpriority'] = 3;
    $dynrow['projectstatus'] = 'ACTIVE';
    $dynrow_teams = array('teamid' => '');
    $dynrow_emps = array('userid' => '', 'position' => '');
    $dynrow['companyid'] = $_SESSION['filterings']['company'] ?? 0; //isset, or 0
    $dynrow['clientid'] = $_SESSION['filterings']['client'] ?? 0;
    $dynrow['clientprojectid'] = $_SESSION['filterings']['project'] ?? 0;
    $dynrow['level'] = 0;
	if($isTempalte){
		$dynrow['projectstart'] = '0000-00-00';
		$dynrow['isTemplate'] = 'TRUE';
	}
}
?>
<ul class="nav nav-tabs">
	<li class="active"><a data-toggle="tab" href="#projectBasics<?php echo $x; ?>">Basic</a></li>
	<li><a data-toggle="tab" href="#projectAdvanced<?php echo $x; ?>">Erweiterte Optionen</a></li>
</ul>
<div class="tab-content">
	<div id="projectBasics<?php echo $x; ?>" class="tab-pane fade in active"><br>
		<div class="row">
			<?php
			$filterings = array('company' => $dynrow['companyid'], 'client' => $dynrow['clientid'], 'project' => $dynrow['clientprojectid']); //5b28e37956a11
			include dirname(__DIR__).'/misc/select_project.php';
			?>
		</div>

		<div class="col-md-12"><small>*Auswahl ist Optional. Falls leer, entscheidet der Benutzer.</small><br><br></div>
		<div class="col-md-12"><label><?php echo mc_status('TASK'); ?> Task Name*</label>
			<input spellchecking="true" class="form-control required-field" type="text" name="name" placeholder="Bezeichnung" maxlength="55" value="<?php echo asymmetric_encryption('TASK', $dynrow['projectname'], $userID, $privateKey, $dynrow['v2']); ?>" />
		<br></div>
		<?php
		$modal_options = '';
		if($isDynamicProjectsAdmin == 'TRUE'){
			$result = $conn->query("SELECT id, firstname, lastname FROM UserData WHERE id IN (".implode(', ', $available_users).")");
		} else {
			$result = $conn->query("SELECT id, firstname, lastname FROM UserData WHERE id = $userID");
		}
		while ($row = $result->fetch_assoc()){ $modal_options .= '<option value="'.$row['id'].'" data-icon="user">'.$row['firstname'] .' '. $row['lastname'].'</option>'; }
		?>
		<div class="row">
			<div class="col-md-6">
				<label><?php echo $lang["EMPLOYEE"]; ?>/ Team*</label>
				<select class="select2-team-icons required-field" name="employees[]" multiple="multiple">
					<?php
					if($isDynamicProjectsAdmin != 'TRUE'){
						$result = str_replace('<option value="', '<option selected value="user;', $modal_options);
					} else {
						$result = str_replace('<option value="', '<option value="user;', $modal_options);
					}
					for($i = 0; $i < count($dynrow_emps); $i++){
						if($dynrow_emps[$i]['position'] == 'normal'){
							$result = str_replace('<option value="user;'.$dynrow_emps[$i]['userid'].'" ', '<option selected value="user;'.$dynrow_emps[$i]['userid'].'" ', $result);
						}
					}
					echo $result;
					if($isDynamicProjectsAdmin == 'TRUE'){
						$result = $conn->query("SELECT id, name FROM $teamTable");
						while ($row = $result->fetch_assoc()) {
							$selected = '';
							if(in_array($row['id'], $dynrow_teams)){
								$selected = 'selected';
							}
						echo '<option value="team;'.$row['id'].'" data-icon="group" '.$selected.' >'.$row['name'].'</option>';
						}
					}
					?>
				</select><br>
			</div>
			<div class="col-md-6">
				<label><?php echo $lang["LEADER"]; ?></label>
				<select class="js-example-basic-single" name="leader">
					<option value="">....</option>
					<?php
					$result = $modal_options;
					for($i = 0; $i < count($dynrow_emps); $i++){
						if($dynrow_emps[$i]['position'] == 'leader'){
							$result = str_replace('<option value="'.$dynrow_emps[$i]['userid'].'" ', '<option selected value="'.$dynrow_emps[$i]['userid'].'" ', $result);
						}
					}
					echo $result;
					?>
				</select><br>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4">
				<label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?>*</label>
				<select class="form-control js-example-basic-single" name="priority">
					<?php
					for($i = 1; $i < 6; $i++){
						$selected = $dynrow['projectpriority'] == $i ? 'selected' : '';
						echo '<option value="'.$i.'" '.$selected.'>'.$lang['PRIORITY_TOSTRING'][$i].'</option>';
					}
					?>
				</select>
			</div>
			<div class="col-md-4">
				<label>Geschätzte Zeit <a data-toggle="collapse" href="#estimateCollapse-<?php echo $x; ?>"><i class="fa fa-question-circle-o"></i></a></label>
				<input type="text" class="form-control" value="<?php echo $dynrow['estimatedHours']; ?>" name="estimatedHours" />
			</div>
			<div class="col-md-4">
				<label><?php echo $lang["BEGIN"]; ?>*</label>
				<input type='text' class="form-control datepicker" name='start' placeholder='Anfangsdatum' value="<?php echo $dynrow['projectstart']; ?>" />
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="collapse" id="estimateCollapse-<?php echo $x; ?>"><br>
					<div class="well">
						Die <strong>Geschätzte Zeit</strong> wird per default in Stunden angegeben. D.h. 120 = 120 Stunden. <br>
						Mit "m", "t", "w" oder "M" können genauere Angaben gemacht werden: z.B. 2M für 2 Monate, 7m = 7 Minuten, 4t = 4 Tage und 6w = 6 Wochen.<br>
						Konkret: "2M 3w 50" würde also für 2 Monate, 3 Wochen und 50 Stunden stehen. (Alle anderen Angaben werden gespeichert, aber vom Programm ignoriert)
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-12">
			<label>Tags</label>
			<select class="form-control js-example-tokenizer" name="projecttags[]" multiple="multiple">
				<?php
				$result = $conn->query("SELECT value FROM tags");
				while($result && ($row = $result->fetch_assoc())){ //5b34fa15e7a23
					echo '<option value="'.$row['value'].'">'.$row['value'].'</option>';
				}
				foreach(explode(',', $dynrow['projecttags']) as $tag){
					if($tag) echo '<option value="'.$tag.'" selected>'.$tag.'</option>';
				}
				?>
			</select><small>Tags werden durch ',' oder ' ' automatisch getrennt.</small><br><br>
		</div>
		<?php if(!$isTempalte): ?>
			<div class="col-md-12">
				<label><?php echo mc_status('TASK').' '.$lang["DESCRIPTION"]; ?>* <small>(Max. 15MB)</small></label>
				<textarea class="form-control projectDescriptionEditor required-field" name="description">
					<?php echo asymmetric_encryption('TASK', $dynrow['projectdescription'], $userID, $privateKey, $dynrow['v2']); ?>
				</textarea>
				<br>
				<label class="btn btn-default"><input type="file" name="newTaskFiles[]" style="display:none" multiple />Dateien Hochladen</label>
				<br>
			</div>
			<div class="row">
				<?php
				$result = $conn->query("SELECT uniqID, name, type FROM archive WHERE category = 'TASK' AND categoryID = '$x' ");
				while($row = $result->fetch_assoc()){
					echo '<div class="col-sm-6 checkbox">
					<label title="Löschen" onclick="$(this).parent().hide();"><input type="checkbox" name="deleteTaskFile[]" value="'.$row['uniqID'].'" style="visibility:hidden"/><i style="color:red" class="fa fa-times"></i></label> '
					.$row['name'].'.'.$row['type'].'</div>'; //if checked -> delete;
				}
				?>
			</div>
		<?php else: ?>
			<input type="hidden" id="isTemplate-<?php echo $x; ?>" name="isTemplate" value="true" />
		<?php endif; ?>
	</div>
	<div id="projectAdvanced<?php echo $x; ?>" class="tab-pane fade"><br>
		<div class="row">
			<div class="col-md-6">
				<label>Status*</label>
				<div class="input-group">
					<select class="form-control" name="status" >
						<option value="DEACTIVATED" <?php if($dynrow['projectstatus'] == "DEACTIVATED") echo 'selected'; ?>>Deaktiviert</option>
						<option value="ACTIVE" <?php if($dynrow['projectstatus'] == 'ACTIVE') echo 'selected'; ?>>Aktiv</option>
						<option value="DRAFT" <?php if($dynrow['projectstatus'] == 'DRAFT') echo 'selected'; ?>>Entwurf</option>
						<option value="COMPLETED" <?php if($dynrow['projectstatus'] == 'COMPLETED') echo 'selected'; ?>>Abgeschlossen</option>
					</select>
					<span class="input-group-addon text-warning"><?php echo $lang["DYNAMIC_PROJECTS_PERCENTAGE_FINISHED"]; ?></span>
					<input type='number' class="form-control" name='completed' value="<?php echo $dynrow['projectpercentage']; ?>" min="0" max="100" step="1"/>
				</div><br>
			</div>
			<div class="col-md-6">
				<div class="col-md-6">
					<?php if($isDynamicProjectsAdmin == 'TRUE'): ?>
						<label>Skill Minimum</label>
						<input type="range" step="10" value="<?php echo $dynrow['level']; ?>" oninput="document.getElementById('projectskill-<?php echo $x; ?>').value = this.value;"><br>
					<?php endif; ?>
				</div>
				<div class="col-md-6">
					<?php  if($isDynamicProjectsAdmin == 'TRUE'): ?>
						<label>Level</label>
						<input id="projectskill-<?php echo $x; ?>" type="number" class="form-control" name="projectskill" value="<?php echo $dynrow['level']; ?>"><br>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4">
				<label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_COLOR"]; ?></label>
				<input type="color" class="form-control" value="<?php echo $dynrow['projectcolor']; ?>" name="color"><br>
			</div>
			<div class="col-md-4">
			<?php  if($isDynamicProjectsAdmin == 'TRUE'): ?>
				<label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PARENT"]; ?>:</label>
				<select class="form-control js-example-basic-single" name="parent">
					<option value=''>Keines</option>
					<?php
					$result = $conn->query("SELECT projectid, projectname FROM dynamicprojects");
					while ($row = $result->fetch_assoc()) {
						$selected = ($row['projectid'] == $dynrow['projectparent']) ? 'selected' : '';
						echo '<option '.$selected.' value="'.$row["projectid"].'" >'.$row["projectname"].'</option>';
					}
					?>
				</select><br>
				<?php endif; ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
			<?php  if($isDynamicProjectsAdmin == 'TRUE'): ?>
				<label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OPTIONAL_EMPLOYEES"]; ?></label>
				<select class="select2-team-icons" name="optionalemployees[]" multiple="multiple">
					<?php
					$result = $modal_options;
					for($i = 0; $i < count($dynrow_emps); $i++){
						if($dynrow_emps[$i]['position'] == 'optional')
						$result = str_replace('<option value="'.$dynrow_emps[$i]['userid'].'" ', '<option selected value="'.$dynrow_emps[$i]['userid'].'" ', $result);
					}
					echo $result;
					?>
				</select>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div><!-- /tab-content -->
