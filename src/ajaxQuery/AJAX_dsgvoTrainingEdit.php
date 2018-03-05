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
$allowOverwrite = $row["allowOverwrite"];
$random = $row["random"];
$moduleID = $row["moduleID"];
$answerEveryNDays = $row["answerEveryNDays"];

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
        <div class="modal-header"><i class="fa fa-cube"></i> Aufgabenstellung/Schulung Bearbeiten</div>
        <div class="modal-body">
            <label>Name*</label>
            <input type="text" class="form-control" name="name" placeholder="Name des Sets" value="<?php echo $name ?>"/>
            <label>Set*</label>
            <select class="js-example-basic-single" name="module" required>
                <?php 
                $result = $conn->query("SELECT * FROM dsgvo_training_modules");
                while ($result && ($row = $result->fetch_assoc())) {
                    $name = $row["name"];
                    $id = $row["id"];
                    $selected = ($id == $moduleID)?"selected":"";
                    echo "<option $selected value='$id'>$name</option>";
                }
                ?>
            </select>
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
            <br/><label>Überschreiben von Antworten</label><br/>
            <label><input type="radio" name="allowOverwrite" value="TRUE" <?php if($allowOverwrite == 'TRUE') echo "checked" ?> />Alte Antworten werden durch das erneute Beantworten überschrieben.</label><br/>
            <label><input type="radio" name="allowOverwrite" value="FALSE" <?php if($allowOverwrite == 'FALSE') echo "checked" ?> />User können das Training wiederholen, aber es bleiben die alten Antworten bestehen.</label>
            <br/><label>Anordnung der Antworten</label><br/>
            <label><input type="radio" name="random" value="TRUE" <?php if($random == 'TRUE') echo "checked" ?> />Antwortmöglichkeiten zufällig anordnen.</label><br/>
            <label><input type="radio" name="random" value="FALSE" <?php if($random == 'FALSE') echo "checked" ?> />Antwortmöglichkeiten wie in der Frage belassen.</label>
            <br /><label>Wiederholungsintervall in Tagen</label>
            <input type="number" title="Kann nur aktiviert werden, wenn das Überschreiben von Antworten erlaubt ist und die Beantwortung bei Login erfolgt" min="1" max="365" class="form-control" name="answerEveryNDays" value="<?php echo $answerEveryNDays; ?>" <?php if($onLogin == 'FALSE' || $allowOverwrite == 'FALSE') echo "disabled" ?> />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-warning" name="editTraining" value="<?php echo $trainingID; ?>">Training bearbeiten</button>
        </div>
      </div>
    </div>
</form>
<script>
$("input[name=onLogin][value='TRUE']").change(function(event){
    if($("input[name=allowOverwrite][value='TRUE']").is(':checked'))
        $("input[name=answerEveryNDays]").attr("disabled",false)
})
$("input[name=onLogin][value='FALSE']").change(function(event){
    $("input[name=answerEveryNDays]").attr("disabled",true)
})
$("input[name=allowOverwrite][value='TRUE']").change(function(event){
    if($("input[name=onLogin][value='TRUE']").is(':checked'))
        $("input[name=answerEveryNDays]").attr("disabled",false)
})
$("input[name=allowOverwrite][value='FALSE']").change(function(event){
    $("input[name=answerEveryNDays]").attr("disabled",true)
})
</script>