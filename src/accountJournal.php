<?php require 'header.php'; ?>

<div class="page-header"><h3><?php echo $lang['ACCOUNT_JOURNAL']; ?>
<div class="page-header-button-group"><button type="submit" form="csvForm" class="btn btn-default" title="CSV Download"><i class="fa fa-download"></i></button></div>
</h3></div>

<?php
if(isset($_GET['n']) && in_array($_GET['n'], $available_companies)){
    $cmpID = $_GET['n'];
} else {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Zugriff verweigert.</div>';
    include 'footer.php';
    die();
}
?>

<table class="table table-hover">
<thead><tr>
    <th>Nr.</th>
    <th>Benutzer</th>
    <th>Buchungsdatum</th>
    <th><?php echo $lang['DATE']; ?></th>
    <th><?php echo $lang['ACCOUNT']; ?></th>
    <th><?php echo $lang['OFFSET_ACCOUNT']; ?></th>
    <th>Text</th>
    <th style="text-align:right"><?php echo $lang['FINANCE_DEBIT']; ?> <small>(Brutto)</small></th>
    <th style="text-align:right"><?php echo $lang['FINANCE_CREDIT'];?> <small>(Brutto)</small></th>
    <th><?php echo $lang['VAT']; ?></th>
    <th style="text-align:right"><?php echo $lang['TAXES']; ?></th>
</tr></thead>
<tbody>
<?php
$result = $conn->query("SELECT account_journal.*, taxRates.description, percentage, code, firstname, lastname, a1.num AS accNum, a2.num AS offNum, a1.name AS accName, a2.name AS offName
FROM account_journal LEFT JOIN accounts a1 ON a1.id = account_journal.account
LEFT JOIN accounts a2 ON a2.id = account_journal.offAccount
LEFT JOIN UserData ON UserData.id = account_journal.userID INNER JOIN taxRates ON account_journal.taxID = taxRates.id
WHERE a1.companyID = $cmpID ORDER BY docNum, inDate");
echo $conn->error;
$csv = "Nr.;Datum;Konto;Gegenkonto;Text;Steuer;Steuercode\n";
while($result && ($row = $result->fetch_assoc())){
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
    if($row['should'] != 0) $t = $row['should'] * $row['percentage']/100;
    if($row['have'] != 0) $t = $row['have'] * $row['percentage']/100;
    echo '<td style="text-align:right">'.number_format($t,2,',','.').'</td>';
    echo '</tr>';
    $csv .= $row['docNum'].';'.substr($row['payDate'], 0, 10).';'.$row['accNum'].';'.$row['offNum'].';'.iconv('UTF-8','windows-1252',$row['info']).';'.$row['percentage'].'%;'.$row['code']."\n";
}
?>
</tbody>
</table>

<form id="csvForm" method="POST" target="_blank" action="../project/csvDownload">
    <input type="hidden" name='csv' value="<?php echo rawurlencode($csv); ?>" />
</form>

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