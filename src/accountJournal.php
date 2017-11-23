<?php
require 'header.php';
if(isset($_GET['n']) && in_array($_GET['n'], $available_companies)){
    $cmpID = $_GET['n'];
} else {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Zugriff verweigert.</div>';
    include 'footer.php';
    die();
}

$filterings = array('user' => 0, "date" => array(substr(getCurrentTimestamp(), 0, 8).'01', date('Y-m-t', strtotime(getCurrentTimestamp()))) );
?>

<div class="page-header"><h3><?php echo $lang['ACCOUNT_JOURNAL']; ?><div class="page-header-button-group">
    <?php include __DIR__.'/misc/set_filter.php'; ?>
    <button type="submit" form="csvForm" class="btn btn-default" title="CSV Download"><i class="fa fa-download"></i> CSV</button>
    <?php include __DIR__.'/misc/lockAccounting.php'; ?>
</div></h3></div>

<table class="table table-hover">
<thead><tr>
    <th>Nr.</th>
    <th>Benutzer</th>
    <th>Buchungsdatum</th>
    <th style="min-width:55px"><?php echo $lang['DATE']; ?></th>
    <th><?php echo $lang['ACCOUNT']; ?></th>
    <th><?php echo $lang['OFFSET_ACCOUNT']; ?></th>
    <th>Text</th>
    <th style="text-align:right"><?php echo $lang['FINANCE_DEBIT']; ?> <small>(Netto)</small></th>
    <th style="text-align:right"><?php echo $lang['FINANCE_CREDIT'];?> <small>(Netto)</small></th>
    <th><?php echo $lang['VAT']; ?></th>
    <th style="text-align:right"><?php echo $lang['TAXES']; ?></th>
    <th>WEB</th>
</tr></thead>
<tbody>
<?php
$userQuery = $dateQuery = '';
if($filterings['user']) $userQuery = "AND UserData.id = ".$filterings['user'];
if($filterings['date']) $dateQuery = "AND DATE(payDate) >= DATE('".$filterings['date'][0]."') AND DATE(payDate) <=  DATE('".$filterings['date'][1]."') ";
$result = $conn->query("SELECT account_journal.*, taxRates.description, percentage, code, firstname, lastname, 
a1.num AS accNum, a2.num AS offNum, a1.name AS accName, a2.name AS offName, r1.id AS receiptID, taxRates.account2, taxRates.account3
FROM account_journal INNER JOIN accounts a1 ON a1.id = account_journal.account
INNER JOIN accounts a2 ON a2.id = account_journal.offAccount
LEFT JOIN UserData ON UserData.id = account_journal.userID
INNER JOIN taxRates ON account_journal.taxID = taxRates.id
LEFT JOIN receiptBook r1 ON r1.journalID = account_journal.id
WHERE a1.companyID = $cmpID $userQuery $dateQuery ORDER BY docNum, inDate");
echo $conn->error;
$csv = "belegnr;konto;gkto;belegdat;text;buchdat;bucod;betrag;steuer;steucod;mwst;symbol;periode;gegenbuchkz;verbuchkz\n";
while($result && ($row = $result->fetch_assoc())){
    if($row['account2'] && $row['account3']){
        if($row['should'] != 0) $t = $row['should'] * $row['percentage'] / 100;
        if($row['have'] != 0) $t = $row['have'] * $row['percentage'] / 100;
    } else {
        if($row['should'] != 0) {$t = $row['should'] - (100 * $row['should']) / (100 + $row['percentage']); $row['should'] -= $t; }
        if($row['have'] != 0) {$t = $row['have'] - (100 * $row['have']) / (100 + $row['percentage']); $row['have'] -= $t; }
    }
    echo '<tr>';
    echo '<td>'.$row['docNum'].'</td>';
    echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
    echo '<td>'.substr($row['inDate'], 0, 16).'</td>';
    echo '<td>'.substr($row['payDate'], 0, 10).'</td>';
    echo '<td><a title="Zum Konto" href="account?v='.$row['account'].'" >'.$row['accNum'].' '.$row['accName'].'</a></td>';
    echo '<td><a title="Zum Konto" href="account?v='.$row['offAccount'].'" >'.$row['offNum'].' '.$row['offName'].'</a></td>';
    echo '<td>'.$row['info'].'</td>';
    echo '<td style="text-align:right">'.number_format($row['should'],2,',','.').'</td>';
    echo '<td style="text-align:right">'.number_format($row['have'],2,',','.').'</td>';
    echo '<td>'.$row['percentage'].'% '.$row['description'].'</td>';

    echo '<td style="text-align:right">'.number_format($t,2,',','.').'</td>';
    if($row['receiptID']){ echo '<td>'.$lang['YES'].'</td>'; } else { echo '<td>'.$lang['NO'].'</td>'; }
    echo '</tr>';

    //### CSV ####
    if($row['should'] != 0) { $val = $row['should']; $code = 1; } else { $val = $row['have'] * -1; $code = 2; $t *= -1; }
    if($row['offNum'] >= 2800){ $sym = 'bk'; } else { $sym = 'ka'; }
    if($row['code'] == 19 || $row['code'] == 9){ $t *= -1; }
    //belegnr;     konto;    gkto;     belegdat;     text;    buchdat;     bucod;     betrag;     steuer;   steucod;
    $csv .= $row['docNum'].';'.$row['accNum'].';'.$row['offNum'].';'.date('Ymd', strtotime($row['payDate'])).';'.iconv('UTF-8','windows-1252',$row['info']).';'
    .date('Ymd', strtotime($row['payDate'])).';'.$code.';'.number_format($val, 2, ',','.').';'.number_format($t, 2, ',','.').';'.$row['code'].';'.$row['percentage']
    .";$sym;". date('n', strtotime($row['payDate'])).";E;A\n";
}
?>
</tbody>
</table>

<form id="csvForm" method="POST" target="_blank" action="../project/csvDownload?name=BMD_Export"><input type="hidden" name='csv' value="<?php echo rawurlencode($csv); ?>" /></form>

<script>
$('.table').DataTable({
    order: [[ 0, "asc" ]],
    deferRender: true,
    responsive: true,
    colReorder: true,
    autoWidth: false,
    language: {
        <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    },
    fixedHeader: {
      header: true,
      headerOffset: 50,
      zTop: 1
    }
});

setTimeout(function(){ window.dispatchEvent(new Event('resize')); $('.table').trigger('column-reorder.dt'); }, 500);
</script>
<?php require 'footer.php'; ?>