<?php
session_start();
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "dsgvo_training_common.php";

$result = $conn->query("SELECT id, firstname, lastname, suspension_count, TIMESTAMPDIFF(DAY, last_suspension, CURRENT_TIMESTAMP) = 0 suspended_today
                        FROM UserData 
                        LEFT JOIN dsgvo_training_user_suspension
                        ON UserData.id = dsgvo_training_user_suspension.userID");
echo $conn->error;
?>

<form method="POST">
<div class="modal fade">
    <div class="modal-dialog modal-content modal-md">
    <div class="modal-header"><?php echo $lang['SUSPENDED_SURVEYS'] ?></div>
    <div class="modal-body">
        <?php 

        echo "<table class='table'>";
        echo "<thead><tr><th>User</th><th></th><th style='white-space: nowrap; width: 1%;'></th></tr></thead>";
        echo "<tbody>";
        while ($result && $row = $result->fetch_assoc()) {
            $id = $row["id"];
            $name = $row["firstname"]." ".$row["lastname"];
            $surveys_are_suspended = $row["suspended_today"];
            $count = $row["suspension_count"];
            $class = !$surveys_are_suspended && !($count>=3)?"text-muted":"";
            echo "<tr class='$class'>";
            if($surveys_are_suspended){
                switch($count){
                    case 1: $icon = "hourglass-start"; break;
                    case 2: $icon = "hourglass-half"; break;
                    default: $icon = "hourglass-end"; break;
                }
                echo "<td><span><i title='$count Aufschiebung(en)' data-container='body' data-toggle='tooltip' class='fa fa-fw fa-$icon'></i></span> $name</td>";
            }else{
                if($count>=3){
                    $icon = "hourglass";
                    $message = "Keine weiteren Aufschiebungen möglich";
                }else{
                    $icon = "hourglass-o";
                    $message = "Nicht aufgeschoben";
                }
                echo "<td><span><i title='$message' data-container='body' data-toggle='tooltip' class='fa fa-fw fa-$icon'></i></span> $name</td>";
            }
            echo "<td style='white-space: nowrap; width: 1%;'>";
            $disabled = "";
            // $disabled = $count?"":"disabled";
            echo "<span data-container='body' data-toggle='tooltip' title='Zurücksetzen (Der User kann wieder 3 mal aufschieben)'>";
            echo "<button $disabled type='submit' style='background:none;border:none;' name='undoSuspension' value='$id'><i class='fa fa-fw fa-undo'></i></button>";
            echo "</span>";
            // $disabled = $count >= 3?"disabled":"";
            echo "<span data-container='body' data-toggle='tooltip' title='Aufschiebung überspringen (Der User kann kein weiteres mal aufschieben)'>";
            echo "<button $disabled type='submit' style='background:none;border:none;' name='fastForwardSuspension' value='$id'><i class='fa fa-fw fa-fast-forward'></i></button>";
            echo "</span>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";

        ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
    </div>
    </div>
</div>
</form>
