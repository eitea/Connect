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
if(isset($_POST['addFinance']) || isset($_POST['editJournalEntry'])){
    $accept = true;
    //either should or have can be 0
    if($_POST['add_nr'] > 0 && test_Date($_POST['add_date'], "Y-m-d") && $_POST['add_account'] > 0 && (!empty($_POST['add_should']) xor !empty($_POST['add_have']))){
        $account = intval($_POST['add_account']);
        $offAccount = $id;
        $docNum = $_POST['add_nr'];
        $date = $_POST['add_date'];
        $text = test_input($_POST['add_text']);
        $should = $temp_should = floatval($_POST['add_should']);
        $have = $temp_have = floatval($_POST['add_have']);
        $tax = 1;
        if(isset($_POST['add_tax'])) $tax = intval($_POST['add_tax']);

        //STRRRIKES!
        $res = $conn->query("SELECT num FROM accounts WHERE id = $account");
        if($res && ( $rowP = $res->fetch_assoc())) $accNum = $rowP['num'];
        // else: strike
        if($accNum >= 5000 && $accNum < 8000 && $have ){
            //STRIKE
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_UNEXPECTED'].'</div>';
            include 'footer.php';
            die ();
        } elseif($accNum >= 4000 && $accNum < 5000 && $should){
            //STRIKE
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_UNEXPECTED'].'</div>';
            include 'footer.php';
            die ();
        } elseif(($accNum >= 8000 && $accNum < 10000) && ($accNum >= 1000 && $accNum < 3000)){
            $tax = 1; //id1 = no tax;
        }

         //journal
         $conn->query("INSERT INTO account_journal(docNum, userID, account, offAccount, payDate, inDate, taxID, should, have, info)
         VALUES ($docNum, $userID, $account, $offAccount, '$date', UTC_TIMESTAMP, $tax, $should, $have, '$text')");         
         $journalID = $conn->insert_id;

         if($conn->error){
             $accept = false;
             echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Journal Failed: '.$conn->error.'</div>';
        } else {
            //tax
            $res = $conn->query("SELECT percentage, account2, account3, code FROM taxRates WHERE id = $tax");
            if(!$res || $res->num_rows < 1) $accept = false; //STRIKE
            $taxRow = $res->fetch_assoc();
            
            //prepare balance
            $stmt = $conn->prepare("INSERT INTO account_balance (journalID, accountID, should, have) VALUES(?, ?, ?, ?)");
            echo $conn->error;
            $stmt->bind_param("iidd", $journalID, $account, $should, $have);

            $account2 = $account3 = '';
            if($taxRow['account2']){
                $res = $conn->query("SELECT id FROM accounts WHERE num = ".$taxRow['account2']." AND companyID IN (SELECT companyID FROM accounts WHERE id = $id) "); echo $conn->error;
                if($res && $res->num_rows > 0) $account2 = $res->fetch_assoc()['id'];
            }
            if($taxRow['account3']){
                $res = $conn->query("SELECT id FROM accounts WHERE num = ".$taxRow['account3']." AND companyID IN (SELECT companyID FROM accounts WHERE id = $id) "); echo $conn->error;
                if($res && $res->num_rows > 0) $account3 = $res->fetch_assoc()['id'];
            }
        }
         
        if($accept){
             //tax deduction
            $should_tax = ($taxRow['percentage'] / 100) * $should;
            $have_tax = ($taxRow['percentage'] / 100) * $have;
            
            //tax balance
            if($account2){
                //netto balance
                $should = $temp_should - $should_tax;
                $have = $temp_have - $have_tax;
                $stmt->execute();
                //tax
                $should = $should_tax;
                $have = $have_tax;
                $account = $account2;
                $stmt->execute();
                //restore
                $should = $temp_should;
                $have = $temp_have;
            } else {
                //no tax deduction
                $stmt->execute();
            }

            if($account3){
                //turnaround tax
                $should = $have_tax;
                $have = $should_tax;
                $account = $account3;
                $stmt->execute();
                //tax deduct should and have
                $should = $temp_should - $should_tax;
                $have = $temp_have - $have_tax;
            }

            //turnaround offset balance
            $temp = $should;
            $should = $have;
            $have = $temp;
            $account = $offAccount;
            $stmt->execute();
        }
    } else {
        $accept = false;
        echo '<div class="alert alert-danger" class="close"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
    }

    if(isset($_POST['editJournalEntry']) && $accept){
        $val = intval($_POST['editJournalEntry']);
        $conn->query("DELETE FROM account_journal WHERE id = $val");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        }
    }
}

