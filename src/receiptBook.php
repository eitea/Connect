<?php
require 'header.php';
$filterings = array('company' => 0, 'supplier' => 0, 'date' => array(substr(getCurrentTimestamp(), 0, 8).'01', date('Y-m-t', strtotime(getCurrentTimestamp()))));

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
?>
<div class="page-header"><h3><?php echo $lang['RECEIPT_BOOK']; ?>
<div class="page-header-button-group"><?php include __DIR__ . '/misc/set_filter.php'; ?></div></h3>
</div>

<?php
$supplierQuery = $companyQuery = '';
if($filterings['supplier']) $supplierQuery = 'AND supplierID = '.$filterings['supplier'];
if($filterings['company']) $companyQuery = 'AND companyID = '.$filterings['company'];

$tax_select = '';
$result = $conn->query("SELECT id, percentage, description FROM taxRates");
while($result && ($row = $result->fetch_assoc())){
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
        <th><?php echo $lang['VAT']; ?></th>
        <th>Journal Status</th>
    </tr></thead>
    <tbody>
    <?php
    $runNumbers = array_fill_keys($available_companies, 0); //let's try something fun
    $result = $conn->query("SELECT receiptBook.*, companyData.name, companyID, percentage, clientData.name AS supplierName 
    FROM receiptBook INNER JOIN clientData ON clientData.id = supplierID INNER JOIN companyData ON companyData.id = clientData.companyID INNER JOIN taxRates ON  taxRates.id = taxID
    WHERE companyID IN (".implode(', ', $available_companies).") $supplierQuery $companyQuery ORDER BY receiptBook.id ASC"); echo $conn->error;
    while($row = $result->fetch_assoc()){
        $runNumbers[$row['companyID']]++;
        echo '<tr>';
        if(count($available_companies) > 2) echo '<td>'.$row['name'].'</td>';
        echo '<td>'.$runNumbers[$row['companyID']].'</td>';
        echo '<td>'.substr($row['invoiceDate'], 0, 10).'</td>';
        echo '<td>'.$row['supplierName'].'</td>';
        echo '<td>'.$row['info'].'</td>';
        echo '<td>'.number_format(($row['amount']), 2, ',', '.').'</td>';
        echo '<td>'.number_format($row['amount'] * $row['percentage'] / 100, 2, ',', '.').'</td>';
        if($row['journalID']){ echo '<td class="charged" ><a href="../finance/journal?n='.$row['companyID'].'" class="btn btn-link">'.$lang['CHARGED'].'</a></td>'; } else { echo '<td class="not-charged" >'.$lang['NOT_CHARGED'].'</td>'; }
        echo '</tr>';
    }
    ?>
    </tbody>
</table>

<br><br>
<form method="POST" class="well">
<div class="row form-group">
<?php include __DIR__.'/misc/select_supplier.php'; ?>
</div>
    <div class="row form-group">
        <div class="col-md-3"><label><?php echo $lang['RECEIPT_DATE']; ?></label><input type="text" class="form-control datepicker" name="add_date" value="<?php echo substr(getCurrentTimestamp(),0,10); ?>" /></div>
        <div class="col-md-3"><label><?php echo $lang['VAT']; ?></label><select id="tax" class="js-example-basic-single" name="add_tax" ><?php echo $tax_select; ?></select></div>
        <div class="col-md-2"><label>Text</label><input type="text" class="form-control" name="add_text" maxlength="64" /></div>
        <div class="col-md-2"><label><?php echo $lang['AMOUNT']; ?> <small>(Brutto)</small></label><input id="should" type="number" step="0.01" class="form-control" name="add_amount" placeholder="0,0"/></div>
        <div class="col-md-2"><label style="color:transparent">O.K.</label><button type="submit" class="btn btn-warning btn-block" name="addReceipt" ><?php echo $lang['ADD']; ?></button></div>        
    </div>
</form>
<?php require 'footer.php'; ?>