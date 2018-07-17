<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID); ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
$teamID = 0;
if(isset($_POST['createTeam']) && !empty($_POST['createTeam_name'])){
    $name = test_input($_POST['createTeam_name']);
	$email = test_input($_POST['createTeam_email']);
	$emailName = test_input($_POST['createTeam_emailName']); //5b34d75a75691
    $leader = intval($_POST['leader']);
    $replacement = intval($_POST['replacement']);
	$signature = test_input($_POST['createTeam_signature']);
    $isDepartment = empty($_POST['create_department']) ? 'FALSE' : 'TRUE';
    $conn->query("INSERT INTO teamData (name, leader, leaderreplacement, isDepartment, email, emailName, emailSignature)
	VALUES('$name', '$leader', '$replacement', '$isDepartment', '$email', '$emailName', '$signature')");
    $teamID = mysqli_insert_id($conn);
	if(isset($_POST['createTeam_members'])){
		foreach($_POST['createTeam_members'] AS $user){
			$user = intval($user);
			$skill = intval($_POST['createTeam_skill_'.$user]);
			$conn->query("INSERT INTO relationship_team_user(teamID, userID, skill) VALUES($teamID, $user, $skill)");
		}
	} else {
		showWarning("Es wurden keine Teammitglieder ausgewählt");
	}
    if(!$conn->error){
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_CREATE'].'</div>';
    }
} elseif(isset($_POST['removeTeam'])){
    $teamID = intval($_POST['removeTeam']);
    $conn->query("UPDATE UserData SET departmentID = NULL WHERE departmentID = $teamID");
    $conn->query("DELETE FROM teamData WHERE id = $teamID");
    if(!$conn->error){
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
    }
} elseif(isset($_POST['removeMember']) && !empty($_POST['teamID'])){
    $teamID = intval($_POST['teamID']);
    $user = intval($_POST['removeMember']);
    $conn->query("DELETE FROM relationship_team_user WHERE userID = $user AND teamID = $teamID");
    if(!$conn->error){
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
    }
} elseif(isset($_POST['saveTeam']) && !empty($_POST['teamID'])){
    $teamID = intval($_POST['teamID']);
    foreach($_POST['saveTeam_users'] as $user){
        $user = intval($user);
        $skill = intval($_POST['saveTeam_skill_'.$user]);
        $conn->query("UPDATE relationship_team_user SET skill = $skill WHERE teamID = $teamID AND userID = $user");
    }
    if(!$conn->error){
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
	$val = intval($_POST['teamCompany']);
	$leader = intval($_POST['teamLeader']);
	$replacement = intval($_POST['teamReplacement']);
	$conn->query("UPDATE teamData SET companyID = $val, leader = $leader, leaderreplacement = $replacement WHERE id = $teamID");
} elseif(isset($_POST['hire']) && !empty($_POST['userIDs'])){
    $teamID = intval($_POST['hire']);
    foreach($_POST['userIDs'] as $user){
        $user = intval($user);
        $skill = intval($_POST['hire_'.$user]);
        $conn->query("INSERT INTO relationship_team_user (teamID, userID, skill) VALUES ($teamID, $user, $skill)");
    }
    if(!$conn->error){
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
    }
} elseif(isset($_POST['changeTeamName']) && !empty($_POST['teamName'])){
    $teamID = intval($_POST['changeTeamName']);
    $name = test_input($_POST['teamName']);
	$email = test_input($_POST['teamMail']); //5b28952ad8a9a
	$emailName = test_input($_POST['teamMailName']); //5b34d75a75691
	$signature = test_input($_POST['changeTeam_signature']);
    $conn->query("UPDATE teamData SET name = '$name', email='$email', emailName='$emailName', emailSignature = '$signature' WHERE id = $teamID");
	if(empty($_POST['isDepartment'])){
	    $conn->query("UPDATE teamData SET isDepartment = 'FALSE' WHERE id = $teamID");
	} else {
	    $result = $conn->query("SELECT userID FROM relationship_team_user WHERE teamID = $teamID AND userID IN
	        (SELECT userID FROM relationship_team_user, teamData WHERE teamData.id = teamID AND teamData.id != $teamID AND teamData.isDepartment = 'TRUE')");
	    if($result && $result->num_rows > 0){
	        $row = $result->fetch_assoc();
	        echo showError($userID_toName[$row['userID']].' befindet sich bereits in einer anderen Abteilung.');
	        $result->free();
	    } elseif($result) {
	        $conn->query("UPDATE teamData SET isDepartment = 'TRUE' WHERE id = $teamID");
	    }
	}
    if(!$conn->error){
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
}

if($conn->error){ showError($conn->error.__LINE__); }

$activeTab = $teamID;
$percentage_select = '';
for($i = 0; $i < 11; $i++){
    $percentage_select .= '<option value="'.($i*10).'">'.($i*10).'%</option>';
}
?>

<div class="page-header">
    <h3>Team <div class="page-header-button-group">
        <button type="button" data-toggle="modal" data-target=".bookingModal-newTeam" title="<?php echo $lang['ADD']; ?>" class="btn btn-default">+</button>
    </div></h3>
</div>

<div class="container-fluid">
    <?php
    $result = $conn->query("SELECT id, name, isDepartment, email, emailName, leader, leaderreplacement, companyID, emailSignature FROM teamData");
    while($result && ($row = $result->fetch_assoc())):
        $teamID = $row['id'];
        ?>

        <form method="POST">
            <input type="hidden" name="teamID" value="<?php echo $teamID; ?>">
            <div class="panel panel-default">
				<div class="panel-heading container-fluid">
					<div class="col-xs-6"><a data-toggle="collapse" href="#teamCollapse-<?php echo $teamID; ?>"><?php echo $row['name']; ?></a>
						<?php if($row['isDepartment'] == 'TRUE') echo '<small style="padding-left:35px;color:green;">Abteilung</small>'; ?>
						<small style="padding-left:35px;"> <?php echo $row['email']; //5b28952ad8a9a ?></small>
					</div>
					<div class="col-xs-6 text-right">
						<?php $taskResult = $conn->query("SELECT projectid FROM dynamicprojectsteams WHERE teamid = $teamID");
						if($taskResult->num_rows < 1): ?>
							<button type="submit" class="btn-empty" style="color:red;" title="Löschen" name="removeTeam" value="<?php echo $teamID; ?>"><i class="fa fa-trash-o"></i></button>
						<?php endif; ?>
						<button type="button" class="btn-empty" title="Bearbeiten" data-toggle="modal" data-target="#edit-team-<?php echo $teamID; ?>" ><i class="fa fa-cog"></i></button>
						<button type="submit" class="btn-empty" title="Speichern" name="saveTeam" value="<?php echo $teamID; ?>"><i class="fa fa-floppy-o"></i></button>
					</div>
				</div>
                <div class="collapse <?php if($teamID == $activeTab) echo 'in'; ?>" id="teamCollapse-<?php echo $teamID; ?>">
                    <div class="panel-body container-fluid">
						<!-- GENERAL OPTIONS -->
						<?php $result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
						if($result_fc && $result_fc->num_rows > 1): ?>
							<div class="row">
								<div class="col-md-3">
									<label>Mandant</label>
									<select class="js-example-basic-single" name="teamCompany">
										<?php
									      while($result_fc && ($row_fc = $result_fc->fetch_assoc())){
									        $checked = $row['companyID'] == $row_fc['id'] ? 'selected' : '';
									        echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
									      }
									      echo '</select>';

										?>
									</select>
								</div>
								<div class="col-md-3">
									<label><?php echo $lang['LEADER'] ?></label>
									<select name="teamLeader" class="js-example-basic-single">
										<option value="">...</option>
										<?php
										foreach($userID_toName as $id=>$name){
											$selected = $id == $row['leader'] ? 'selected' : '';
											echo "<option $selected value='$id'>$name</option>";
										}
										?>
									</select><br>
								</div>
								<div class="col-md-3">
									<label><?php echo $lang['LEADER_REPLACEMENT'] ?></label>
									<select name="teamReplacement" class="js-example-basic-single">
										<option value="">...</option>
										<?php
										foreach($userID_toName as $id=>$name){
											$selected = $id == $row['leaderreplacement'] ? 'selected' : '';
											echo "<option $selected value='$id'>$name</option>";
										}
										?>
									</select><br>
								</div>
							</div>
						<?php endif; ?>
						<br> <!-- MEMBERS -->
                        <?php
                        $userResult = $conn->query("SELECT userID, skill FROM relationship_team_user WHERE teamID = $teamID");
                        while($userResult && ($userRow = $userResult->fetch_assoc())){
                            echo '<div class="col-xs-8 col-md-2">';
                            echo '<input type="hidden" name="saveTeam_users[]" value="'.$userRow['userID'].'">';
                            echo '<button type="submit" style="background:none;border:none" name="removeMember" value="'.$userRow['userID'].'"><i style="color:red" class="fa fa-times"></i></button>';
                            echo $userID_toName[$userRow['userID']];
                            echo '</div><div class="col-xs-4 col-md-2">';
                            echo '<select name="saveTeam_skill_'.$userRow['userID'].'" class="form-control">'.
                            str_replace('value="'.$userRow['skill'].'">', 'value="'.$userRow['skill'].'" selected>', $percentage_select).'</select>';
                            echo '</div>';
                        }
                        echo mysqli_error($conn);
                        ?>
                        <div class="col-md-12 text-right"><br>
							<a class="btn btn-default" data-toggle="modal" data-target=".addTeamMember_<?php echo $teamID; ?>" title="Add Team Member">Mitglied Hinzufügen</a>
						</div>
                    </div>
                </div>
            </div>
        </form>

        <div class="modal fade addTeamMember_<?php echo $teamID; ?>">
            <div class="modal-dialog modal-content modal-md">
                <form method="POST">
                    <div class="modal-header"></div>
                    <div class="modal-body">
                        <table class="table table-hover">
                            <thead>
                                <th></th>
                                <th>Name</th>
                                <th>Skill</th>
                            </thead>
                            <tbody>
                                <?php
                                if($row['isDepartment'] == 'TRUE'){
                                    $sql = "SELECT id, firstname, lastname FROM UserData WHERE id NOT IN (SELECT DISTINCT userID FROM relationship_team_user WHERE teamID = $teamID)
                                    AND id NOT IN (SELECT DISTINCT userID FROM relationship_team_user, teamData WHERE teamData.id = teamID AND isDepartment = 'TRUE')";
                                } else {
                                    $sql = "SELECT id, firstname, lastname FROM UserData WHERE id NOT IN (SELECT DISTINCT userID FROM relationship_team_user WHERE teamID = $teamID)";
                                }
                                $res_addmem = mysqli_query($conn, $sql);
                                while ($res_addmem && ($row_addmem = $res_addmem->fetch_assoc())) {
                                    echo '<tr>';
                                    echo '<td><input type="checkbox" name="userIDs[]" value="'.$row_addmem['id'].'" ></td>';
                                    echo '<td>'.$row_addmem['firstname'].' '. $row_addmem['lastname'] .'</td>';
                                    echo '<td><select class="form-control" name="hire_'.$row_addmem['id'].'">'.$percentage_select.'</select></td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" name="hire" value="<?php echo $teamID; ?>">Benutzer einstellen</button>
                    </div>
                </form>
            </div>
        </div>
        <div id="edit-team-<?php echo $teamID; ?>" class="modal fade">
            <div class="modal-dialog modal-content modal-md">
                <form method="POST">
                    <div class="modal-header h4"><?php echo $row['name']; ?> Editieren</div>
                    <div class="modal-body">
						<div class="row">
							<div class="col-md-6">
								<label>Name</label>
								<input type="text" class="form-control" name="teamName" value="<?php echo $row['name']; ?>" />
							</div>
							<div class="col-md-6">
								<br>
								<label>
									<input type="checkbox" name="isDepartment" value="1" <?php if($row['isDepartment'] == 'TRUE') echo 'checked'; ?> />
									Abteilung
								</label>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<label>E-Mail</label>
								<input type="email" class="form-control" name="teamMail" value="<?php echo $row['email']; ?>" />
							</div>
							<div class="col-md-6">
								<label>E-Mail Anzeigename</label>
								<input type="text" class="form-control" name="teamMailName" value="<?php echo $row['emailName']; ?>" maxlength="45"/>
							</div>
						</div>
						<div class="col-md-12"><br>
							<label>E-Mail Signature</label>
							<textarea name="changeTeam_signature" rows="4" class="form-control"><?php echo $row['emailSignature']; ?></textarea>
						</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" name="changeTeamName" value="<?php echo $teamID; ?>"><?php echo $lang['SAVE']; ?></button>
                    </div>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<form method="POST">
    <div class="modal fade bookingModal-newTeam">
        <div class="modal-dialog modal-lg modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Team</h4>
            </div>
            <div class="modal-body">
				<div class="row">
					<div class="col-md-6">
						<label>Name</label>
						<input type="text" class="form-control" name="createTeam_name" placeholder="Name" />
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
	                    <label>E-Mail</label>
	                    <input type="email" class="form-control" name="createTeam_email" placeholder="E-Mail" />
	                </div>
					<div class="col-md-6">
	                    <label>E-Mail Anzeigename</label>
	                    <input type="text" class="form-control" name="createTeam_emailName" placeholder="Anzeigename" maxlength="45"/>
	                </div>
				</div>
                <div class="row">
                    <div class="col-md-6">
                        <label><?php echo $lang['LEADER'] ?></label>
                        <select name="leader" class="js-example-basic-single">
                            <option value="">...</option>
                            <?php
                            foreach($userID_toName as $id=>$name){
                                echo "<option value='$id'>$name</option>";
                            }
                            ?>
                        </select><br>
                    </div>
                    <div class="col-md-6">
                        <label><?php echo $lang['LEADER_REPLACEMENT'] ?></label>
                        <select name="replacement" class="js-example-basic-single">
                            <option value="">...</option>
                            <?php
                            foreach($userID_toName as $id=>$name){
                                echo "<option value='$id'>$name</option>";
                            }
                            ?>
                        </select><br>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <label>Benutzer</label>
                    </div>
                    <div class="col-sm-2">
                        <label>Skill-Level*</label>
                    </div>
                </div>
                <div class="row">
                    <?php
                    foreach($userID_toName as $id=>$name){
                        echo '<div class="col-xs-4"><input type="checkbox" name="createTeam_members[]" value="'.$id.'" />'.$name.
                        '</div><div class="col-sm-2"><select class="form-control" name="createTeam_skill_'.$id.'">'.$percentage_select.'</select></div>';
                    }
                    ?>
                </div>
                <div class="row"><small>* Der Skill Level regelt die automatische Task Zuweisung.
                    Wird ein Task mit Skill Level 50% ersetellt und ein Team zugewiesen, so wird dieser Task nur den Team-Mitgliedern angezeigt,
                    die ebenfalls einen Skill Level von 50% oder höher besitzen.</small>
                </div>
				<div class="col-md-12"><br>
					<label>E-Mail Signatur</label>
					<textarea name="createTeam_signature" rows="4" class="form-control"></textarea>
				</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="createTeam" value="<?php echo $x; ?>"><?php echo $lang['ADD']; ?></button>
            </div>
        </div>
    </div>
</form>
<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
