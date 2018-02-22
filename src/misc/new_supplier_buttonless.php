<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if($canEditSuppliers == 'TRUE'){
        if(isset($_POST['create_client']) && !empty($_POST['create_client_name']) && $_POST['create_client_company'] != 0){
            $name = test_input($_POST['create_client_name']);
            $filterCompanyID = $companyID = intval($_POST['create_client_company']);
            $conn->query("INSERT INTO $clientTable (name, companyID, clientNumber, isSupplier) VALUES('$name', $companyID, '".$_POST['clientNumber']."', 'TRUE' )");

            if($conn->error){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            } else {
                echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
            }
        } elseif(isset($_POST['create_client'])){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
        }
    }else{
        echo "no permission (you need canEditSuppliers)";
    }
}
?>

<div class="modal fade" id="create_client" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-content" role="document">
        <form method="POST">
            <div class="modal-header"><h4><?php echo $lang['SUPPLIER']; ?></h4></div>
            <div class="modal-body"><br>
                <div class="col-md-12">
                    <label>Name</label>
                    <input type="text" class="form-control required-field" name="create_client_name" placeholder="Name..." onkeydown="if (event.keyCode == 13) return false;">
                    <br>
                </div>
                <div class="col-md-6">
                    <label for="#create_client_company">Name</label>
                    <select id="create_client_company" name="create_client_company" class="js-example-basic-single" style="width:200px">
                        <?php
                        $result_cc = $conn->query("SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
                        while ($result_cc && ($row_cc = $result_cc->fetch_assoc())) {
                            $cmpnyID = $row_cc['id'];
                            $cmpnyName = $row_cc['name'];
                            if($filterings['company'] == $cmpnyID){
                                echo "<option selected name='cmp' value='$cmpnyID'>$cmpnyName</option>";
                            } else {
                                echo "<option name='cmp' value='$cmpnyID'>$cmpnyName</option>";
                            }
                        }
                        ?>
                    </select><br>
                </div>
                <div class="col-md-6">
                    <?php
                    $clientNums = array(1 => '');
                    $numstrings = '';
                    $res_num = $conn->query("SELECT companyID, supplierStep, supplierNum FROM erp_settings");
                    while($res_num && ($rowNum = $res_num->fetch_assoc())){
                        $cmpnyID = $rowNum['companyID'];
                        $step = $rowNum['supplierStep'];
                        $res_c_num = $conn->query("SELECT clientNumber FROM clientData WHERE isSupplier = 'TRUE' AND clientNumber IS NOT NULL AND clientNumber != '' AND companyID = $cmpnyID ORDER BY clientNumber DESC LIMIT 1 ");
                        if($row_c_num = $res_c_num->fetch_assoc()){
                            $num = $row_c_num['clientNumber'];
                            if($row_c_num['clientNumber'] < $rowNum['supplierNum']){
                                $num = $rowNum['supplierNum'];
                            }
                        } else {
                            $num = $rowNum['supplierNum'];
                            $step = 0;
                        }
                        $clientNums[$cmpnyID] = preg_replace('/[0-9]+/', '', $num) . (preg_replace('/[^0-9]+/', '', $num) + $step);
                        $numstrings .= $cmpnyID .':"'. $clientNums[$cmpnyID] .'",';
                    }
                    ?>
                    <label>Lieferantennummer</label>
                    <input id="clientNumber" type="text" class="form-control" name="clientNumber" value="<?php echo $clientNums[1]; ?>" >
                    <small> &nbsp Optional</small>
                </div>
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
