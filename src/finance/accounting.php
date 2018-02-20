<?php include dirname(__DIR__) . '/header.php'; ?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
enableToFinance($userID);

$filterings = array("savePage" => $this_page, 'date' => array(date('Y-m')));
$show_undo = false;

if (!empty($_GET['v'])) {
    $id = intval($_GET['v']);
} else {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Zugriff verweigert</div>';
    include dirname(__DIR__) . '/footer.php';
    die();
}
$result = $conn->query("SELECT * FROM accounts WHERE id = $id AND companyID IN (" . implode(', ', $available_companies) . ")");
if (!$result || $result->num_rows < 1) {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Zugriff verweigert</div>';
    include dirname(__DIR__) . '/footer.php';
    die();
} else {
    $account_row = $result->fetch_assoc();
}
if (isset($_POST['undo'])) {
    $res = $conn->query("SELECT id FROM account_journal WHERE offAccount = $id AND userID = $userID ORDER BY inDate DESC ");
    echo $conn->error;
    $val = $res->fetch_assoc()['id'];
    $conn->query("DELETE FROM account_journal WHERE id = $val");
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
    if ($_POST['add_should'] != $_POST['add_tax']) {
        $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Ungültiger Steuersatz.</strong> ' . $lang['ERROR_STRIKE'] . '</div>';
    }
}
$journalID = false;
if (isset($_POST['addFinance']) || isset($_POST['editJournalEntry'])) {
    $accept = true;
    //code below is highly sensitive. do not touch.
    if ($_POST['add_nr'] > 0 && test_Date($_POST['add_date'], "Y-m-d") && $_POST['add_account'] > 0 && ($_POST['add_should'] == 0 xor $_POST['add_have'] == 0)) {
        $addAccount = intval($_POST['add_account']);
        $offAccount = $id;
        $docNum = $_POST['add_nr'];
        $date = $_POST['add_date'];
        $text = test_input($_POST['add_text']);
        $should = $temp_should = floatval($_POST['add_should']);
        $have = $temp_have = floatval($_POST['add_have']);
        $tax = 1;
        if (isset($_POST['add_tax'])) {
            $tax = intval($_POST['add_tax']);
        }

        $res = $conn->query("SELECT * FROM accountingLocks WHERE YEAR(lockDate) = YEAR('$date') AND MONTH(lockDate) = MONTH('$date') AND companyID = " . $account_row['companyID']);
        if ($res && $res->num_rows > 0) {
            $accept = false;
            echo '<div class="alert alert-danger" class="close"><a href="#" data-dismiss="alert" class="close">&times;</a>Dieser Monat wurde gesperrt und darf nicht bebucht werden.</div>';
        }
        $res = $conn->query("SELECT num FROM accounts WHERE id = $addAccount");
        if ($res && ($rowP = $res->fetch_assoc())) {
            $accNum = $rowP['num'];
        } else {//else STRIKE
            $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Ungültiger Monat.</strong> ' . $lang['ERROR_STRIKE'] . '</div>';
        }


        if ($accNum >= 5000 && $accNum < 8000 && $have) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Konten Klasse 5, 6 und 7 dürfen nicht im Haben stehen.</div>';
            $accept = false;
        } elseif ($accNum >= 4000 && $accNum < 5000 && $should) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Konten Klasse 4 dürfen nicht im Soll stehen.</div>';
            $accept = false;
        } elseif (($accNum >= 8000 && $accNum < 10000) && ($accNum >= 1000 && $accNum < 3000)) {
            $tax = 1; //id1 = no tax;
        }
        if (!empty($_POST['webID'] || isset($_POST['transferToWEB'])) && ($accNum < 5000 || $accNum >= 6000)) { //STRIKE
            $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Ungültige Konto Klasse.</strong> Es dürfen nur Buchungen auf Konten der Klasse 5 ins WEB übertragen werden. ' . $lang['ERROR_STRIKE'] . '</div>';
            $accept = false;
        }
        if ($accept) {
            //journal
            $conn->query("INSERT INTO account_journal(docNum, userID, account, offAccount, payDate, inDate, taxID, should, have, info)
            VALUES ($docNum, $userID, $addAccount, $offAccount, '$date', UTC_TIMESTAMP, $tax, $should, $have, '$text')");
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
                $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Ungültiger Steuersatz.</strong> ' . $lang['ERROR_STRIKE'] . '</div>';
            }
            //STRIKE
            $taxRow = $res->fetch_assoc();

            //prepare balance
            $stmt = $conn->prepare("INSERT INTO account_balance (journalID, accountID, should, have) VALUES(?, ?, ?, ?)");
            echo $conn->error;
            $stmt->bind_param("iidd", $journalID, $account, $should, $have);

            $account2 = $account3 = '';
            if ($taxRow['account2']) {
                $res = $conn->query("SELECT id FROM accounts WHERE num = " . $taxRow['account2'] . " AND companyID IN (SELECT companyID FROM accounts WHERE id = $id) ");
                echo $conn->error;
                if ($res && $res->num_rows > 0) {
                    $account2 = $res->fetch_assoc()['id'];
                }
            }
            if ($taxRow['account3']) {
                $res = $conn->query("SELECT id FROM accounts WHERE num = " . $taxRow['account3'] . " AND companyID IN (SELECT companyID FROM accounts WHERE id = $id) ");
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
            $account = $offAccount;
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
            $account = $addAccount;
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

include dirname(__DIR__) . '/misc/new_supplier_buttonless.php';

$account_select = $tax_select = '';
$web_select = $supplier_select = '<option value="0">...</option><option value="new" >+ Neu</option>';
$result = $conn->query("SELECT id, num, name, companyID FROM accounts WHERE companyID IN (SELECT companyID FROM accounts WHERE id = $id) ");
while ($result && ($row = $result->fetch_assoc())) {
    $account_select .= '<option value="' . $row['id'] . '">' . $row['num'] . ' ' . $row['name'] . '</option>';
}
$result = $conn->query("SELECT * FROM taxRates");
while ($result && ($row = $result->fetch_assoc())) {
    $tax_select .= '<option value="' . $row['id'] . '">' . sprintf('%02d', $row['id']) . ' - ' . $row['percentage'] . '% ' . $row['description'] . '</option>';
}
$result = $conn->query("SELECT receiptBook.*, clientData.name, taxRates.percentage
FROM receiptBook INNER JOIN taxRates ON  taxRates.id = taxID INNER JOIN clientData ON clientData.id = supplierID WHERE companyID = " . $account_row['companyID'] . " AND (journalID IS NULL OR journalID = 0)");
while ($result && ($row = $result->fetch_assoc())) {
    $web_select .= '<option value="' . $row['id'] . '">' . substr($row['invoiceDate'], 0, 10) . ' - ' . $row['name'] . ' - ' . $row['info'] . ' (' . number_format(($row['amount']), 2, ',', '.') . '; ' . $row['percentage'] . '%)';
}
$result = $conn->query("SELECT id, name FROM $clientTable WHERE isSupplier = 'TRUE' AND companyID = " . $account_row['companyID']);
while ($result && ($row = $result->fetch_assoc())) {
    $supplier_select .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
}
?>

<div class="page-header"><h3><?php echo $lang['OFFSET_ACCOUNT'] . ' <small>' . $account_row['num'] . ' - ' . $account_row['name'] . '</small>'; ?>
        <div class="page-header-button-group"><?php include dirname(__DIR__) . '/misc/set_filter.php';
$cmpID = $account_row['companyID'];
include dirname(__DIR__) . '/misc/lockAccounting.php'; ?>
            <?php if ($show_undo): ?>
                <form method="POST" style="display:inline"><button type="submit" name="undo" class="btn btn-warning" >Undo</button></form>
            <?php endif; ?>
        </div></h3></div>
<br>
<table class="table table-hover">
    <thead><tr>
            <th>Nr.</th>
            <th><?php echo $lang['DATE']; ?></th>
            <th><?php echo $lang['ACCOUNT']; ?></th>
            <th>Text</th>
            <th>Steuer Nr.</th>
            <th style="text-align:right"><?php echo $lang['FINANCE_DEBIT']; ?></th>
            <th style="text-align:right"><?php echo $lang['FINANCE_CREDIT']; ?></th>
            <th style="text-align:right">Saldo</th>
            <th>WEB</th>
            <th></th>
        </tr></thead>
    <tbody>
        <?php
        $dateQuery = '';
        if ($filterings['date'][0]) {
            $dateQuery = "AND payDate LIKE '" . $filterings['date'][0] . "-%'";
        }

        $docDate = getCurrentTimestamp();
        $modals = '';
        $docNum = $saldo = 0;
        $result = $conn->query("SELECT account_journal.*, accounts.num, account_balance.have as netto_have, account_balance.should as netto_should, r1.id AS receiptID
        FROM account_balance INNER JOIN account_journal ON account_journal.id = account_balance.journalID
        INNER JOIN accounts ON account_journal.account = accounts.id
        LEFT JOIN receiptBook r1 ON r1.journalID = account_balance.journalID
        WHERE account_balance.accountID = $id $dateQuery ORDER BY docNum, payDate, inDate ");
        echo $conn->error;
        $lockedMonths = $conn->query("SELECT lockDate FROM accountingLocks WHERE companyID = $cmpID");
        $lockedMonths = array_column($lockedMonths->fetch_all(), 0);
        while ($result && ($row = $result->fetch_assoc())) {
            $docNum = $row['docNum'];
            $docDate = $row['payDate'];
            $saldo += $row['netto_should'];
            $saldo -= $row['netto_have'];
            echo '<tr>';
            echo '<td>' . $row['docNum'] . '</td>';
            echo '<td>' . substr($row['payDate'], 0, 10) . '</td>';
            echo '<td><a href="account?v=' . $row['account'] . '" class="btn btn-link" >' . $row['num'] . '</a></td>';
            echo '<td>' . $row['info'] . '</td>';
            echo '<td>' . sprintf('%02d', $row['taxID']) . '</td>';
            echo '<td style="text-align:right">' . number_format($row['netto_should'], 2, ',', '.') . '</td>';
            echo '<td style="text-align:right">' . number_format($row['netto_have'], 2, ',', '.') . '</td>';
            echo '<td style="text-align:right">' . number_format($saldo, 2, ',', '.') . '</td>';
            if ($row['receiptID']) {
                echo '<td>' . $lang['YES'] . '</td>';
            } else {
                echo '<td>' . $lang['NO'] . '</td>';
            }

            if ($account_row['manualBooking'] == 'TRUE' && !$row['receiptID'] && !in_array(substr($row['payDate'], 0, 8) . '01', $lockedMonths)) {
                echo '<td><button type="button" class="btn btn-default editing-modal-butt" title="Editieren" value="' . $row['id'] . '" ><i class="fa fa-pencil"></i></button></td>';
            } else {
                echo '<td></td>';
            }
            echo '</tr>';
        }
        if ($account_row['options'] == 'CONT') {
            $docNum++;
        }

        if (!$docNum) {
            $docNum = 1;
        }
        ?>
    </tbody>
</table>
<hr><br><br>
<?php if ($account_row['manualBooking'] == 'TRUE'): ?>
    <form method="POST" class="well">
        <div class="row form-group">
            <div id="openWebButton" class="col-sm-2"><button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#openWEB" title="Wareneingangsbuch"><?php echo $lang['FROM'] . ' WEB'; ?></button></div>
        </div>
        <div class="row">
            <div class="col-md-2"><label>Nr.</label><input type="number" class="form-control" step="1" min="1" name="add_nr" value="<?php echo $docNum; ?>" autofocus/></div>
            <div class="col-md-2"><label><?php echo $lang['DATE']; ?></label><input type="text" class="form-control datepicker" name="add_date" value="<?php echo substr($docDate, 0, 10); ?>" /></div>
            <div class="col-md-4"><label><?php echo $lang['ACCOUNT']; ?></label>
                <select id="account" class="js-example-basic-single" name="add_account" ><option>...</otpion><?php echo $account_select; ?></select>
                <small><a href="plan?n=<?php echo $account_row['companyID']; ?>" tabindex="-1" ><?php echo $lang['ACCOUNT_PLAN']; ?></a></small>
            </div>
            <div class="col-md-4"><label><?php echo $lang['VAT']; ?></label><select id="tax" class="js-example-basic-single" name="add_tax" ><?php echo $tax_select; ?></select></div>
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
<?php else: ?>
    <div class="alert alert-info">Gegen dieses Konto können keine Buchungen getätigt werden. Um gegen ein Konto buchen zu können, muss es unter den Einstellungen Ihres Mandanten als Gegenkonto markiert werden und ein Konto der Klasse 2 sein. </div>
<?php endif; ?>

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
                    $val = $account_row['companyID'];
                    $i = 1;
                    $res = $conn->query("SELECT receiptBook.*, clientData.name, taxRates.percentage
                FROM receiptBook INNER JOIN taxRates ON  taxRates.id = taxID INNER JOIN clientData ON clientData.id = supplierID WHERE companyID = $val AND (journalID IS NULL OR journalID = 0)");
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
    $("select[name=add_supplier]").change(function () {
        if ($(this).val() == 'new') {
            $('#create_client').modal().toggle();
        }
    });

    var existingModals = new Array();
    $('.editing-modal-butt').click(function () {
        var index = $(this).val();
        if (existingModals.indexOf(index) == -1) {
            $.ajax({
                url: 'ajaxQuery/AJAX_accountingModalEdit.php',
                data: {
                    id: index, acc: '<?php echo urlencode($account_select); ?>',
                    tax: '<?php echo urlencode($tax_select); ?>',
                    web: '<?php echo urlencode($web_select); ?>',
                    sups: '<?php echo urlencode($supplier_select); ?>'
                },
                type: 'post',
                success: function (resp) {
                    $("#editingModalDiv").append(resp);
                    existingModals.push(index);
                    onPageLoad();
                    $('.edit-journal-' + index).modal('show');
                },
                error: function (resp) {}
            });
        } else {
            $('.edit-journal-' + index).modal('show');
        }
    });

    var active_should = true;
    var active_have = true;
    function disenable(xor1, xor2, active) {
        if ($('#' + xor1).val()) {
            $('#' + xor2).val('');
            $('#' + xor2).prop('readonly', true);
            $('#' + xor2).attr('tabindex', '-1');
        } else if (active) {
            $('#' + xor2).prop('readonly', false);
        }
    }
    function showHide(show_id, hide_id) {
        $('#' + show_id).show();
        $('#' + hide_id).hide();
    }
    $('#should').keyup(function (e) {
        disenable('should', 'have', active_have);
    });
    $('#have').keyup(function (e) {
        disenable('have', 'should', active_should);
    });

    $('#account').change(function (e) {
        if ($('#webID').val())
            return;
        active_should = true;
        active_have = true;
        disenable('should', 'have', true);
        disenable('have', 'should', true);
        $('#tax').select2().attr('disabled', false);
        var account = $(this).select2('data')[0].text.split(' ')[0];
        if (account >= 5000 && account < 8000) {
            active_should = true;
            active_have = false;
            $('#have').val('');
            $('#have').prop('readonly', true);
            $('#have').attr('tabindex', '-1');
            $('#should').prop('readonly', false);
        } else if (account >= 4000 && account < 5000) {
            active_have = true;
            active_should = false;
            $('#should').val('');
            $('#should').prop('readonly', true);
            $('#should').attr('tabindex', '-1');
            $('#have').prop('readonly', false);
        } else if ((account >= 1000 && account < 4000) || account >= 8000 && account < 10000) {
            $('#tax').val(1).trigger('change');
            $('#tax').select2().attr('disabled', true);
            $('#tax').select2().attr('tabindex', '-1');
        }
        if (account >= 5000 && account < 6000) {
            $('#transferToWEB').show();
        } else {
            if ($('#transferToWEBc').is(':checked')) {
                $('#transferToWEBc').trigger('click');
            }
            $('#transferToWEB').hide();
        }
    });
    $('#transferToWEBc').click(function () {
        if (this.checked) {
            showHide('transferToWebInputs', 'openWebButton');
        } else {
            showHide('openWebButton', 'transferToWebInputs');
        }
        $('#receiptDate').trigger('change');
    });
    function webFillout(id, amount, text, tax) {
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

        $('#account').children().filter(function () { //remove all class 5
            var account = $(this).text().split(' ')[0];
            return (account < 5000 || account > 5999);
        }).remove();
    }
    $('#receiptDate').change(function (e) {
        if ($("#receiptDate").val() == false && $('#transferToWEBc').is(':checked')) {
            $('#addFinance').attr('disabled', true);
        } else {
            $('#addFinance').attr('disabled', false);
        }
    });

    $(document).ready(function () {
        var tab = $('.table').DataTable({
            order: [],
            ordering: false,
            language: {
<?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
            },
            responsive: true,
            autoWidth: false
        });
        tab.page('last').draw('page');
        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
            $('.table').trigger('column-reorder.dt');
        }, 500);
    });
</script>
<?php include dirname(__DIR__) . '/footer.php'; ?>

<script>
    $(document).ready(function () {
        $('#account').select2({
            matcher: function (params, data) {
                var defaultMatcher = $.fn.select2.defaults.defaults.matcher;
                if (data.text && $.isNumeric(params.term)) {
                    if (params.term % 1000 == 0) {
                        if ($.isNumeric(data.text.substr(0, 5)) && data.text.match("^" + (params.term / 1000))) {
                            return data;
                        }
                    } else if (params.term < 1000 && params.term % 100 == 0) {
                        var dat = data.text.substr(0, 4);
                        if ($.isNumeric(dat) && dat < 1000 && data.text.match("^" + (params.term / 100))) {
                            return data;
                        }
                        return null;
                    } else {
                        if (data.text.match("^" + params.term)) {
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