$account_select = $tax_select = '';
$result = $conn->query("SELECT id, num, name, companyID FROM accounts WHERE companyID IN (SELECT companyID FROM accounts WHERE id = $id) ");
while($result && ($row = $result->fetch_assoc())){
    $account_select .= '<option value="'.$row['id'].'">'.$row['num'].' '.$row['name'].'</option>';
    $cmpID = $row['companyID'];
}
$result = $conn->query("SELECT * FROM taxRates");
while($result && ($row = $result->fetch_assoc())){
    $tax_select .= '<option value="'.$row['id'].'">'.sprintf('%02d',$row['id']).' - '.$row['percentage'].'% '.$row['description'].'</option>';
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
    <th style="text-align:right"><?php echo $lang['FINANCE_DEBIT']; ?></th>
    <th style="text-align:right"><?php echo $lang['FINANCE_CREDIT'];?></th>
    <th style="text-align:right">Saldo</th>
    <th></th>
</tr></thead>
<tbody>
<?php
$docNum = 1;
$docDate = getCurrentTimestamp();
$modals = '';
$saldo = 0;
$result = $conn->query("SELECT account_journal.*, accounts.num, account_balance.have as netto_have, account_balance.should as netto_should FROM account_journal, account_balance, accounts
WHERE account_journal.id = account_balance.journalID AND account_journal.account = accounts.id AND account_balance.accountID = $id ORDER BY docNum, inDate ");
echo $conn->error;
while($result && ($row = $result->fetch_assoc())){
    $docNum = $row['docNum'];
    $docDate = $row['payDate'];
    $saldo -= $row['netto_should'];
    $saldo += $row['netto_have'];
    echo '<tr>';
    echo '<td>'.$row['docNum'].'</td>';
    echo '<td>'.substr($row['payDate'],0, 10).'</td>';
    echo '<td>'.$row['num'].'</td>';
    echo '<td>'.$row['info'].'</td>';
    echo '<td style="text-align:right">'.number_format($row['netto_should'], 2, ',', '.').'</td>';
    echo '<td style="text-align:right">'.number_format($row['netto_have'], 2, ',', '.').'</td>';
    echo '<td style="text-align:right">'.number_format($saldo, 2, ',', '.').'</td>';
    if($account_row['manualBooking'] == 'TRUE'){
        echo '<td><button type="button" class="btn btn-default" title="Editieren" data-toggle="modal" data-target=".edit-journal-'.$row['id'].'" ><i class="fa fa-pencil"></i></button></td>';

        $modals .= '<div class="modal fade edit-journal-'.$row['id'].'"><div class="modal-dialog modal-content modal-md"><form method="POST">
        <div class="modal-header"><h4>'.$lang['EDIT'].'</h4></div><div class="modal-body"><div class="row">
        <div class="col-md-4"><label>Nr.</label><input type="number" class="form-control" step="1" min="1" name="add_nr" value="'.$row['docNum'].'"/><br></div>
        <div class="col-md-8"><label>'.$lang['DATE'].'</label><input type="text" class="form-control datepicker" name="add_date" value="'.substr($row['payDate'],0,10).'" /><br></div>
        <div class="col-md-6"><label>'.$lang['ACCOUNT'].'</label><select class="js-example-basic-single" name="add_account" ><option>...</otpion>'.$account_select.'</select><br></div>
        <div class="col-md-6"><label>'.$lang['VAT'].'</label><select class="js-example-basic-single" name="add_tax">'.$tax_select.'</select><br><br></div>
        <div class="col-md-6"><label>Text</label><input type="text" class="form-control" name="add_text" maxlength="64" value="'.$row['info'].'" /><br></div>
        <div class="col-md-3"><label>'.$lang['FINANCE_DEBIT'].'<small> (Brutto)</small></label><input type="number" step="0.01" class="form-control" name="add_should" value="'.$row['should'].'"/></div>
        <div class="col-md-3"><label>'.$lang['FINANCE_CREDIT'].'<small> (Brutto)</small></label><input type="number" step="0.01" class="form-control" name="add_have" value="'.$row['have'].'"/></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" value="'.$row['id'].'" name="editJournalEntry">'.$lang['SAVE'].'</button></div></form></div></div>';

        //$("[name='add_product_unit']").val(res[4]).trigger('change');
    } else {
        echo '<td></td>';
    }
}
?>
</tbody>
</table>
<hr><br><br>
<?php if($account_row['manualBooking'] == 'TRUE'): echo $modals; ?>
    <form method="POST" class="well">
        <div class="row">
            <div class="col-md-1"><label>Nr.</label><input type="number" class="form-control" step="1" min="1" name="add_nr" value="<?php echo $docNum; ?>"/></div>
            <div class="col-md-2"><label><?php echo $lang['DATE']; ?></label><input type="text" class="form-control datepicker" name="add_date" value="<?php echo substr($docDate,0,10); ?>" /></div>
            <div class="col-md-4"><label><?php echo $lang['ACCOUNT']; ?></label>
                <select id="account" class="js-example-basic-single" name="add_account" ><option>...</otpion><?php echo $account_select; ?></select>
                <small><a href="plan?n=<?php echo $cmpID; ?>" ><?php echo $lang['ACCOUNT_PLAN'];?></a></small>
            </div>
            <div class="col-md-4"><label><?php echo $lang['VAT']; ?></label>
                <select id="tax" class="js-example-basic-single" name="add_tax" ><?php echo $tax_select; ?></select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-5"><label>Text</label><input type="text" class="form-control" name="add_text" maxlength="64" placeholder="Optional" /></div>
            <div class="col-md-2"><label><?php echo $lang['FINANCE_DEBIT']; ?> <small>(Brutto)</small></label><input id="should" type="number" step="0.01" class="form-control" name="add_should" placeholder="0.0"/></div>
            <div class="col-md-2"><label><?php echo $lang['FINANCE_CREDIT']; ?> <small>(Brutto)</small></label><input id="have" type="number" step="0.01" class="form-control" name="add_have" placeholder="0.0"/></div>
            <div class="col-md-1"><label style="color:transparent">O.K.</label><button type="submit" class="btn btn-warning" name="addFinance"><?php echo $lang['ADD']; ?></button></div>        
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
        $('#' + xor2).attr('tabindex', '-1');
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
        $('#have').attr('tabindex', '-1');
        $('#should').prop('readonly', false);
    } else if(account >= 4000 && account < 5000){
        active_have = true;
        active_should = false;
        $('#should').val('');
        $('#should').prop('readonly', true);
        $('#should').attr('tabindex', '-1');
        $('#have').prop('readonly', false);
    } else if((account >= 1000 && account < 4000) || account >= 8000 && account < 10000){
        $('#tax').val(1).trigger('change');
        $('#tax').select2().attr('disabled', true);
        $('#tax').select2().attr('tabindex', '-1');
    }
});

$('select').on(
    'select2:select',(
        function(){
            $(this).focus();
        }
    )
);

</script>

<?php include "footer.php"; ?>