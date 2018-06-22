<?php
session_start();

require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";

isset($_SESSION["userid"]) or die("Not logged in");
isset($_REQUEST["messageID"]) or die("No Message specified");
$userID = $_SESSION["userid"];
$messageID = intval($_REQUEST["messageID"]);
$result = $conn->query("SELECT groupmessages.sender, groupmessages.sent, groupmessages_user.userID receiver, groupmessages_user.seen, firstname, lastname FROM groupmessages INNER JOIN groupmessages_user ON groupmessages.id = groupmessages_user.messageID INNER JOIN UserData ON UserData.id = groupmessages_user.userID WHERE groupmessages.id = $messageID");
showError($conn->error);
?>
<form method="POST">
    <div class="modal fade">
        <div class="modal-dialog modal-content modal-md">
        <div class="modal-header">Information für Nachricht <?= $messageID ?></div>
        <div class="modal-body">
            <table class="table">
                <thead>
                    <tr>
                        <td>User</td>
                        <td>Gelesen</td>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while ($result && $row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>";
                        echo $row["firstname"] . " " . $row["lastname"];
                        echo "</td>";
                        echo "<td>Ja</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
        </div>
        </div>
    </div>
</form>