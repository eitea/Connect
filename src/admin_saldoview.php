<?php include 'header.php'; ?>
<?php enableToCore($userID); require 'Calculators/LogCalculator.php'; ?>

<div class="page-header">
<h3><?php echo $lang['USERS']; ?> Saldo</h3>
</div>

<table class="table table-hover">
  <thead>
    <th>Name</th>
    <th>Saldo</th>
    <th><?php echo $lang['DAYS'].' '.$lang['AVAILABLE'].': '. $lang['VACATION']; ?></th>
  </thead>
  <tbody>
    <?php
      $result = $conn->query("SELECT id, firstname, lastname FROM $userTable");
      while($result && ($row = $result->fetch_assoc())){
        $calc = new LogCalculator($row['id']);
        echo '<tr>';
        echo '<td>'. $row['firstname'].' '.$row['lastname'].'</td>';
        echo '<td>'. displayAsHoursMins($calc->saldo) .'</td>';
        echo '<td>'.sprintf('%+d', $calc->vacationDays).'</td>';
        echo '</tr>';
      }
     ?>
  </tbody>
</table>

<?php include 'footer.php'; ?>
