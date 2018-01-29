<?php include dirname(__DIR__) . '/header.php'; enableToERP($userID); ?>
  <div class="page-header">
    <h3><?php echo $lang['TAX_RATES']; ?></h3>
  </div>
  <table class="table table-striped">
    <thead>
      <th>Nr.</th>
      <th>Name</th>
      <th>Prozentsatz</th>
      <th>Steuerkonto Klasse 2</th>
      <th>Steuerkonto Klasse 3</th>
      <th>Steuercode</th>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT * FROM taxRates");
      while ($result && ($row = $result->fetch_assoc())) {
        echo '<tr>';
        echo '<td>'.$row['id'].'</td>';
        echo '<td>'.$row['description'].'</td>';
        echo '<td style="text-align:right">'.intval($row['percentage']).'%</td>';
        echo '<td style="text-align:center">'.$row['account2'].'</td>';
        echo '<td style="text-align:center">'.$row['account3'].'</td>';
        echo '<td style="text-align:center">'.$row['code'].'</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
<?php include dirname(__DIR__) . '/footer.php'; ?>
