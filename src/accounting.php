<?php require "header.php"; enableToFinance($userID); ?>
<?php
if(!empty($_GET['v'])){
    $id = intval($_GET['v']);
} else {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Zugriff verweigert</div>';
    include 'footer.php';
    die();
}

$result = $conn->query("SELECT * FROM accounts WHERE id = $id AND companyID IN (".implode(', ', $available_companies).")");
if(!$result || $result->num_rows < 1) {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Zugriff verweigert</div>';
    include 'footer.php';
    die();
}
$account_row = $result->fetch_assoc();

if(isset($_POST['addFinance'])){
    if($_POST['add_nr'] > 0 && test_Date($_POST['add_date'], "Y-m-d") && $_POST['add_account'] > 0 && (!empty($_POST['add_should']) xor !empty($_POST['add_have'])) && !empty($_POST['add_tax']) ){
        $account = intval($_POST['add_account']);
        $offAccount = $id;
        $docNum = $_POST['add_nr'];
        $date = $_POST['add_date'];
        $text = test_input($_POST['add_text']);
        $should = floatval($_POST['add_should']);
        $have = floatval($_POST['add_have']);
        $tax = intval($_POST['add_tax']);

        $conn->query("INSERT INTO account_journal(docNum, userID, account, offAccount, payDate, inDate, tax, shoud, have, info)
                VALUES ($docNum, $userID, $account, $offAccount, '$date', UTC_TIMESTAMP, $tax, $should, $have, $text)");
        echo $conn->error;
        $journalID = $conn->insert_id;
        
        /*
        //TODO: Let the madness begin
        $stmt = $conn->prepare("INSERT INTO account_balance (journalID, accountNum, should, have) VALUES(?, ?, ?, ?)");
        echo $conn->error;
        $stmt->bind_param("iissddi", $journalID, $num, $should, $have);
        $stmt->execute();

        //swap
        $s = $account;
        $account = $offAccount;
        $offAccount = $s;
        $s = $should;
        $should = $have;
        $have = $s;
        $stmt->execute();
        $stmt->close();
        */
    } else {
        echo '<div class="alert alert-danger" class="close"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
    }
}
?>

<div class="page-header"><h3><?php echo $lang['OFFSET_ACCOUNT'].' <small>'.$account_row['num'].' - '.$account_row['name'].'</small>'; ?></h3></div>
<br>
<table class="table table-hover">
<thead><tr>
    <th>Nr.</th>
    <th><?php echo $lang['DATE']; ?></th>
    <th><?php echo $lang['ACCOUNT']; ?></th>
    <th>Text</th>
    <th><?php echo $lang['VAT']; ?></th>
    <th><?php echo $lang['FINANCE_DEBIT']; ?></th>
    <th><?php echo $lang['FINANCE_CREDIT'];?></th>
    <th>Saldo</th>
</tr></thead>
<tbody>
<?php
$docNum = $saldo = 0;
$result = $conn->query("SELECT account_journal.*, taxRates.percentage FROM account_journal, taxRates WHERE tax = taxRates.id AND account_journal.account = $id ORDER BY docNum ASC");
echo $conn->error;
while($result && ($row = $result->fetch_assoc())){
    $saldo -= $row['should'];
    $saldo += $row['have'];
    echo '<tr>';
    echo '<td>'.$row['docNum'].'</td>';
    echo '<td>'.substr($row['payDate'],0, 10).'</td>';
    echo '<td>'.$row['offAccount'].'</td>';
    echo '<td>'.$row['info'].'</td>';
    echo '<td>'.$row['tax'].'</td>';
    echo '<td>'.$row['should'].'</td>';
    echo '<td>'.$row['have'].'</td>';
    echo '<td>'.$saldo.'</td>';
    $docNum = $row['docNum'];
}
$docNum++;
$docDate = substr(getCurrentTimestamp(), 0, 10);
?>
</tbody>
</table>
<hr><br><br>

<?php if($account_row['manualBooking'] == 'TRUE'): ?>
    <form method="POST">
        <div class="row">
            <div class="col-md-1"><label>Nr.</label><input type="number" class="form-control" step="1" min="1" name="add_nr" value="<?php echo $docNum; ?>"/></div>
            <div class="col-md-2"><label><?php echo $lang['DATE']; ?></label><input type="text" class="form-control datepicker" name="add_date" value="<?php echo $docDate; ?>" /></div>
            <div class="col-md-4"><label><?php echo $lang['ACCOUNT']; ?></label>
                <select class="js-example-basic-single" name="add_account" ><option>...</otpion>
                    <?php
                    $result = $conn->query("SELECT * FROM accounts WHERE companyID IN (SELECT companyID FROM accounts WHERE id = $id) ");
                    while($result && ($row = $result->fetch_assoc())){
                        echo '<option value="'.$row['id'].'">'.$row['num'].' '.$row['name'].'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4"><label><?php echo $lang['VAT']; ?></label>
                <select class="js-example-basic-single" name="add_tax" ><option>...</otpion>
                    <?php
                    $result = $conn->query("SELECT * FROM taxRates");
                    while($result && ($row = $result->fetch_assoc())){
                        echo '<option value="'.$row['id'].'">'.$row['percentage'].'% '.$row['description'].'</option>';
                    }
                    ?>
                </select>
            </div>
        </div><br>
        <div class="row">
            <div class="col-md-5"><label>Text</label><input type="text" class="form-control" name="add_text" maxlength="64" placeholder="Optional" /></div>
            <div class="col-md-2"><label><?php echo $lang['FINANCE_DEBIT']; ?> <small>(Brutto)</small></label><input id="xor1" type="number" step="0.01" class="form-control" name="add_should" placeholder="0.0"/></div>
            <div class="col-md-2"><label><?php echo $lang['FINANCE_CREDIT']; ?> <small>(Brutto)</small></label><input id="xor2" type="number" step="0.01" class="form-control" name="add_have" placeholder="0.0"/></div>
            <div class="col-md-1"><label style="color:white">Add</label><button type="submit" class="btn btn-warning" name="addFinance"><?php echo $lang['ADD']; ?></button></div>        
        </div>
    </form>
<?php else: ?>
    <div class="alert alert-info">Auf diesen Konto können keine Buchungen getätigt werden. Um auf ein Konto buchen zu können, muss es unter den Einstellungen Ihres Mandanten als buchbar markiert werden und ein Konto der Klasse 2 sein. </div>
<?php endif; ?>

<script>
    $('#xor1').keyup(function(e) {
        if($(this).val() == ''){
            $('#xor2').prop('readonly', false);
        } else {
            $('#xor2').prop('readonly', true);
        }
    });
    $('#xor2').keyup(function(e) {
        if($(this).val() == ''){
            $('#xor1').prop('readonly', false);
        } else {
            $('#xor1').prop('readonly', true);
        }
    });
</script>

<?php include "footer.php"; ?>