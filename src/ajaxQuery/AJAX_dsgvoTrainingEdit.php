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
?>
 <form method="POST">
 <div class="modal fade">
      <div class="modal-dialog modal-content modal-md">
        <div class="modal-header">Aufgabenstellung/Schulung Bearbeiten</div>
        <div class="modal-body">
            <label>Name*</label>
            <input type="text" class="form-control" name="name" placeholder="Name des Sets" value="<?php echo $name ?>"/>
            <label>Version</label>
            <input type="number" class="form-control" name="version" placeholder="1" value="<?php echo $version ?>" />
            <label>Zugeordnete Personen</label>
            <label><?php echo $lang["EMPLOYEE"]; ?>/ Team*</label>
            <!-- <select class="select2-team-icons required-field" name="employees[]" multiple="multiple">
                <?php
                $result_emp = str_replace('<option value="', '<option value="user;', $modal_options); //append 'user;' before every value
                for($i = 0; $i < count($dynrow_emps); $i++){
                    if($dynrow_emps[$i]['position'] == 'normal'){
                        $result_emp = str_replace('<option value="user;'.$dynrow_emps[$i]['userid'].'" ', '<option selected value="user;'.$dynrow_emps[$i]['userid'].'" ', $result_emp);
                    }
                }
                echo $result_emp;
                $result_emp = $conn->query("SELECT id, name FROM $teamTable");
                while ($row = $result_emp->fetch_assoc()) {
                    $selected = '';
                    if(in_array($row['id'], $dynrow_teams)){
                        $selected = 'selected';
                    }
                    echo '<option value="team;'.$row['id'].'" data-icon="group" '.$selected.' >'.$row['name'].'</option>';
                }
                ?>
            </select><br> -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-warning" name="editTraining" value="<?php echo $trainingID; ?>">Training bearbeiten</button>
        </div>
      </div>
    </div>
</form>