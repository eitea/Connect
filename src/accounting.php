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
    //either should or have can be 0
    if($_POST['add_nr'] > 0 && test_Date($_POST['add_date'], "Y-m-d") && $_POST['add_account'] > 0 && (!empty($_POST['add_should']) xor !empty($_POST['add_have'])) && !empty($_POST['add_tax']) ){
        $account = intval($_POST['add_account']);
        $offAccount = $id;
        $docNum = $_POST['add_nr'];
        $date = $_POST['add_date'];
        $text = test_input($_POST['add_text']);
        $should = floatval($_POST['add_should']);
        $have = floatval($_POST['add_have']);
        $tax = intval($_POST['add_tax']);

        //STRRRIKES!
        $res = $conn->query("SELECT num FROM accounts WHERE id = $account");
        if($res && ( $rowP = $res->fetch_assoc())) $accNum = $rowP['num'];
        // else: strike
        if($accNum >= 5000 && $accNum < 8000 && $have ){
            //STRIKE
            echo $lang['ERROR_UNEXPECTED'];
            include 'footer.php';
            die ();
        } elseif($accNum >= 4000 && $accNum < 5000 && $should){
            //STRIKE
            echo $lang['ERROR_UNEXPECTED'];
            include 'footer.php';
            die ();
        } elseif(($accNum >= 8000 && $accNum < 10000) && ($accNum >= 1000 && $accNum < 3000)){
            $tax = 1; //id1 = no tax;
        }

        //journal
        $conn->query("INSERT INTO account_journal(docNum, userID, account, offAccount, payDate, inDate, tax, should, have, info)
        VALUES ($docNum, $userID, $account, $offAccount, '$date', UTC_TIMESTAMP, $tax, $should, $have, '$text')");
        echo $conn->error;
        $journalID = $conn->insert_id;

        //tax
        $res = $conn->query("SELECT * FROM taxRates WHERE id = $tax");
        $taxRow = $res->fetch_assoc();
        
        //prepare balance
        $stmt = $conn->prepare("INSERT INTO account_balance (journalID, accountID, should, have) VALUES(?, ?, ?, ?)");
        echo $conn->error;
        $stmt->bind_param("iidd", $journalID, $account, $should, $have);

        //tax deduction
        $should_tax = ($taxRow['percentage'] / 100) * $should;
        $have_tax = ($taxRow['percentage'] / 100) * $have;
        $should -= $should_tax;
        $have -= $have_tax;
        $stmt->execute();

        $account2 = $account3 = '';
        if($taxRow['account2']){
            $res = $conn->query("SELECT id FROM accounts WHERE num = ".$taxRow['account2']." AND companyID IN (SELECT companyID FROM accounts WHERE id = $id) "); echo $conn->error;
            $account2 = $res->fetch_assoc()['id'];
        }
        if($taxRow['account3']){
            $res = $conn->query("SELECT id FROM accounts WHERE num = ".$taxRow['account3']." AND companyID IN (SELECT companyID FROM accounts WHERE id = $id) "); echo $conn->error;
            $account3 = $res->fetch_assoc()['id'];
        }

        //tax balance
        if($taxRow['code'] == '2' && $accNum < 1000){
            $temp_should = $should; //save
            $temp_have = $have;
            $should = $should_tax; //overwrite
            $have = $have_tax;
            $account = $account2;
            $stmt->execute(); //apply
            $should = $temp_should + $should_tax; //restore
            $have = $temp_have + $have_tax;
        } elseif($taxRow['code'] == '19'){
            if($account2 && $account3){
                $temp_should = $should; //save
                $temp_have = $have;
    
                $should = $should_tax;
                $have = $have_tax;
                $account = $account2;
                $stmt->execute();
    
                $temp = $should;
                $should = $have;
                $have = $temp;
                $account = $account3;
                $stmt->execute();
                
                $should = $temp_should + $should_tax; //restore
                $have = $temp_have + $have_tax;
            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_UNEXPECTED'].' Steuersätze cd 19 nicht richtig definiert.</div>';
            }
        }

        //offset balance
        $temp = $should;
        $should = $have;
        $have = $temp;
        $account = $offAccount;
        $stmt->execute();
        
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
    <th><?php echo $lang['FINANCE_DEBIT']; ?></th>
    <th><?php echo $lang['FINANCE_CREDIT'];?></th>
    <th>Saldo</th>
</tr></thead>
<tbody>
<?php
$docNum = $saldo = 0;
$result = $conn->query("SELECT account_journal.*, accounts.num, account_balance.have as netto_have, account_balance.should as netto_should FROM account_journal, account_balance, accounts
WHERE account_journal.id = account_balance.journalID AND account_journal.account = accounts.id AND account_balance.accountID = $id ORDER BY docNum ASC");
echo $conn->error;
while($result && ($row = $result->fetch_assoc())){
    $saldo -= $row['netto_should'];
    $saldo += $row['netto_have'];
    echo '<tr>';
    echo '<td>'.$row['docNum'].'</td>';
    echo '<td>'.substr($row['payDate'],0, 10).'</td>';
    echo '<td>'.$row['num'].'</td>';
    echo '<td>'.$row['info'].'</td>';
    echo '<td>'.$row['netto_should'].'</td>';
    echo '<td>'.$row['netto_have'].'</td>';
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
    <form method="POST" class="well">
        <div class="row">
            <div class="col-md-1"><label>Nr.</label><input type="number" class="form-control" step="1" min="1" name="add_nr" value="<?php echo $docNum; ?>"/></div>
            <div class="col-md-2"><label><?php echo $lang['DATE']; ?></label><input type="text" class="form-control datepicker" name="add_date" value="<?php echo $docDate; ?>" /></div>
            <div class="col-md-4"><label><?php echo $lang['ACCOUNT']; ?></label>
                <select id="account" class="js-example-basic-single" name="add_account" ><option>...</otpion>
                    <?php
                    $result = $conn->query("SELECT * FROM accounts WHERE companyID IN (SELECT companyID FROM accounts WHERE id = $id) ");
                    while($result && ($row = $result->fetch_assoc())){
                        echo '<option value="'.$row['id'].'">'.$row['num'].' '.$row['name'].'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4"><label><?php echo $lang['VAT']; ?></label>
                <select id="tax" class="js-example-basic-single" name="add_tax" >
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
            <div class="col-md-2"><label><?php echo $lang['FINANCE_DEBIT']; ?> <small>(Brutto)</small></label><input id="should" type="number" step="0.01" class="form-control" name="add_should" placeholder="0.0"/></div>
            <div class="col-md-2"><label><?php echo $lang['FINANCE_CREDIT']; ?> <small>(Brutto)</small></label><input id="have" type="number" step="0.01" class="form-control" name="add_have" placeholder="0.0"/></div>
            <div class="col-md-1"><label style="color:white">Add</label><button type="submit" class="btn btn-warning" name="addFinance"><?php echo $lang['ADD']; ?></button></div>        
        </div>
    </form>
<?php else: ?>
    <div class="alert alert-info">Gegen dieses Konto können keine Buchungen getätigt werden. Um gegen ein Konto buchen zu können, muss es unter den Einstellungen Ihres Mandanten als buchbar markiert werden und ein Konto der Klasse 2 sein. </div>
<?php endif; ?>

<script>
var active_should = true;
var active_have = true;

function disenable(xor1, xor2, active){
    if($('#' + xor1).val()){
        $('#' + xor2).val('');
        $('#' + xor2).prop('readonly', true);
    } else if(active) {
        $('#' + xor2).prop('readonly', false);
    }
}

$('#should').keyup(function(e) { disenable('should', 'have', active_have); });
$('#have').keyup(function(e) { disenable('have', 'should', active_should); });

$('#account').change(function(e) {
    $('#tax').select2().attr('disabled', false);
    active_should = true;
    active_have = true;
    disenable('should', 'have', true);
    disenable('have', 'should', true);
    var account = $(this).select2('data')[0].text.split(' ')[0];
    if(account >= 5000 && account < 8000){
        active_should = true;
        active_have = false;
        $('#have').val('');
        $('#have').prop('readonly', true);
        $('#should').prop('readonly', false);
    } else if(account >= 4000 && account < 5000){
        active_have = true;
        active_should = false;
        $('#should').val('');
        $('#should').prop('readonly', true);
        $('#have').prop('readonly', false);
    } else if((account >= 1000 && account < 4000) || account >= 8000 && account < 10000){
        $('#tax').val(1).trigger('change');
        $('#tax').select2().attr('disabled', true);
    }
});

</script>

<?php include "footer.php"; ?>