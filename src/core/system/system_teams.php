<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID); ?>
<?php
$teamID = 0;
if(isset($_POST['createTeam']) && !empty($_POST['createTeam_name'])){
    $name = test_input($_POST['createTeam_name']);
    $conn->query("INSERT INTO teamData (name) VALUES('$name')");
    $teamID = mysqli_insert_id($conn);
    foreach($_POST['createTeam_members'] AS $user){
        $user = intval($user);
        $skill = intval($_POST['createTeam_skill_'.$user]);
        $conn->query("INSERT INTO $teamRelationshipTable(teamID, userID, skill) VALUES($teamID, $user, $skill)");
    }
} elseif(isset($_POST['removeTeam'])){
    $teamID = intval($_POST['removeTeam']);
    $conn->query("DELETE FROM teamData WHERE id = $teamID");
} elseif(isset($_POST['removeMember']) && !empty($_POST['teamID'])){
    $teamID = intval($_POST['teamID']);
    $user = intval($_POST['removeMember']);
    $conn->query("DELETE FROM $teamRelationshipTable WHERE userID = $user AND teamID = $teamID");
} elseif(isset($_POST['saveTeam']) && !empty($_POST['teamID'])){
    $teamID = intval($_POST['teamID']);
    foreach($_POST['saveTeam_users'] as $user){
        $user = intval($user);
        $skill = intval($_POST['saveTeam_skill_'.$user]);
        $conn->query("UPDATE $teamRelationshipTable SET skill = $skill WHERE teamID = $teamID AND userID = $user");
        echo $conn->error;
    }
} elseif(isset($_POST['hire']) && !empty($_POST['userIDs'])){
    $teamID = intval($_POST['hire']);
    foreach($_POST['userIDs'] as $user){
        $user = intval($user);
        $skill = intval($_POST['hire_'.$user]);
        $conn->query("INSERT INTO $teamRelationshipTable (teamID, userID, skill) VALUES ($teamID, $user, $skill)");
    }
}
$activeTab = $teamID;
echo mysqli_error($conn);

$percentage_select = '';
for($i = 0; $i < 11; $i++){
    $percentage_select .= '<option value="'.($i*10).'">'.($i*10).'%</option>';
}
?>

<div class="page-header">
    <h3>Team <div class="page-header-button-group"><button type="button" data-toggle="modal" data-target=".bookingModal-newTeam" title="<?php echo $lang['ADD']; ?>" class="btn btn-default">+</button></div></h3>
</div>

<?php
$result = $conn->query("SELECT * FROM teamData");
while($result && ($row = $result->fetch_assoc())):
    $teamID = $row['id'];
    ?>
    <form method="POST">
        <input type="hidden" name="teamID" value="<?php echo $teamID; ?>">
        <div class="panel panel-default">
            <div class="panel-heading container-fluid">
                <div class="col-xs-6"><a data-toggle="collapse" href="#teamCollapse-<?php echo $teamID; ?>"><?php echo $row['name']; ?></a></div>
                <div class="col-xs-6 text-right"><button type="submit" style="background:none;border:none;color:#d90000;" name="removeTeam" value="<?php echo $teamID; ?>"><i class="fa fa-trash-o"></i></button>
                    <button type="submit" style="background:none;border:none;color:#0078e7;" name="saveTeam" value="<?php echo $teamID; ?>"><i class="fa fa-floppy-o"></i></button></div>
            </div>
            <div class="collapse <?php if($teamID == $activeTab) echo 'in'; ?>" id="teamCollapse-<?php echo $teamID; ?>">
                <div class="panel-body container-fluid">
                    <?php
                    $userResult = $conn->query("SELECT userID, skill FROM teamRelationshipData WHERE teamID = $teamID");
                    while($userResult && ($userRow = $userResult->fetch_assoc())){
                        echo '<div class="col-md-4">';
                        echo '<input type="hidden" name="saveTeam_users[]" value="'.$userRow['userID'].'">';
                        echo '<button type="submit" style="background:none;border:none" name="removeMember" value="'.$userRow['userID'].'"><i style="color:red" class="fa fa-times"></i></button>';
                        echo $userID_toName[$userRow['userID']];
                        echo ' <select name="saveTeam_skill_'.$userRow['userID'].'" style="max-width:75px;display:inline;">'
                        .str_replace('value="'.$userRow['skill'].'">', 'value="'.$userRow['skill'].'" selected>', $percentage_select).'</select>';
                        echo '</div>';
                    }
                    ?>
                    <div class="col-md-12 text-right"><a class="btn btn-default" data-toggle="modal" data-target=".addTeamMember_<?php echo $teamID; ?>" title="Add Team Member">+</a></div>
                </div>
            </div>
        </div>

        <div class="modal fade addTeamMember_<?php echo $teamID; ?>">
            <div class="modal-dialog modal-content modal-md">
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
                            $sql = "SELECT id, firstname, lastname FROM UserData WHERE id NOT IN (SELECT DISTINCT userID FROM $teamRelationshipTable WHERE teamID = $teamID)";
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
            </div>
        </div>
    </form>
<?php endwhile; ?>

<form method="post">
    <div class="modal fade bookingModal-newTeam" tabindex="-1" role="dialog" aria-labelledby="newTeamModal">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">New Team</h4>
                </div>
                <div class="modal-body">
                    <label>Name</label>
                    <input type="text" class="form-control" name="createTeam_name" placeholder="Name" /><br>
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
                        $result = $conn->query("SELECT id, firstname, lastname FROM UserData WHERE id IN (".implode(', ', $available_users).")");
                        while($result && ($row = $result->fetch_assoc())){
                            echo '<div class="col-sm-4"><label><input type="checkbox" name="createTeam_members[]" value="'.$row['id'].'" />'.$row['firstname'].' '.$row['lastname'].'</label>
                            </div><div class="col-sm-2"><select class="form-control" name="createTeam_skill_'.$row['id'].'">'.$percentage_select.'</select></div>';
                        }
                        ?>
                    </div>
                    <div class="row"><small>* Der Skill Level regelt die automatische Task Zuweisung.
                        Hat der Benutzer im Team einen Skill von 40% und man erstellt einen Task für ein Team mit einem minimum Skill Level von 50%,
                        dann wird dieser Task nur bei Benutzern angezeigt, die ein Minimum Skill Level in diesem Team von 50% oder höher aufweisen.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" name="createTeam" value="<?php echo $x; ?>"><?php echo $lang['ADD']; ?></button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
