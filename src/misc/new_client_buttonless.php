<div style="display:hidden">
    <form method="post" id="filterCompany_form">
        <input type="hidden" name="filterCompanyID" value="<?php echo $filterCompanyID; ?>" />
    </form>
</div>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['create_client']) && !empty($_POST['create_client_name']) && $_POST['create_client_company'] != 0){
        $name = test_input($_POST['create_client_name']);
        $clientNum = test_input($_POST['clientNumber']);
        $isSupplier = ($_POST['create_client_type'] == 'supplier') ? 'TRUE' : 'FALSE'; //5acb8a41e2d9e
        $filterCompanyID = $companyID = intval($_POST['create_client_company']);

        // TODO: test interest (INTERESTED.WRITE)
        if(($isSupplier == 'TRUE' && Permissions::has("SUPPLIERS.WRITE")) || ($isSupplier == 'FALSE' && Permissions::has("CLIENTS.WRITE"))){
            $conn->query("INSERT INTO clientData (name, companyID, clientNumber, isSupplier) VALUES('$name', $companyID, '$clientNum', '$isSupplier')");
            if(!$conn->error){
                showSuccess($lang['OK_CREATE']);
                $insert_clientID = $conn->insert_id;
                if($isSupplier == 'FALSE'){
                    $conn->query("INSERT INTO projectData (clientID, name, status, hours, field_1, field_2, field_3)
                    SELECT '$insert_clientID', name, status, hours, field_1, field_2, field_3 FROM $companyDefaultProjectTable WHERE companyID = $companyID");
                    if($conn->error)showError($conn->error.__LINE__);
                }
                $conn->query("INSERT INTO $clientDetailTable (clientID) VALUES($insert_clientID)");
                if($conn->error)showError($conn->error.__LINE__);
                if($_POST['create_client_type'] == 'interest') showError('Interessenten zurzeit noch nicht definiert. Wurde als Kunde angelegt.');
            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            }
        }else{
            showError("Ihnen fehlt die Berechtigung, ". ($isSupplier == 'TRUE'?'Lieferanten':'Kunden') ." zu erstellen");
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
                <div class="col-md-8">
                    <label>Name</label>
                    <input type="text" class="form-control required-field" name="create_client_name" placeholder="Name..." onkeydown="if (event.keyCode == 13) return false;">
                    <br>
                </div>
                <div class="col-md-4">
                    <label>Typ</label>
                    <select id="changeClientType" name="create_client_type" class="js-example-basic-single">
                        <?php if (Permissions::has("CLIENTS.WRITE")): ?>
                        <option value="client" selected><?php echo $lang['CLIENT']; ?></option>
                        <?php endif ?>
                        <?php if (Permissions::has("SUPPLIERS.WRITE")): ?>
                        <option value="supplier"><?php echo $lang['SUPPLIER']; ?></option>
                        <?php endif ?>
                        <?php if (Permissions::has("INTERESTED.WRITE")): ?>
                        <option value="interest">Interessenten</option>
                        <?php endif ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Mandant</label>
                    <select id="create_client_company" name="create_client_company" class="js-example-basic-single" style="width:200px">
                        <?php
                        $result_cc = $conn->query("SELECT id, name FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
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
                    $supplierNums = array(1 => '');
                    $numstrings = $snumstrings = '';
                    $stmt_num = $conn->prepare("SELECT clientNumber FROM clientData WHERE isSupplier = ? AND clientNumber IS NOT NULL AND clientNumber != '' AND companyID = ? ORDER BY clientNumber DESC LIMIT 1 ");
                    $stmt_num->bind_param('ss', $isSupplier, $cmpnyID);
                    $res_num = $conn->query("SELECT companyID, clientStep, clientNum, supplierStep, supplierNum FROM erp_settings");
                    while($res_num && ($rowNum = $res_num->fetch_assoc())){
                        $cmpnyID = $rowNum['companyID'];

                        $step = $rowNum['clientStep'];
                        $isSupplier = 'FALSE';
                        $stmt_num->execute();
                        $res_c_num = $stmt_num->get_result();
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

                        $sstep = $rowNum['supplierStep'];
                        $isSupplier = 'TRUE';
                        $stmt_num->execute();
                        $res_c_num = $stmt_num->get_result();
                        if($row_c_num = $res_c_num->fetch_assoc()){
                            $snum = $row_c_num['clientNumber'];
                            if($row_c_num['clientNumber'] < $rowNum['clientNum']){
                                $snum = $rowNum['clientNum'];
                            }
                        } else {
                            $snum = $rowNum['clientNum'];
                            $sstep = 0;
                        }
                        $supplierNums[$cmpnyID] = preg_replace('/[0-9]+/', '', $snum) . (preg_replace('/[^0-9]+/', '', $snum) + $sstep);
                        $snumstrings .= $cmpnyID .':"'. $supplierNums[$cmpnyID] .'",';
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
    var supplierNums = <?php echo "{ $snumstrings }"; ?> ;
    if ($('#changeClientType').val() == 'supplier') {
        $('#clientNumber').val(supplierNums[$(this).val()]);
    } else {
        $('#clientNumber').val(clientNums[$(this).val()]);
    }
});
$('#changeClientType').change(function(){
    var clientNums = <?php echo "{ $numstrings }"; ?> ;
    var supplierNums = <?php echo "{ $snumstrings }"; ?> ;
    if ($(this).val() == 'supplier') {
        $('#clientNumber').val(supplierNums[$('#create_client_company').val()]);
    } else {
        $('#clientNumber').val(clientNums[$('#create_client_company').val()]);
    }
});
</script>
