<?php
if (!isset($_REQUEST["questionID"])) {
    echo "error";
    die();
}
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

$questionID = $_REQUEST["questionID"];
$result = $conn->query(
        "SELECT userID, correct, firstname, lastname
     FROM dsgvo_training_questions tq 
     INNER JOIN dsgvo_training_completed_questions tcq ON tcq.questionID = tq.id 
     INNER JOIN UserData ON UserData.id = tcq.userID
     WHERE tq.id = $questionID"
);
?>
<form method="POST">
    <div class="modal fade">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header">Statistik</div>
            <div class="modal-body">
                <?php
                if (!$result || $result->num_rows == 0) {
                    echo "no data yet";
                } else {
                    ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Correct</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>${row['firstname']} ${row['lastname']}</td>";
                                echo "<td>${row['correct']}</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</form>