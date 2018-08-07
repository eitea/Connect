<?php
include dirname(__DIR__) . '/header.php';
?><?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
$filterings = array('company' => 0, 'supplier' => 0, 'date' => array(substr(getCurrentTimestamp(), 0, 8).'01', date('Y-m-t', strtotime(getCurrentTimestamp()))));

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['delete'])){
        $val = intval($_POST['delete']);
        $conn->query("DELETE FROM receiptBook where id = $val");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    }
    if(isset($_POST['addReceipt'])){
        if(!empty($_POST['add_date']) && test_Date($_POST['add_date'], 'Y-m-d') && !empty($_POST['filterSupplier']) && !empty($_POST['add_tax']) && !empty($_POST['add_text']) && !empty($_POST['add_amount'])){
            $date = $_POST['add_date'];
            $supplierID = intval($_POST['filterSupplier']);
            $taxID = intval($_POST['add_tax']);
            $text = test_input($_POST['add_text']);
            $amount = floatval($_POST['add_amount']);
            $stmt = $conn->prepare("INSERT INTO receiptBook (supplierID, taxID, invoiceDate, info, amount) VALUES(?, ?, ?, ?, ?)");
            $stmt->bind_param("iissd", $supplierID, $taxID, $date, $text, $amount);
            $stmt->execute();
            $stmt->close();
            if($conn->error){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            } else {
                echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
        }
    }
    if(isset($_POST['saveEdit']) && test_Date($_POST['edit_date'], 'Y-m-d') && !empty($_POST['edit_tax']) && !empty($_POST['edit_text']) && !empty($_POST['edit_amount'])){
        $val = intval($_POST['saveEdit']);
        $date = $_POST['edit_date'];
        $taxID = intval($_POST['edit_tax']);
        $text = test_input($_POST['edit_text']);
        $amount = floatval($_POST['edit_amount']);
        $conn->query("UPDATE receiptBook SET invoiceDate='$date', taxID='$taxID', info='$text', amount='$amount' WHERE id = $val ");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        }
    }
}
?>
<div class="page-header-fixed">
<div class="page-header"><h3><?php echo $lang['RECEIPT_BOOK']; ?>
<div class="page-header-button-group"><?php include dirname(__DIR__) . '/misc/set_filter.php'; ?></div></h3>
</div>
</div>
<div class="page-content-fixed-170">
<?php
$supplierQuery = $companyQuery = '';
if($filterings['supplier']) $supplierQuery = 'AND supplierID = '.$filterings['supplier'];
if($filterings['company']) $companyQuery = 'AND companyID = '.$filterings['company'];

$tax_select = '';
$result = $conn->query("SELECT id, percentage, description FROM taxRates");
while($result && ($row = $result->fetch_assoc())){
    if($row['id'] < 5) continue;
    $tax_select .= '<option value="'.$row['id'].'">'.sprintf('%02d',$row['id']).' - '.$row['percentage'].'% '.$row['description'].'</option>';
}
?>

