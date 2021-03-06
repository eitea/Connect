<?php
require dirname(__DIR__).DIRECTORY_SEPARATOR.'header.php';

$filterings = array("savePage" => $this_page, 'date' => array(date('Y-m')));
$show_undo = false;

if (empty($_GET['cmp']) || !in_array($_GET['cmp'], $available_companies)) {
	echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Zugriff verweigert</div>';
    include dirname(__DIR__) . '/footer.php';
    die();
} else {
	$cmpID = intval($_GET['cmp']);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
	if (isset($_POST['undo'])) {
	    $res = $conn->query("DELETE FROM account_journal WHERE status = 'rebooking' AND userID = $userID ORDER BY inDate DESC LIMIT 1");
	    if ($conn->error) {
	        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
	    } else {
	        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_DELETE'] . '</div>';
	    }
	}
	if (!empty($_POST['webID']) && !isset($_POST['transferToWEB'])) {
	    $val = intval($_POST['webID']);
	    $result = $conn->query("SELECT * FROM receiptBook WHERE id = $val");
	    $row = $result->fetch_assoc();
	    //TODO: if these dont match: STRIKE
	    $_POST['add_should'] = $row['amount'];
	    $_POST['add_tax'] = $row['taxID'];
	    if($_POST['add_should']!=$_POST['add_tax']){
	        $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
	        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Ungültiger Steuersatz.</strong> '.$lang['ERROR_STRIKE'].'</div>';
	    }
	}
	$journalID = false;
	if (isset($_POST['addFinance']) || isset($_POST['editJournalEntry'])) {
	    $accept = true;
	    //code below is highly sensitive. do not touch.
	    if ($_POST['add_nr'] > 0 && test_Date($_POST['add_date'], "Y-m-d") && $_POST['add_account'] > 0 && $_POST['add_offsetaccount'] > 0 && ($_POST['add_should'] == 0 xor $_POST['add_have'] == 0)) {
	        $addAccount = intval($_POST['add_account']);
	        $offAccount = intval($_POST['add_offsetaccount']);
	        $docNum = $_POST['add_nr'];
	        $date = $_POST['add_date'];
	        $text = test_input($_POST['add_text']);
	        $should = $temp_should = floatval($_POST['add_should']);
	        $have = $temp_have = floatval($_POST['add_have']);
	        $tax = 1;
	        if (isset($_POST['add_tax'])) {
	            $tax = intval($_POST['add_tax']);
	        }
	        $res = $conn->query("SELECT * FROM accountingLocks WHERE YEAR(lockDate) = YEAR('$date') AND MONTH(lockDate) = MONTH('$date') AND companyID = $cmpID");
	        if ($res && $res->num_rows > 0) {
	            $accept = false;
	            echo '<div class="alert alert-danger" class="close"><a href="#" data-dismiss="alert" class="close">&times;</a>Dieser Monat wurde gesperrt und darf nicht bebucht werden.</div>';
	        }
	        $res = $conn->query("SELECT num FROM accounts WHERE id = $offAccount");
	        if ($res && ($rowP = $res->fetch_assoc())) {
	            $accNum = $rowP['num'];
	        } else { //else STRIKE
	            $conn->query("UPDATE UserData SET strikeCount = strikecount + 1 WHERE id = $userID");
	            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Ungültiger Monat.</strong> '.$lang['ERROR_STRIKE'].'</div>';
	        }

	        if (($accNum >= 4000 && $accNum < 5000)) {
	            $tax = 1; //id1 = no tax;
	        }
	        if (!empty($_POST['webID'] || isset($_POST['transferToWEB'])) && ($accNum < 5000 || $accNum >= 6000)) { //STRIKE
	            $conn->query("UPDATE UserData SET strikeCount = strikeCount + 1 WHERE id = $userID");
	            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Ungültige Konto Klasse.</strong> Es dürfen nur Buchungen auf Konten der Klasse 5 ins WEB übertragen werden. '.$lang['ERROR_STRIKE'].'</div>';
	            $accept = false;
	        }
	        if ($accept) {
	            //journal
	            $conn->query("INSERT INTO account_journal(docNum, userID, account, offAccount, payDate, inDate, taxID, should, have, info, status)
	            VALUES ($docNum, $userID, $addAccount, $offAccount, '$date', UTC_TIMESTAMP, $tax, $should, $have, '$text', 'rebooking')");
	            if ($conn->error) {
	                $accept = false;
	            } else {
	                $journalID = $conn->insert_id;
	            }
	        }
	        if ($accept) {
	            //tax
	            $res = $conn->query("SELECT percentage, account2, account3, code FROM taxRates WHERE id = $tax");
	            if (!$res || $res->num_rows < 1) {
	                $accept = false;
	                $conn->query("UPDATE UserData SET strikeCount = strikeCount + 1 WHERE id = $userID"); //STRIKE
	                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Ungültiger Steuersatz.</strong> '.$lang['ERROR_STRIKE'].'</div>';
	            }
	            $taxRow = $res->fetch_assoc();

	            //prepare balance
	            $stmt = $conn->prepare("INSERT INTO account_balance (journalID, accountID, should, have) VALUES(?, ?, ?, ?)");
	            echo $conn->error;
	            $stmt->bind_param("iidd", $journalID, $account, $should, $have);

	            $account2 = $account3 = '';
	            if ($taxRow['account2']) {
	                $res = $conn->query("SELECT id FROM accounts WHERE num = " . $taxRow['account2'] . " AND companyID = $cmpID LIMIT 1");
	                echo $conn->error;
	                if ($res && $res->num_rows > 0) {
	                    $account2 = $res->fetch_assoc()['id'];
	                }
	            }
	            if ($taxRow['account3']) {
	                $res = $conn->query("SELECT id FROM accounts WHERE num = " . $taxRow['account3'] . " AND companyID = $cmpID LIMIT 1");
	                echo $conn->error;
	                if ($res && $res->num_rows > 0) {
	                    $account3 = $res->fetch_assoc()['id'];
	                }
	            }
	        }
	        if ($accept) {
	            //tax calculation
	            if ($account2 && $account3) {
	                $should_tax = $should * ($taxRow['percentage'] / 100);
	                $have_tax = $have * ($taxRow['percentage'] / 100);
	            } else {
	                $should_tax = $should - ($should * 100) / (100 + $taxRow['percentage']);
	                $have_tax = $have - ($have * 100) / (100 + $taxRow['percentage']);
	            }

	            $should = $temp_have;
	            $have = $temp_should;
	            //account balance
	            if ($account2) {
	                $should = $have_tax;
	                $have = $should_tax;
	                $account = $account2;
	                $stmt->execute();
	                if ($account3) {
	                    $should = $temp_have;
	                    $have = $temp_should;
	                } else {
	                    $have = $temp_should - $should_tax;
	                    $should = $temp_have - $have_tax;
	                }
	            }
	            $account = $addAccount;
	            $stmt->execute();

	            //offAccount balance
	            $have = $temp_have;
	            $should = $temp_should;
	            if ($account3) {
	                $have = $have_tax;
	                $should = $should_tax;
	                $account = $account3;
	                $stmt->execute();
	                if ($account2) {
	                    $should = $temp_should;
	                    $have = $temp_have;
	                } else {
	                    $should = $temp_should - $should_tax;
	                    $have = $temp_have - $have_tax;
	                }
	            }
	            $account = $offAccount;
	            $stmt->execute();

	            $show_undo = true;
	        }
	    } else {
	        $accept = false;
	        echo '<div class="alert alert-danger" class="close"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['ERROR_INVALID_DATA'] . '</div>';
	    }
	    if (isset($_POST['editJournalEntry']) && $accept) {
	        $val = intval($_POST['editJournalEntry']);
	        $conn->query("DELETE FROM account_journal WHERE id = $val");
	        if ($conn->error) {
	            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
	        } else {
	            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_SAVE'] . '</div>';
	        }
	    }
	}
	if (isset($_POST['transferToWEB']) && !empty($_POST['webID'])) {
	    //journal creation may not fail if both were entered (MARK: EDIT) User will have to try again with valid parameters
	    $journalID = false;
	    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Buchungen können nicht vom und ins WEB gleichzeitig übertragen werden.</div>';
	}
	if (isset($_POST['transferToWEB']) && $journalID) {
	    if (!empty($_POST['add_supplier']) && test_Date($_POST['add_invoiceDate'], 'Y-m-d') && !empty($_POST['add_tax']) && !empty($_POST['add_should'])) {
	        $date = $_POST['add_invoiceDate'];
	        $supplierID = intval($_POST['add_supplier']);
	        $taxID = intval($_POST['add_tax']);
	        $text = test_input($_POST['add_text']);
	        $amount = floatval($_POST['add_should']);
	        $stmt = $conn->prepare("INSERT INTO receiptBook (supplierID, taxID, invoiceDate, info, amount, journalID) VALUES(?, ?, ?, ?, ?, ?)");
	        $stmt->bind_param("iissdi", $supplierID, $taxID, $date, $text, $amount, $journalID);
	        $stmt->execute();
	        $stmt->close();
	        if ($conn->error) {
	            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
	        }
	    } else {
	        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['ERROR_MISSING_FIELDS'] . '</div>';
	    }
	} elseif (!empty($_POST['webID']) && $journalID) {
	    $val = intval($_POST['webID']);
	    $conn->query("UPDATE receiptBook SET journalID = $journalID WHERE id = $val");
	    if ($conn->error) {
	        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
	    }
	}
}


include dirname(__DIR__) . '/misc/new_client_buttonless.php';

$lockedMonths = $conn->query("SELECT lockDate FROM accountingLocks WHERE companyID = $cmpID");
$lockedMonths = array_column($lockedMonths->fetch_all(), 0);

$account_select = $tax_select = '';
$web_select = $supplier_select = '<option value="0">...</option><option value="new" >+ Neu</option>';
$result = $conn->query("SELECT id, num, name, companyID FROM accounts WHERE companyID = $cmpID");
while ($result && ($row = $result->fetch_assoc())) {
    $account_select .= '<option value="' . $row['id'] . '">' . $row['num'] . ' ' . $row['name'] . '</option>';
}
$result = $conn->query("SELECT * FROM taxRates");
while ($result && ($row = $result->fetch_assoc())) {
    $tax_select .= '<option value="' . $row['id'] . '">' . sprintf('%02d', $row['id']) . ' - ' . $row['percentage'] . '% ' . $row['description'] . '</option>';
}
$result = $conn->query("SELECT receiptBook.*, clientData.name, taxRates.percentage
	FROM receiptBook INNER JOIN taxRates ON  taxRates.id = taxID INNER JOIN clientData ON clientData.id = supplierID
	WHERE companyID = $cmpID AND (journalID IS NULL OR journalID = 0)");
while ($result && ($row = $result->fetch_assoc())) {
    $web_select .= '<option value="' . $row['id'] . '">' . substr($row['invoiceDate'], 0, 10) . ' - ' . $row['name'] . ' - ' . $row['info'] . ' (' . number_format(($row['amount']), 2, ',', '.') . '; ' . $row['percentage'] . '%)';
}
$result = $conn->query("SELECT id, name FROM $clientTable WHERE isSupplier = 'TRUE' AND companyID = $cmpID");
while ($result && ($row = $result->fetch_assoc())) {
    $supplier_select .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
}
?>

<div class="page-header"><h3>Umbuchung
	<div class="page-header-button-group"><?php include dirname(__DIR__) . '/misc/set_filter.php'; include __DIR__.'/lockAccounting.php';?>
	<?php if ($show_undo): ?>
	    <form method="POST" style="display:inline"><button type="submit" name="undo" class="btn btn-warning" >Undo</button></form>
	<?php endif;?>
</div></h3></div>

<table class="table table-hover">
    <thead><tr>
        <th>Nr.</th>
        <th><?php echo $lang['DATE']; ?></th>
        <th><?php echo $lang['ACCOUNT']; ?></th>
        <th>Text</th>
        <th>Steuer Nr.</th>
        <th style="text-align:right"><?php echo $lang['FINANCE_DEBIT']; ?></th>
        <th style="text-align:right"><?php echo $lang['FINANCE_CREDIT']; ?></th>
        <th>WEB</th>
    </tr></thead>
    <tbody>
        <?php
        $dateQuery = '';
        if ($filterings['date'][0]) {
            $dateQuery = "AND payDate LIKE '" . $filterings['date'][0] . "-%'";
        }

        $docDate = getCurrentTimestamp();
        $modals = '';
        $docNum = 1;
        $result = $conn->query("SELECT account_journal.*, accounts.num, account_balance.have AS netto_have, accountID,
			account_balance.should AS netto_should, r1.id AS receiptID
	        FROM account_balance INNER JOIN account_journal ON account_journal.id = account_balance.journalID
	        INNER JOIN accounts ON account_balance.accountID = accounts.id
	        LEFT JOIN receiptBook r1 ON r1.journalID = account_balance.journalID
	        WHERE account_journal.status = 'rebooking' $dateQuery ORDER BY docNum, payDate, inDate ");
        echo $conn->error;
        while ($result && ($row = $result->fetch_assoc())) {
            $docNum = $row['docNum'];
            $docDate = $row['payDate'];
            echo '<tr>';
            echo '<td>' . $row['docNum'] . '</td>';
            echo '<td>' . substr($row['payDate'], 0, 10) . '</td>';
            echo '<td><a href="account?v=' . $row['accountID'] . '" class="btn btn-link" >' . $row['num'] . '</a></td>';
            echo '<td>' . $row['info'] . '</td>';
            echo '<td>' . sprintf('%02d', $row['taxID']) . '</td>';
            echo '<td style="text-align:right">' . number_format($row['netto_should'], 2, ',', '.') . '</td>';
            echo '<td style="text-align:right">' . number_format($row['netto_have'], 2, ',', '.') . '</td>';
            if ($row['receiptID']) {echo '<td>' . $lang['YES'] . '</td>';} else {echo '<td>' . $lang['NO'] . '</td>';}
            echo '</tr>';
			$docNum++;
        }
        ?>
    </tbody>
</table>

<form method="POST" class="well">
	<div class="row form-group">
		<div id="openWebButton" class="col-sm-2">
			<button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#openWEB" title="Wareneingangsbuch"><?php echo $lang['FROM'] . ' WEB'; ?></button>
		</div>
	</div>
	<div class="row">
		<div class="col-md-1"><label>Nr.</label><input type="number" class="form-control" name="add_nr" value="<?php echo $docNum; ?>" readonly autofocus/></div>
		<div class="col-md-2"><label><?php echo $lang['DATE']; ?></label>
			<input type="text" class="form-control datepicker" name="add_date" value="<?php echo substr($docDate, 0, 10); ?>" />
		</div>
		<div class="col-md-3"><label><?php echo $lang['ACCOUNT']; ?></label>
			<select id="account" class="js-example-basic-single accounts" name="add_account" ><option>...</otpion><?php echo $account_select; ?></select>
				<small><a href="plan?cmp=<?php echo $cmpID; ?>" tabindex="-1" ><?php echo $lang['ACCOUNT_PLAN']; ?></a></small>
		</div>
		<div class="col-md-3"><label><?php echo $lang['OFFSET_ACCOUNT']; ?></label>
			<select id="offAccount" class="js-example-basic-single accounts" name="add_offsetaccount" ><option>...</otpion><?php echo $account_select; ?></select>
		</div>
		<div class="col-md-3"><label><?php echo $lang['VAT']; ?></label>
			<select id="tax" class="js-example-basic-single" name="add_tax" ><?php echo $tax_select; ?></select>
		</div>
	</div>
	<div class="row form-group">
		<div class="col-md-3"><label>Text</label><input id="infoText" type="text" class="form-control" name="add_text" maxlength="64" placeholder="Optional" /></div>
		<div class="col-md-2"><label><?php echo $lang['FINANCE_DEBIT']; ?> <small>(Brutto)</small></label><input id="should" type="number" step="0.01" class="form-control money" name="add_should" placeholder="0,00"/></div>
		<div class="col-md-2"><label><?php echo $lang['FINANCE_CREDIT']; ?> <small>(Brutto)</small></label><input id="have" type="number" step="0.01" class="form-control money" name="add_have" placeholder="0,00"/></div>
		<div class="col-md-2"><label style="color:transparent">O.K.</label><button id="addFinance" type="submit" class="btn btn-warning btn-block" name="addFinance"><?php echo $lang['ADD']; ?></button></div>
		<div class="col-md-3"><label id="transferToWEB" style="display:none;padding-top:28px;"><input type="checkbox" id="transferToWEBc" name="transferToWEB" value="1" />Ins WEB</label></div>
	</div>
	<div class="row">
		<span id="transferToWebInputs" style="display:none">
			<div class="col-md-3"><label><?php echo $lang['SUPPLIER']; ?></label><select name="add_supplier" class="js-example-basic-single"><?php echo $supplier_select; ?></select></div>
			<div class="col-md-2"><label><?php echo $lang['RECEIPT_DATE']; ?></label><input id="receiptDate" type="text" class="form-control datepicker" name="add_invoiceDate" value="" /></div>
		</span>
	</div>
	<input type="hidden" id="webID" name="webID" value=""/>
</form>
<form method="POST"><button type="submit" class="btn btn-link btn-sm">Reset</button></form>


<div id="editingModalDiv"></div>
<div id="openWEB" class="modal fade">
	<div class="modal-dialog modal-content modal-md">
		<div class="modal-header"><h4><?php echo $lang['RECEIPT_BOOK']; ?> - <small>Zeile auswählen</small></h4></div>
		<div class="modal-body">
			<table class="table table-hover">
				<thead><tr>
					<th>Nr.</th>
					<th><?php echo $lang['RECEIPT_DATE']; ?></th>
					<th><?php echo $lang['SUPPLIER']; ?></th>
					<th>Infotext</th>
					<th><?php echo $lang['AMOUNT']; ?> <small>(Brutto)</small></th>
					<th><?php echo $lang['TAXES']; ?></th>
					<th><?php echo $lang['VAT']; ?></th>
				</tr></thead>
				<tbody>
					<?php
					$i = 1;
					$res = $conn->query("SELECT receiptBook.*, clientData.name, taxRates.percentage
					FROM receiptBook INNER JOIN taxRates ON  taxRates.id = taxID INNER JOIN clientData ON clientData.id = supplierID
						WHERE companyID = $cmpID AND (journalID IS NULL OR journalID = 0)");
					while ($row = $res->fetch_assoc()) {
						echo '<tr onclick="webFillout(' . $row['id'] . ', ' . $row['amount'] . ',  \'' . $row['info'] . '\', ' . $row['taxID'] . ');">';
						echo '<td>' . $i++ . '</td>';
						echo '<td>' . substr($row['invoiceDate'], 0, 10) . '</td>';
						echo '<td>' . $row['name'] . '</td>';
						echo '<td>' . $row['info'] . '</td>';
						echo '<td>' . number_format(($row['amount']), 2, ',', '.') . '</td>';
						echo '<td>' . $row['percentage'] . '% </td>';
						echo '<td>' . number_format($row['amount'] - ($row['amount'] * 100) / (100 + $row['percentage']), 2, ',', '.') . '</td>';
						echo '<td>' . '</td>';
						echo '</tr>';
					}
					?>
				</tbody>
			</table>
		</div>
		<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button></div>
	</div>
</div>
<script>
$("select[name=add_supplier]").change(function(){
  if($(this).val() == 'new'){
    $('#create_client').modal().toggle();
  }
});

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
function showHide(show_id, hide_id){
    $('#'+show_id).show();
    $('#'+hide_id).hide();
}
$('#should').keyup(function(e) { disenable('should', 'have', active_have); });
$('#have').keyup(function(e) { disenable('have', 'should', active_should); });

$('#account').change(function(e) {
    if($('#webID').val()) return;
    var account = $(this).select2('data')[0].text.split(' ')[0];
    if(account >= 4000 && account < 5000){
		$('#offAccount').children().filter(function(){ //remove all class 4
			var account = $(this).text().split(' ')[0];
			return (account >= 4000 && account < 5000);
		}).prop('disabled',true);
	} else {
		$('#offAccount').children().filter(function(){ //remove all class 4
			var account = $(this).text().split(' ')[0];
			return (account >= 4000 && account < 5000);
		}).prop('disabled',false);
		$('#offAccount').select2();
	}
});

$('#offAccount').change(function(e) {
    if($('#webID').val()) return;
    var account = $(this).select2('data')[0].text.split(' ')[0];

    if(account >= 4000 && account < 5000){
		$('#account').children().filter(function(){ //remove all class 4
			var account = $(this).text().split(' ')[0];
			return (account >= 4000 && account < 5000);
		}).prop('disabled',true);
	} else {
		$('#account').children().filter(function(){ //remove all class 4
			var account = $(this).text().split(' ')[0];
			return (account >= 4000 && account < 5000);
		}).prop('disabled',false);
		$('#account').select2();
	}
});


$('#transferToWEBc').click(function(){
    if(this.checked) {
        showHide('transferToWebInputs', 'openWebButton');
    } else {
        showHide('openWebButton', 'transferToWebInputs');
    }
    $('#receiptDate').trigger('change');
});
function webFillout(id, amount, text, tax){
    $('#openWEB').modal('toggle');
    $('#webID').val(id);
    $('#should').val(amount);
    $('#should').prop('readonly', true);
    $('#should').attr('tabindex', '-1');
    $('#have').prop('readonly', true);
    $('#have').attr('tabindex', '-1');
    $('#infoText').val(text);
    $('#tax').val(tax).trigger('change');
    $('#tax').select2().attr('disabled', true);
    $('#tax').select2().attr('tabindex', '-1');
    $('#transferToWEB').hide();
}
$('#receiptDate').change(function(e) {
    if($("#receiptDate").val() == false && $('#transferToWEBc').is(':checked')){
        $('#addFinance').attr('disabled', true);
    } else {
        $('#addFinance').attr('disabled', false);
    }
});

$(document).ready(function(){
  var tab = $('.table').DataTable({
    order: [],
    ordering: false,
    language: {
      <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    },
    responsive: true,
    autoWidth: false
  });
  tab.page('last').draw( 'page' );
setTimeout(function(){ window.dispatchEvent(new Event('resize')); $('.table').trigger('column-reorder.dt'); }, 500);
});
</script>
<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; ?>
<script>
$(document).ready(function(){
    $('.accounts').select2({
        matcher: function(params, data){
            var defaultMatcher = $.fn.select2.defaults.defaults.matcher;
            if (data.text && $.isNumeric(params.term)) {
                if (params.term % 1000 == 0){
                    if ($.isNumeric(data.text.substr(0,5)) && data.text.match("^" + (params.term / 1000))) {
                        return data;
                    }
                } else if (params.term < 1000 && params.term % 100 == 0){
                    var dat = data.text.substr(0,4);
                    if ($.isNumeric(dat) && dat < 1000 && data.text.match("^" + (params.term / 100))) {
                        return data;
                    }
                    return null;
                } else {
                    if(data.text.match("^" + params.term)){
                        return data;
                    }
                    return null;
                }
            }
            return defaultMatcher(params, data);
        }
    });
});
</script>
