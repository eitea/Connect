<?php include 'header.php'; ?>
<?php enableToCore($userID); require 'Calculators/IntervalCalculator.php'; ?>

<div class="page-header">
<h3><?php echo $lang['USERS']; ?> Saldo</h3>
</div>

<script>
  document.getElementById("loader").style.display = "none";
  document.getElementById("bodyContent").style.display = "block";
</script>

<table class="table table-hover">
  <thead>
    <th>Name</th>
    <th>Saldo</th>
    <th><?php echo $lang['VACATION_DAYS'].$lang['PER_YEAR']; ?></th>
  </thead>
  <tbody>
    <?php
      $result = $conn->query("SELECT id, firstname, lastname FROM $userTable");
      while($result && ($row = $result->fetch_assoc())){
        $calc = new Interval_Calculator($row['id']);
        echo '<tr>';
        echo '<td>'. $row['firstname'].' '.$row['lastname'].'</td>';
        echo '<td>'. displayAsHoursMins($calc->saldo) .'</td>';
        echo '<td>'.sprintf('%+d', $calc->availableVacation).'</td>';
        echo '</tr>';
      }
     ?>
  </tbody>
</table>

<?php include 'footer.php'; ?>
