<?php
session_start();

require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";

isset($_SESSION["userid"]) or die("Not logged in");
isset($_REQUEST["group"]) or die("No Group specified");
$userID = $_SESSION["userid"];
$groupID = intval($_REQUEST["group"]);

$result = $conn->query("SELECT subject FROM messagegroups WHERE id = $groupID");
if (!$result || !($row = $result->fetch_assoc())) {
    die("Group not found");
}
$name = $row["subject"];
$members = array();
$admins = array();
$isGroupAdmin = false;
$result = $conn->query("SELECT userID, admin FROM messagegroups_user INNER JOIN UserData ON UserData.id = messagegroups_user.userID WHERE groupID = $groupID");
while ($result && ($row = $result->fetch_assoc())) {
    if ($row["admin"] == "TRUE") {
        $admins[] = $row["userID"];
    } else {
        $members[] = $row["userID"];
    }
    if ($row["userID"] == $userID) {
        $isGroupAdmin = $row["admin"] == "TRUE";
    }
}

$disabled = $isGroupAdmin ? "" : "disabled"


?>
<form method="POST">
<input type="hidden" name="members[]" value="-1" /> <!-- if no user is selected, members[] is still present -->
    <div class="modal fade">
        <div class="modal-dialog modal-content modal-md">
        <div class="modal-header"><?= $name ?></div>
        <div class="modal-body">
            
            <label>Mitglieder</label>
                <select id="members-select" <?= $disabled ?> class="js-example-basic-single" name="members[]" multiple="multiple">
                <?php
                $result = $conn->query("SELECT UserData.id id, firstname, lastname FROM UserData");
                while ($result && ($row = $result->fetch_assoc())) {
                    $selected = '';
                    if (in_array($row['id'], $members)) {
                        $selected = 'selected';
                    }
                    echo '<option title="Benutzer" value="' . $row['id'] . '" data-icon="user" ' . $selected . ' >' . $row['firstname'] . ' ' . $row['lastname'] . '</option>';
                }
                ?>
            </select><br><br>
            <label>Admins</label>
                <select id="admins-select" <?= $disabled ?> class="js-example-basic-single" name="admins[]" multiple="multiple">
                <?php
                $result = $conn->query("SELECT UserData.id id, firstname, lastname FROM UserData");
                while ($result && ($row = $result->fetch_assoc())) {
                    $selected = '';
                    if (in_array($row['id'], $admins)) {
                        $selected = 'selected';
                    }
                    echo '<option title="Benutzer" value="' . $row['id'] . '" data-icon="user" ' . $selected . ' >' . $row['firstname'] . ' ' . $row['lastname'] . '</option>';
                }
                ?>
            </select><br><br>
            
            <div id="delete-warning-members">
                <div class="alert alert-info">
                    Gruppen ohne Mitglieder werden gelöscht
                </div>
            </div>
       
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
            <button type="submit" class="btn btn-default" name="saveGroup" value="<?= $groupID ?>" >Speichern</button>
        </div>
        </div>
    </div>
</form>
<script>
$("#delete-warning-members").hide();
$("#members-select").change(displayWarning)
$("#admins-select").change(displayWarning)
function displayWarning(event){
    mv = $("#members-select").val();
    av = $("#admins-select").val();
    console.log(mv,av);
    if((mv && mv.length > 0 ) || (av && av.length > 0)){
        $("#delete-warning-members").fadeOut();
    }else{
        $("#delete-warning-members").fadeIn();
    }
}
</script>