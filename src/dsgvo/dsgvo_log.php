<?php
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "misc" . DIRECTORY_SEPARATOR . "helpcenter.php";
enableToDSGVO($userID);
?>

    <div class="page-header">
        <h3>Logs</h3>
    </div>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>Uhrzeit</th>
                <th>Beschreibung</th>
                <th>Benutzer</th>
                <th>Lange Beschreibung</th>
            </tr>
        </thead>
        <tbody>
            <?php
$result = $conn->query("SELECT dsgvo_vv_logs.*,UserData.firstname,UserData.lastname, TIMEDIFF(dsgvo_vv_logs.log_time,CURRENT_TIMESTAMP) AS timespan FROM dsgvo_vv_logs, UserData WHERE UserData.id = dsgvo_vv_logs.user_id ORDER BY log_time DESC,id DESC");
showError($conn->error);
while ($result && ($row = $result->fetch_assoc())) {
    $id = $row["id"];
    $user_id = $row["user_id"];
    $user_name = $row["firstname"]." ".$row["lastname"];
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
    $timediff = $row["timespan"];
    $tr_classes = "";
    $short_description_classes = "";
    switch($short_description){
        case "INSERT": 
            $tr_classes = "bg-success"; 
            $short_tooltip = "Ein neuer Eintrag wurde hinzugefügt";
            $short_description_classes = "text-success text-bold";
            break;
        case "UPDATE": 
            $tr_classes = "bg-warning"; 
            $short_tooltip = "Ein bestehender Eintrag wurde verändert";
            $short_description_classes = "text-warning";
            break;
        case "DELETE": 
            $tr_classes = "bg-danger"; 
            $short_tooltip = "Ein bestehender Eintrag wurde entfernt";
            $short_description_classes = "text-danger";
            break;
        default:
            $tr_classes = "";
            $short_tooltip = "";
            $short_description_classes = "text-muted";
    }
    echo "<tr class='$tr_classes'>";
    echo "<td data-toggle='tooltip' data-container='body' data-placement='right' title='$timediff (Log ID: $id)'>$log_time</td>";
    echo "<td class='$short_description_classes' data-toggle='tooltip' data-container='body' data-placement='right' title='$short_tooltip'><strong>$short_description</strong></td>";
    echo "<td data-toggle='tooltip' data-container='body' data-placement='right' title='Benutzer ID: $user_id'>$user_name</td>";
    echo "<td>$long_description</td>";
    echo "</tr>";
}
?>
        </tbody>
    </table>

<script>
$('[data-toggle="tooltip"]').tooltip(); 
</script>

    <?php include dirname(__DIR__) . '/footer.php';?>