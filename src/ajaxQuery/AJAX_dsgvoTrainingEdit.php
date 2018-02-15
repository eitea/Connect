<?php 
if (!isset($_REQUEST["trainingID"])){
    echo "error";
    die();
}
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

$trainingID = $_REQUEST["trainingID"];
$row = $conn->query("SELECT * FROM dsgvo_training WHERE id = $trainingID")->fetch_assoc();
$name = $row["name"];
$version = $row["version"];
$companyID = $row["companyID"];
$onLogin = $row["onLogin"];

$userArray = array();
$teamArray = array();
$result = $conn->query("SELECT userID id FROM dsgvo_training_user_relations WHERE trainingID = $trainingID");
while($row = $result->fetch_assoc()){
    $userArray[] = $row["id"];
}
$result = $conn->query("SELECT teamID id FROM dsgvo_training_team_relations WHERE trainingID = $trainingID");
while($row = $result->fetch_assoc()){
    $teamArray[] = $row["id"];
}
?>
 <form method="POST">
 <div class="modal fade">
      <div class="modal-dialog modal-content modal-md">
        <div class="modal-header">Aufgabenstellung/Schulung Bearbeiten</div>
        <div class="modal-body">
            <label>Name*</label>
            <input type="text" class="form-control" name="name" placeholder="Name des Sets" value="<?php echo $name ?>"/>
            <label>Version</label>
            <input type="number" class="form-control" name="version" placeholder="1" min="1" step="1" value="<?php echo $version ?>" />
            <label>Zugeordnete Personen</label>
            <label><?php echo $lang["EMPLOYEE"]; ?>/ Team*</label>
                <select class="select2-team-icons required-field" name="employees[]" multiple="multiple">
                <?php
                $result = $conn->query("SELECT UserData.id id, firstname, lastname FROM relationship_company_client INNER JOIN UserData on UserData.id = relationship_company_client.userID WHERE companyID = $companyID GROUP BY UserData.id");
                while($row = $result->fetch_assoc()){
                    $selected = '';
                    if(in_array($row['id'], $userArray)){
                        $selected = 'selected';
                    }
                    echo '<option value="user;'.$row['id'].'" data-icon="user" '.$selected.' >'.$row['firstname'].' '.$row['lastname'].'</option>';
                }
                $result = $conn->query("SELECT id, name FROM $teamTable");
                while ($row = $result->fetch_assoc()) {
                    $selected = '';
                    if(in_array($row['id'], $teamArray)){
                        $selected = 'selected';
                    }
                    echo '<option value="team;'.$row['id'].'" data-icon="group" '.$selected.' >'.$row['name'].'</option>';
                }
                ?>
            </select><br>
            <label>Beantwortungsart</label><br/>
            <label><input type="radio" name="onLogin" value="TRUE" <?php if($onLogin == 'TRUE') echo "checked" ?> />Beantwortung bei Login</label><br/>
            <label><input type="radio" name="onLogin" value="FALSE" <?php if($onLogin == 'FALSE') echo "checked" ?> />Beantwortung freiwillig</label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-warning" name="editTraining" value="<?php echo $trainingID; ?>">Training bearbeiten</button>
        </div>
      </div>
    </div>
</form>