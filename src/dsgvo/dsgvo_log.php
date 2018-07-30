<?php
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "misc" . DIRECTORY_SEPARATOR . "helpcenter.php";
require_permission("READ","DSGVO","LOGS");

$current_filter_scope = "all";
$current_filter_short_description = "all";
if(isset($_GET["filter1"])){
    $current_filter_scope = test_input($_GET["filter1"]);
}
if(isset($_GET["filter2"])){
    $current_filter_short_description = test_input($_GET["filter2"]);
}
$total_number_of_logs = $number_of_shown_logs = 0;
$result = $conn->query("SELECT count(id) AS number_of_logs FROM dsgvo_vv_logs");
if($result){
    $row = $result->fetch_assoc();
    $total_number_of_logs = $row["number_of_logs"];
}
showError($conn->error);

$result = $conn->query("SELECT dsgvo_vv_logs.*,UserData.firstname,UserData.lastname, TIMEDIFF(dsgvo_vv_logs.log_time,CURRENT_TIMESTAMP) AS timespan FROM dsgvo_vv_logs, UserData WHERE UserData.id = dsgvo_vv_logs.user_id ORDER BY id DESC");
showError($conn->error);

?>

    <div class="page-header">
        <h3>Logs
            <div class="col-md-6 pull-right">
                <form>
                    <div class="input-group">
                        <select class="form-control" name="filter1" style="width:50%">
                            <option value="all" <?php echo $current_filter_scope == "all"?"selected":"" ?> ><?php echo $lang['ALL_SECTIONS'] ?></option>
                            <option value="VV" <?php echo $current_filter_scope == "VV"?"selected":"" ?> ><?php echo $lang["PROCEDURE_DIRECTORY"] ?></option><!-- todo: find a more dynamic method -->
                            <option value="TRAINING" <?php echo $current_filter_scope == "TRAINING"?"selected":"" ?> ><?php echo $lang["TRAINING"] ?></option>
                        </select>
                        <select class="form-control" name="filter2" style="width:50%">
                            <option value="all" <?php echo $current_filter_short_description == "all"?"selected":"" ?> ><?php echo $lang["ALL_EVENTS"] ?></option>
                            <option value="INSERT" <?php echo $current_filter_short_description == "INSERT"?"selected":"" ?> ><?php echo $lang["DSGVO_LOG_EVENT_TYPES"]["INSERT"] ?></option>
                            <option value="UPDATE" <?php echo $current_filter_short_description == "UPDATE"?"selected":"" ?> ><?php echo $lang["DSGVO_LOG_EVENT_TYPES"]["UPDATE"] ?></option>
                            <option value="DELETE" <?php echo $current_filter_short_description == "DELETE"?"selected":"" ?> ><?php echo $lang["DSGVO_LOG_EVENT_TYPES"]["DELETE"] ?></option>
                            <option value="CLONE" <?php echo $current_filter_short_description == "CLONE"?"selected":"" ?> ><?php echo $lang["DSGVO_LOG_EVENT_TYPES"]["CLONE"] ?></option>
                            <option value="IMPORT" <?php echo $current_filter_short_description == "IMPORT"?"selected":"" ?> ><?php echo $lang["DSGVO_LOG_EVENT_TYPES"]["IMPORT"] ?></option>
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
                        <th><?php echo $lang['TIMES'] ?></th>
                        <th>
                            <?php echo mc_status(); ?><?php echo $lang['DESCRIPTION'] ?></th>
                        <th><?php echo $lang['USER'] ?></th>
                        <th>
                            <?php echo mc_status(); ?><?php echo $lang['SECTION'] ?></th>
                        <th>
                            <?php echo mc_status(); ?><?php echo $lang['LONG_DESCRIPTION'] ?></th>
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
        if($current_filter_scope != "all" && $scope != $current_filter_scope){
            continue;
        }
        if($current_filter_short_description != "all" && $short_description != $current_filter_short_description){
            continue;
        }
        $number_of_shown_logs++;

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
                $short_tooltip = $lang["DSGVO_LOG_EVENT_DESCRIPTION"]["INSERT"];
                $short_description_classes = "text-success text-bold";
                break;
            case "UPDATE": 
                $tr_classes = "bg-warning"; 
                $short_tooltip = $lang["DSGVO_LOG_EVENT_DESCRIPTION"]["UPDATE"];
                $short_description_classes = "text-warning";
                break;
            case "DELETE": 
                $tr_classes = "bg-danger"; 
                $short_tooltip = $lang["DSGVO_LOG_EVENT_DESCRIPTION"]["DELETE"];
                $short_description_classes = "text-danger";
                break;
            default:
                switch($short_description){
                    case "CLONE": 
                        $short_tooltip = $lang["DSGVO_LOG_EVENT_DESCRIPTION"]["CLONE"];
                        break;
                    case "IMPORT":
                        $short_tooltip = $lang["DSGVO_LOG_EVENT_DESCRIPTION"]["IMPORT"];
                        break;                    
                }
                $tr_classes = "bg-info";
                $short_description_classes = "text-info";
        }
        echo "<tr class='$tr_classes'>";
        echo "<td data-toggle='tooltip' data-container='body' data-placement='right' title='$timediff (Log ID: $id)'>$log_time</td>";
        echo "<td class='$short_description_classes' data-toggle='tooltip' data-container='body' data-placement='right' title='$short_tooltip'><strong>$short_description</strong></td>";
        echo "<td data-toggle='tooltip' data-container='body' data-placement='right' title='ID: $user_id'>$user_name</td>";
        echo "<td>$scope</td>";
        echo "<td>$long_description</td>";
        echo "</tr>";
    }

    if($number_of_shown_logs != $total_number_of_logs){
        showInfo(sprintf($lang["NUMBER_OF_LOGS"], $number_of_shown_logs, $total_number_of_logs));
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