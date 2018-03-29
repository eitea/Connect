<?php
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "misc" . DIRECTORY_SEPARATOR . "helpcenter.php";
enableToDSGVO($userID);
?>

    <div class="page-header">
        <h3>Logs</h3>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Benutzer</th>
                <th>Beschreibung</th>
                <th>Lange Beschreibung</th>
                <th>Uhrzeit</th>
            </tr>
        </thead>
        <tbody>
            <?php
$result = $conn->query("SELECT * FROM dsgvo_vv_logs ORDER BY log_time DESC");
showError($conn->error);
while ($result && ($row = $result->fetch_assoc())) {
    $id = $row["id"];
    $user_id = $row["user_id"];
    try{
        $short_description = $row["short_description"];
        $short_description = secure_data('DSGVO', $row["short_description"], 'decrypt', $userID, $privateKey, $encryptionError);
        showError($encryptionError);
        $long_description = secure_data('DSGVO', $row["long_description"], 'decrypt', $userID, $privateKey, $encryptionError);
        showError($encryptionError);
    }catch(Exception $e){
        showError($e->getMessage());
    }
    $log_time = $row["log_time"];
    echo "<tr>";
    echo "<td>$id</td>";
    echo "<td>$user_id</td>";
    echo "<td>$short_description</td>";
    echo "<td>$long_description</td>";
    echo "<td>$log_time</td>";
    echo "</tr>";
}
?>
        </tbody>
    </table>

    <?php include dirname(__DIR__) . '/footer.php';?>