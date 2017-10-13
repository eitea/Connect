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
    <th>Rechnungsdatum</th>
    <th><?php echo $lang['ACCOUNT']; ?></th>
    <th><?php echo $lang['OFFSET_ACCOUNT']; ?></th>
    <th>Text</th>
    <th><?php echo $lang['FINANCE_DEBIT']; ?> <small>(Brutto)</small></th>
    <th><?php echo $lang['FINANCE_CREDIT'];?> <small>(Brutto)</small></th>
    <th><?php echo $lang['VAT']; ?></th>
</tr></thead>
<tbody>
<?php
$result = $conn->query("SELECT account_journal.*, taxRates.description, percentage, code, firstname, lastname, a1.num AS accNum, a2.num AS offNum, a1.name AS accName, a2.name AS offName
FROM account_journal LEFT JOIN accounts a1 ON a1.id = account_journal.account
LEFT JOIN accounts a2 ON a2.id = account_journal.offAccount
LEFT JOIN UserData ON UserData.id = account_journal.userID INNER JOIN taxRates ON account_journal.taxID = taxRates.id
WHERE a1.companyID = $cmpID");
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
    echo '<td>'.$row['should'].'</td>';
    echo '<td>'.$row['have'].'</td>';
    echo '<td>'.$row['percentage'].'% '.$row['description'].'</td>';
    echo '</tr>';
    $csv .= $row['docNum'].';'.substr($row['payDate'], 0, 10).';'.$row['accNum'].';'.$row['offNum'].';'.$row['info'].';'.$row['percentage'].'%;'.$row['code']."\n";
}
?>
</tbody>
</table>

<form id="csvForm" method="POST" target="_blank" action="../project/csvDownload">
    <input type="hidden" name='csv' value="<?php echo rawurlencode($csv); ?>" />
</form>
<?php require 'footer.php'; ?>