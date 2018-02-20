<?php
$filterCompany = empty($filterings['company']) ? 0 : $filterings['company'];
$filterSupplier = empty($filterings['supplier']) ? 0 : $filterings['supplier'];
$result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (" . implode(', ', $available_companies) . ")");
if ($result_fc && $result_fc->num_rows > 1) {
    echo '<div class="col-md-3"><label>' . $lang['COMPANY'] . '</label><select class="js-example-basic-single" name="filterCompany" onchange="select_supplier.showSuppliers(this.value, ' . $filterSupplier . ');" >';
    echo '<option value="0">...</option>';
    while ($result && ($row_fc = $result_fc->fetch_assoc())) {
        $checked = '';
        if ($filterCompany == $row_fc['id']) {
            $checked = 'selected';
        }
        echo "<option $checked value='" . $row_fc['id'] . "' >" . $row_fc['name'] . "</option>";
    }
    echo '</select></div>';
} else {
    $filterCompany = $available_companies[1];
}
?>
<div class="col-md-3">
    <label><?php echo $lang['SUPPLIER']; ?></label>
    <select id="supplierHint" class="js-example-basic-single" name="filterSupplier"></select>
</div>
<script>
    var select_supplier = {
        showSuppliers: function (company, supplier) {
            if (company != "") {
                $.ajax({
                    url: 'ajaxQuery/AJAX_getSupplier.php',
                    data: {companyID: company, supplierID: supplier},
                    type: 'get',
                    success: function (resp) {
                        $("#supplierHint").html(resp);
                    },
                    error: function (resp) {}
                });
            }
        }
    };

    $("#supplierHint").change(function () {
        if ($(this).val() == 'new') {
            $('#create_client').modal().toggle();
        }
    });
</script>


<?php
if ($filterCompany) {
    echo '<script>';
    echo "select_supplier.showSuppliers($filterCompany, $filterSupplier)";
    echo '</script>';
}
?>
