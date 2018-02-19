<?php

require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

$x = intval($_POST['id']);
$account_select = urldecode($_POST['acc']);
$tax_select = urldecode($_POST['tax']);
$web_select = urldecode($_POST['web']);
$supplier_select = urldecode($_POST['sups']);

//id, docNum, payDate, account, taxID, info, should, have
$result = $conn->query("SELECT id, docNum, payDate, account, taxID, info, should, have FROM account_journal WHERE id = $x");
$row = $result->fetch_assoc();

echo '<div class="modal fade edit-journal-' . $row['id'] . '"><div class="modal-dialog modal-content modal-md"><form method="POST">
<div class="modal-header"><h4>' . $lang['EDIT'] . '</h4></div><div class="modal-body"><div class="row">
<div class="col-md-4"><label>Nr.</label><input type="number" class="form-control" step="1" min="1" name="add_nr" value="' . $row['docNum'] . '"/><br></div>
<div class="col-md-8"><label>' . $lang['DATE'] . '</label><input type="text" class="form-control datepicker" name="add_date" value="' . substr($row['payDate'], 0, 10) . '" /><br></div>
<div class="col-md-6"><label>' . $lang['ACCOUNT'] . '</label><select class="js-example-basic-single" name="add_account" ><option>...</otpion>' . str_replace('<option value="' . $row['account'] . '">', '<option selected value="' . $row['account'] . '">', $account_select) . '</select><br></div>
<div class="col-md-6"><label>' . $lang['VAT'] . '</label><select class="js-example-basic-single" name="add_tax">' . str_replace('<option value="' . $row['taxID'] . '">', '<option selected value="' . $row['taxID'] . '">', $tax_select) . '</select><br><br></div>
<div class="col-md-6"><label>Text</label><input type="text" class="form-control" name="add_text" maxlength="64" value="' . $row['info'] . '" /></div>
<div class="col-md-3"><label>' . $lang['FINANCE_DEBIT'] . '<small> (Brutto)</small></label><input type="number" step="0.01" class="form-control" name="add_should" value="' . $row['should'] . '"/></div>
<div class="col-md-3"><label>' . $lang['FINANCE_CREDIT'] . '<small> (Brutto)</small></label><input type="number" step="0.01" class="form-control" name="add_have" value="' . $row['have'] . '"/><br></div>
<div class="col-md-9" id="mod-sel-' . $row['id'] . '" ><label>' . $lang['FROM'] . ' WEB</label><select class="js-example-basic-single" name="webID" >' . $web_select . '</select><br><br></div>
<div class="col-md-3"><label style="padding-top:28px;"><input type="checkbox" name="transferToWEB" onchange="if(this.checked) showHide(\'mod-in-' . $row['id'] . '\' ,\'mod-sel-' . $row['id'] . '\'); else showHide(\'mod-sel-' . $row['id'] . '\', \'mod-in-' . $row['id'] . '\');" />In WEB</label></div>
<span id="mod-in-' . $row['id'] . '" style="display:none"><div class="col-md-5"><label>' . $lang['SUPPLIER'] . '</label><select class="js-example-basic-single" name="add_supplier" >' . $supplier_select . '</select></div>
<div class="col-md-4"><label>' . $lang['RECEIPT_DATE'] . '</label><input type="text" class="form-control datepicker" name="add_invoiceDate" value="" /></div></span>
</div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-warning" value="' . $row['id'] . '" name="editJournalEntry">' . $lang['SAVE'] . '</button></div></form></div></div>';
?>
