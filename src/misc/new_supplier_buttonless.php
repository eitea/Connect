<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
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
                    <label>Lieferantennummer</label>
                    <input type="text" class="form-control" name="clientNumber" placeholder="#" >
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