<table class="table table-hover">
    <thead><tr>
        <?php if(count($available_companies) > 2) echo '<th>'.$lang['COMPANY'].'</th>'; ?>
        <th>Nr.</th>
        <th><?php echo $lang['RECEIPT_DATE']; ?></th>
        <th><?php echo $lang['SUPPLIER']; ?></th>
        <th>Infotext</th>
        <th><?php echo $lang['AMOUNT']; ?> <small>(Brutto)</small></th>
        <th><?php echo $lang['TAXES']; ?></th>
        <th><?php echo $lang['VAT']; ?></th>
        <th>Journal Status</th>
    </tr></thead>
    <tbody>
    <?php
    $modals = '';
    $runNumbers = array_fill_keys($available_companies, 0); //let's try something fun
    $result = $conn->query("SELECT receiptBook.*, companyData.name, companyID, percentage, description, clientData.name AS supplierName
    FROM receiptBook INNER JOIN clientData ON clientData.id = supplierID INNER JOIN companyData ON companyData.id = clientData.companyID INNER JOIN taxRates ON  taxRates.id = taxID
    WHERE companyID IN (".implode(', ', $available_companies).") AND DATE(invoiceDate) >= '".$filterings['date'][0]."' AND DATE(invoiceDate) <= DATE('".$filterings['date'][1]."') $supplierQuery $companyQuery ORDER BY receiptBook.id ASC"); echo $conn->error;
    while($row = $result->fetch_assoc()){
        $runNumbers[$row['companyID']]++;
        echo '<tr>';
        if(count($available_companies) > 2) echo '<td>'.$row['name'].'</td>';
        echo '<td>'.$runNumbers[$row['companyID']].'</td>';
        echo '<td>'.substr($row['invoiceDate'], 0, 10).'</td>';
        echo '<td>'.$row['supplierName'].'</td>';
        echo '<td>'.$row['info'].'</td>';
        echo '<td>'.number_format(($row['amount']), 2, ',', '.').'</td>';
        echo '<td>'.$row['percentage'].'% '. $row['description'].'</td>';
        echo '<td>'.number_format($row['amount'] - ($row['amount'] * 100) / (100 + $row['percentage'] ), 2, ',', '.').'</td>';
        if($row['journalID']){
            echo '<td class="charged" ><a href="../finance/journal?cmp='.$row['companyID'].'" class="btn btn-link">'.$lang['BOOKED'].'</a></td>';
        } else {
            echo '<td style="text-align:center"><form method="POST">';
            echo '<button type="submit" name="delete" value="'.$row['id'].'" class="btn btn-default"><i class="fa fa-trash-o"></i></button> ';
            echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target=".edit-'.$row['id'].'" title="'.$lang['EDIT'].'" ><i class="fa fa-pencil"></i></button>';
            echo '</form></td>';
        }
        echo '</tr>';

        $modals .= '<div class="modal fade edit-'.$row['id'].'"><div class="modal-dialog modal-content modal-md"><div class="modal-header h4">'.$lang['EDIT'].'</div><form method="POST">
        <div class="modal-body"><div class="row">
        <div class="col-md-5"><label>'.$lang['RECEIPT_DATE'].'</label><input type="text" class="form-control datepicker" name="edit_date" value="'.substr($row['invoiceDate'], 0, 10).'" /></div>
        <div class="col-md-7"><label>'.$lang['VAT'].'</label><select id="tax" class="js-example-basic-single" name="edit_tax">'.str_replace('<option value="'.$row['taxID'].'">', '<option selected value="'.$row['taxID'].'">', $tax_select).'</select></div>
        <div class="col-md-7"><br><label>Text</label><input type="text" class="form-control" name="edit_text" maxlength="64" value="'.$row['info'].'" /></div>
        <div class="col-md-5"><br><label>'.$lang['AMOUNT'].' <small>(Brutto)</small></label><input id="should" type="number" step="0.01" class="form-control money" name="edit_amount" value="'.$row['amount'].'"/></div>
        </div></div><div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="saveEdit" value="'.$row['id'].'" >'.$lang['SAVE'].'</button></div></form></div></div>';
    }
    ?>
    </tbody>
</table>
<?php echo $modals; include dirname(__DIR__) .'/misc/new_client_buttonless.php'; ?>
<br><br>
<form method="POST" class="well">
<div class="row form-group">
<?php include dirname(__DIR__).'/misc/select_supplier.php'; ?>
<div class="col-md-3"><label><?php echo $lang['RECEIPT_DATE']; ?></label><input type="text" class="form-control datepicker" name="add_date" value="<?php echo substr(getCurrentTimestamp(),0,10); ?>" /></div>
</div>
    <div class="row form-group">
        <div class="col-md-4"><label><?php echo $lang['VAT']; ?></label><select id="tax" class="js-example-basic-single" name="add_tax" ><?php echo $tax_select; ?></select></div>
        <div class="col-md-4"><label>Text</label><input type="text" class="form-control" name="add_text" maxlength="64" /></div>
        <div class="col-md-2"><label><?php echo $lang['AMOUNT']; ?> <small>(Brutto)</small></label><input id="should" type="number" step="0.01" class="form-control" name="add_amount" placeholder="0,00"/></div>
        <div class="col-md-2"><label style="color:transparent">O.K.</label><button type="submit" class="btn btn-warning btn-block" name="addReceipt" ><?php echo $lang['ADD']; ?></button></div>
    </div>
</form>
</div>
<?php include dirname(__DIR__) . '/footer.php'; ?>
