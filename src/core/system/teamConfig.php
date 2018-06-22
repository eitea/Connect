<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID); ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
$teamID = 0;
if(isset($_POST['createTeam']) && !empty($_POST['createTeam_name'])){
    $name = test_input($_POST['createTeam_name']);
	$email = test_input($_POST['createTeam_email']);
    $leader = intval($_POST['leader']);
    $replacement = intval($_POST['replacement']);
    $isDepartment = empty($_POST['create_department']) ? 'FALSE' : 'TRUE';
    $conn->query("INSERT INTO teamData (name, leader, leaderreplacement, isDepartment, email) VALUES('$name', '$leader', '$replacement', '$isDepartment', '$email')");
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
    $conn->query("UPDATE teamData SET name = '$name', email='$email' WHERE id = $teamID");
    if(!$conn->error){
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
} elseif(!empty($_POST['department_unflag'])){
    $teamID = intval($_POST['department_unflag']);
    $conn->query("UPDATE teamData SET isDepartment = 'FALSE' WHERE id = $teamID");
    if(!$conn->error){
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
} elseif(!empty($_POST['department_flag'])){
    $teamID = intval($_POST['department_flag']);
    //$result = $conn->query("SELECT id FROM teamData WHERE isDepartment = 'TRUE'");
    $result = $conn->query("SELECT userID FROM relationship_team_user WHERE teamID = $teamID AND userID IN
        (SELECT userID FROM relationship_team_user, teamData WHERE teamData.id = teamID AND teamData.isDepartment = 'TRUE')");
    if($result && $result->num_rows > 0){
        $row = $result->fetch_assoc();
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$userID_toName[$row['userID']].' befindet sich bereits in einer anderen Abteilung.</div>';
        $result->free();
    } elseif($result) {
        $conn->query("UPDATE teamData SET isDepartment = 'TRUE' WHERE id = $teamID");
    }
}

$activeTab = $teamID;
if($conn->error){
	echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
}
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
    $result = $conn->query("SELECT id, name, isDepartment, email FROM teamData");
    while($result && ($row = $result->fetch_assoc())):
        $teamID = $row['id'];
        ?>
        <form method="POST">
            <input type="hidden" name="teamID" value="<?php echo $teamID; ?>">
            <div class="panel panel-default">
				<div class="panel-heading container-fluid">
					<div class="col-xs-6"><a data-toggle="collapse" href="#teamCollapse-<?php echo $teamID; ?>"><?php echo $row['name']; ?></a>
						<small style="padding-left:35px;"><?php echo $row['email']; //5b28952ad8a9a ?></small>
					</div>
					<div class="col-xs-6 text-right">
						<?php $taskResult = $conn->query("SELECT projectid FROM dynamicprojectsteams WHERE teamid = $teamID");
						if($taskResult->num_rows < 1): ?>
						<button type="submit" class="btn-empty" style="color:red;" title="Löschen" name="removeTeam" value="<?php echo $teamID; ?>"><i class="fa fa-trash-o"></i></button>
						<?php endif; ?>
						<button type="button" class="btn-empty" style="color:brown;" title="Bearbeiten" data-toggle="modal" data-target="#rename-team-<?php echo $teamID; ?>" ><i class="fa fa-pencil"></i></button>
						<button type="submit" class="btn-empty" style="color:#0078e7;" title="Speichern" name="saveTeam" value="<?php echo $teamID; ?>"><i class="fa fa-floppy-o"></i></button>
						<?php if($row['isDepartment'] == 'TRUE'): ?>
							<button type="submit" class="btn-empty" style="color:#00d608;" title="Abteilung entfernen" name="department_unflag" value="<?php echo $teamID; ?>"><i class="fa fa-share-alt"></i></button>
						<?php else: ?>
							<button type="submit" class="btn-empty" style="color:#a0a0a0;" title="Als Abteilung markieren" name="department_flag" value="<?php echo $teamID; ?>"><i class="fa fa-share-alt"></i></button>
						<?php endif; ?>
					</div>
				</div>
                <div class="collapse <?php if($teamID == $activeTab) echo 'in'; ?>" id="teamCollapse-<?php echo $teamID; ?>">
                    <div class="panel-body container-fluid">
                        <?php
                        $userResult = $conn->query("SELECT userID, skill FROM relationship_team_user WHERE teamID = $teamID");
                        while($userResult && ($userRow = $userResult->fetch_assoc())){
                            echo '<div class="col-xs-8 col-md-3">';
                            echo '<input type="hidden" name="saveTeam_users[]" value="'.$userRow['userID'].'">';
                            echo '<button type="submit" style="background:none;border:none" name="removeMember" value="'.$userRow['userID'].'"><i style="color:red" class="fa fa-times"></i></button>';
                            echo $userID_toName[$userRow['userID']];
                            echo '</div><div class="col-xs-4 col-md-1">';
                            echo '<select name="saveTeam_skill_'.$userRow['userID'].'" style="max-width:75px;display:inline;">'.
                            str_replace('value="'.$userRow['skill'].'">', 'value="'.$userRow['skill'].'" selected>', $percentage_select).'</select>';
                            echo '</div>';
                        }
                        echo mysqli_error($conn);
                        ?>
                        <div class="col-md-12 text-right"><a class="btn btn-default" data-toggle="modal" data-target=".addTeamMember_<?php echo $teamID; ?>" title="Add Team Member">+</a></div>
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
        <div id="rename-team-<?php echo $teamID; ?>" class="modal fade">
            <div class="modal-dialog modal-content modal-md">
                <form method="POST">
                    <div class="modal-header h4"><?php echo $row['name']; ?> Editieren</div>
                    <div class="modal-body">
                        <label>Name</label>
                        <input type="text" name="teamName" value="<?php echo $row['name']; ?>" class="form-control">
						<br>
						<label>Email</label>
                        <input type="text" name="teamMail" value="<?php echo $row['email']; ?>" class="form-control">
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
                <div class="col-md-6">
                    <label>Name</label>
                    <input type="text" class="form-control" name="createTeam_name" placeholder="Name" /><br>
                </div>
				<div class="col-md-6">
                    <label>Name</label>
                    <input type="email" class="form-control" name="createTeam_email" placeholder="E-Mail" /><br>
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
