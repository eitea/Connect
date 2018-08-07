<?php
session_start();
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "dsgvo_training_common.php";

$suspended = [];
$result = $conn->query("SELECT id, firstname, lastname FROM UserData");
while($result && $row = $result->fetch_assoc()){
    $id = $row["id"];
    list($sql_error, $surveys_are_suspended) = surveys_are_suspended_query($id, true);
    if($surveys_are_suspended){
        $suspended[$id] = $row["firstname"]." ".$row["lastname"];
    }
}

?>

<form method="POST">
<div class="modal fade">
    <div class="modal-dialog modal-content modal-md">
    <div class="modal-header"><?php echo $lang['SUSPENDED_SURVEYS'] ?></div>
    <div class="modal-body">
        <?php 

        if(count($suspended)){
            echo "<table class='table'>";
            echo "<thead><tr><th>User</th></tr></thead>";
            echo "<tbody>";
            foreach ($suspended as $id => $name) {
                echo "<tr><td><i class='fa fa-fw fa-hourglass-half'></i> $name</td></tr>";
            }
            echo "</tbody></table>";
        }else{
            echo "Keine Schulungen aufgeschoben";
        }

        ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
    </div>
    </div>
</div>
</form>
