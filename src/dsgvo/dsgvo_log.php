<?php
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "misc" . DIRECTORY_SEPARATOR . "helpcenter.php";
enableToDSGVO($userID);

$current_filter = "all";
if(isset($_GET["filter"])){
    $current_filter = test_input($_GET["filter"]);
}

$result = $conn->query("SELECT dsgvo_vv_logs.*,UserData.firstname,UserData.lastname, TIMEDIFF(dsgvo_vv_logs.log_time,CURRENT_TIMESTAMP) AS timespan FROM dsgvo_vv_logs, UserData WHERE UserData.id = dsgvo_vv_logs.user_id ORDER BY log_time DESC,id DESC");
showError($conn->error);

?>

    <div class="page-header">
        <h3>Logs
            <div class="col-md-6 pull-right">
                <form>
                    <div class="input-group">
                        <select class="form-control" name="filter">
                            <option value="all" <?php echo $current_filter == "all"?"selected":"" ?> ><?php echo $lang["DISPLAY_ALL"] ?></option>
                            <option value="VV" <?php echo $current_filter == "VV"?"selected":"" ?> ><?php echo $lang["PROCEDURE_DIRECTORY"] ?></option><!-- todo: find a more dynamic method -->
                            <option value="TRAINING" <?php echo $current_filter == "TRAINING"?"selected":"" ?> >Schulung</option>
                        </select>
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-warning">
                                <i class="fa fa-search"></i>
                            </button>
                        </span>
                    </div>
                </form>
            </div>
        </h3>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Uhrzeit</th>
                        <th>
                            <?php echo mc_status(); ?>Beschreibung</th>
                        <th>Benutzer</th>
                        <th>
                            <?php echo mc_status(); ?>Bereich</th>
                        <th>
                            <?php echo mc_status(); ?>Lange Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
    while ($result && ($row = $result->fetch_assoc())) {
        try{
            $short_description = $row["short_description"]; //in case an error gets thrown (eg when not encrypted)
            $long_description = $row["long_description"];
            $scope = $row["scope"];
            $short_description = secure_data('DSGVO', $row["short_description"], 'decrypt', $userID, $privateKey, $encryptionError);
            showError($encryptionError);
            $long_description = secure_data('DSGVO', $row["long_description"], 'decrypt', $userID, $privateKey, $encryptionError);
            showError($encryptionError);
            $scope = secure_data('DSGVO', $row["scope"], 'decrypt', $userID, $privateKey, $encryptionError);        
            showError($encryptionError);
        }catch(Exception $e){
            showError($e->getMessage());
        }

        // I can't query scope as it is encrypted with different salt
        if($current_filter != "all" && $scope != $current_filter){
            continue;
        }

        $id = $row["id"];
        $user_id = $row["user_id"];
        $user_name = $row["firstname"]." ".$row["lastname"];
        $log_time = $row["log_time"];
        $timediff = $row["timespan"];
        $tr_classes = "";
        $short_description_classes = "";
        switch($short_description){ // the most used operations get their own color, the rest is blue
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
                switch($short_description){
                    case "CLONE": 
                        $short_tooltip = "Ein bestehender Eintrag wurde dupliziert";
                        break;
                    case "IMPORT":
                        $short_tooltip = "Ein Eintrag wurde importiert";
                        break;                    
                }
                $tr_classes = "bg-info";
                $short_description_classes = "text-info";
        }
        echo "<tr class='$tr_classes'>";
        echo "<td data-toggle='tooltip' data-container='body' data-placement='right' title='$timediff (Log ID: $id)'>$log_time</td>";
        echo "<td class='$short_description_classes' data-toggle='tooltip' data-container='body' data-placement='right' title='$short_tooltip'><strong>$short_description</strong></td>";
        echo "<td data-toggle='tooltip' data-container='body' data-placement='right' title='Benutzer ID: $user_id'>$user_name</td>";
        echo "<td>$scope</td>";
        echo "<td>$long_description</td>";
        echo "</tr>";
    }
    ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        $('[data-toggle="tooltip"]').tooltip(); 
    </script>

    <?php include dirname(__DIR__) . '/footer.php';?>