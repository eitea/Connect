<div style="display:hidden">
    <form method="post" id="filterCompany_form">
        <input type="hidden" name="filterCompanyID" value="<?php echo $filterCompanyID; ?>" />
    </form>
</div>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['create_client']) && !empty($_POST['create_client_name']) && $_POST['create_client_company'] != 0){
        $name = test_input($_POST['create_client_name']);
        $filterCompanyID = $companyID = intval($_POST['create_client_company']);

        $sql = "INSERT INTO clientData (name, companyID, clientNumber) VALUES('$name', $companyID, '".$_POST['clientNumber']."')";
        if($conn->query($sql)){ //if ok, give him default projects
            $id = $conn->insert_id;
            $sql = "INSERT INTO $projectTable (clientID, name, status, hours, field_1, field_2, field_3)
            SELECT '$id', name, status, hours, field_1, field_2, field_3 FROM $companyDefaultProjectTable WHERE companyID = $companyID";
            $conn->query($sql);
            //and his details
            $conn->query("INSERT INTO $clientDetailTable (clientID) VALUES($id)");
        }
        if(mysqli_error($conn)){
            echo mysqli_error($conn);
        } else {
            echo '<script>window.location="../system/clientDetail?custID='.$id.'";</script>';
        }
    } elseif(isset($_POST['create_client'])){
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Error: </strong>'.$lang['ERROR_MISSING_FIELDS'];
        echo '</div>';
    }
}
?>

<div class="modal fade" id="create_client" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-content" role="document">
        <form method="post">
            <div class="modal-header">
                <h4><?php echo $lang['NEW_CLIENT_CREATE']; ?></h4>
            </div>
            <div class="modal-body">
                <div class="col-md-12">
                    <label>Name</label>
                    <input type="text" class="form-control required-field" name="create_client_name" placeholder="Name..." onkeydown="if (event.keyCode == 13) return false;">
                    <br>
                </div>
                <div class="col-md-6">
                    <label>Mandant</label>
                    <select id="create_client_company" name="create_client_company" class="js-example-basic-single" style="width:200px">
                        <?php
                        $result_cc = $conn->query("SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
                        while ($result_cc && ($row_cc = $result_cc->fetch_assoc())) {
                            $cmpnyID = $row_cc['id'];
                            $cmpnyName = $row_cc['name'];
                            if(isset($filterCompanyID) && $filterCompanyID == $cmpnyID){
                                echo "<option selected value='$cmpnyID'>$cmpnyName</option>";
                            } else {
                                echo "<option value='$cmpnyID'>$cmpnyName</option>";
                            }
                        }
                        ?>
                    </select><br>
                </div>
                <div class="col-md-6">
                    <?php
                    $clientNums = array(1 => '');
                    $numstrings = '';
                    $res_num = $conn->query("SELECT companyID, clientStep, clientNum FROM erp_settings");
                    while($res_num && ($rowNum = $res_num->fetch_assoc())){
                        $cmpnyID = $rowNum['companyID'];
                        $step = $rowNum['clientStep'];
                        $res_c_num = $conn->query("SELECT clientNumber FROM clientData WHERE isSupplier = 'FALSE' AND clientNumber IS NOT NULL AND clientNumber != '' AND companyID = $cmpnyID ORDER BY clientNumber DESC LIMIT 1 ");
                        if($row_c_num = $res_c_num->fetch_assoc()){
                            $num = $row_c_num['clientNumber'];
                            if($row_c_num['clientNumber'] < $rowNum['clientNum']){
                                $num = $rowNum['clientNum'];
                            }
                        } else {
                            $num = $rowNum['clientNum'];
                            $step = 0;
                        }
                        $clientNums[$cmpnyID] = preg_replace('/[0-9]+/', '', $num) . (preg_replace('/[^0-9]+/', '', $num) + $step);
                        $numstrings .= $cmpnyID .':"'. $clientNums[$cmpnyID] .'",';
                    }
                    ?>
                    <label>Kundennummer</label>
                    <input id="clientNumber" type="text" class="form-control" name="clientNumber" value="<?php echo $clientNums[1]; ?>" >
                    <small> &nbsp Optional</small>
                </div>
                <input type="hidden" name="filterCompanyID" value="<?php echo $filterCompanyID; ?>" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-warning " name="create_client"> <?php echo $lang['ADD']; ?></button>
            </div>
        </form>
    </div>
</div>
<script>
$('#create_client_company').on('change', function(){
    var clientNums = <?php echo "{ $numstrings }"; ?> ;
    $('#clientNumber').val(clientNums[$(this).val()]);
});
</script>
