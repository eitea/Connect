<?php include 'header.php'; ?>
<?php enableToCore($userID);?>

<div class="page-header"><h3>Riesekostenabrechnung</h3></div>

<?php
$filterUserID = 0;
if(isset($_POST['filterUserID'])){
  $filterUserID = $_POST['filterUserID'];
}

$filterMonth = substr(getCurrentTimestamp(),0,7);
if(isset($_POST['filterYear'])){
  $filterMonth = substr_replace($filterMonth, $_POST['filterYear'],0,4);
}
if(isset($_POST['filterMonth'])){
  $filterMonth = substr_replace($filterMonth, $_POST['filterMonth'],5,2);
}

$sql = "SELECT kmMoney FROM $userTable WHERE id = $userID";
$result = $conn->query($sql);
if($result && ($row = $result->fetch_assoc())){
  $kmMoney = $row['kmMoney'];
} else {
  $kmMoney = 0;
}
?>

<form method = "post">
  <div class="row">
    <div class="col-md-6">
      <select style='width:200px' class="js-example-basic-single" name="filterYear">
        <?php
        for($i = substr($filterMonth,0,4)-5; $i < substr($filterMonth,0,4)+5; $i++){
          $selected = ($i == substr($filterMonth,0,4))?'selected':'';
          echo "<option $selected value=$i>$i</option>";
        }
        ?>
      </select>
      <select style="width:150px" class="js-example-basic-single" name="filterMonth">
        <?php
        for($i = 1; $i < 13; $i++) {
          $selected= '';
          if ($i == substr($filterMonth,5,2)) {
            $selected = 'selected';
          }
          $option = $lang['MONTH_TOSTRING'][$i];
          echo "<option $selected value=".sprintf("%02d",$i).">$option</option>";
        }
        ?>
      </select>
      <select name="filterUserID" class="js-example-basic-single">
        <option value=0>Benutzer... </option>
        <?php
        $result = $conn->query("SELECT firstname, lastname,id FROM $userTable WHERE id IN (".implode(', ', $available_users).")");
        while($result && ($row = $result->fetch_assoc())){
          $selected = '';
          if ($row['id'] == $filterUserID) {
            $selected = 'selected';
          }
          echo "<option $selected value='". $row['id']. "'>" . $row['firstname'] . ' ' . $row['lastname'] . '</option>';
        }
        ?>
      </select>
    </div>
    <button type="submit" name="filter" class="btn btn-warning btn-sm">Filter</button>
  </div>

  <br><br><br>

<?php if($filterUserID != 0): ?>
  <table class="table table-condensed table-hover h6">
    <thead style="border-width:medium; border-top-style:solid; border-color:#e8e8e8">
      <th>Reiseantritt</th>
      <th>Reiseende</th>
      <th style="text-center">km-Stand Anfang</th>
      <th style="text-center">km-Stand Ende</th>

      <th>Grund-Ort-Firma</th>

      <th style="background-color:#f1f3f4">Reisedauer</th>
      <th style="background-color:#f1f3f4">Land</th>
      <th style="background-color:#f1f3f4">Taggeld</th>
      <th style="background-color:#f1f3f4">km</th>
      <th style="background-color:#f1f3f4">kmGeld</th>

      <th>Hotel</th>
      <th style="text-center">Bewirtung 10%</th>
      <th style="text-center">Bewirtung 20%</th>
      <th>Spesen</th>

      <th style="background-color:#f1f3f4">Total</th>
    </thead>
    <tbody>
      <?php
      $sql = "SELECT * FROM $travelTable INNER JOIN $travelCountryTable ON $travelCountryTable.id = $travelTable.countryID WHERE travelDayStart LIKE '$filterMonth-%' AND userID = $filterUserID";
      $result = $conn->query($sql);
      $durationSum = $daySum = $kmSum = $kmPaySum = $hotelSum = $hosting10Sum = $hosting20Sum = $expensesSum = $totalSum = 0;
      while($result && ($row = $result->fetch_assoc())):
        $countryMun = $row['dayPay'];
        $timeDiff = timeDiff_Hours($row['travelDayStart'], $row['travelDayEnd']);
        if($timeDiff <= 3){
          $dayPay = 0;
        } elseif($timeDiff <= 12){
          $dayPay = $timeDiff * $countryMun/12;
        } else {
          $dayPay = $countryMun;
        }
        $drovenKM = $row['kmEnd'] - $row['kmStart'];
        $drovenKMPay = $drovenKM * $kmMoney;
        $total = ($drovenKM * $kmMoney) + $row['hotelCosts'] + $row['hosting10'] + $row['hosting20'] + $row['expenses'];


        $durationSum += $timeDiff;
        $daySum += $dayPay;
        $kmSum += $drovenKM;
        $kmPaySum += $drovenKMPay;
        $hotelSum +=$row['hotelCosts'];
        $hosting10Sum += $row['hosting10'];
        $hosting20Sum += $row['hosting20'];
        $expensesSum += $row['expenses'];
        $totalSum += $total;
        ?>
        <tr>
          <td><?php echo substr($row['travelDayStart'],0,-3); ?></td>
          <td><?php echo substr($row['travelDayEnd'],0,-3); ?></td>
          <td><?php echo $row['kmStart']; ?></td>
          <td><?php echo $row['kmEnd']; ?></td>

          <td><?php echo $row['infoText']; ?></td>

          <td style="background-color:#f2f4f5"><?php echo $timeDiff; ?></td>
          <td style="background-color:#f2f4f5"><?php if(!empty($row['identifier'])){echo $row['identifier'];} ?></td>
          <td style="background-color:#f2f4f5"><?php echo $dayPay; ?></td>
          <td style="background-color:#f2f4f5"><?php echo $drovenKM; ?></td>
          <td style="background-color:#f2f4f5"><?php echo $drovenKMPay; ?></td>

          <td><?php echo $row['hotelCosts']; ?></td>
          <td><?php echo $row['hosting10']; ?></td>
          <td><?php echo $row['hosting20']; ?></td>
          <td><?php echo $row['expenses']; ?></td>

          <td style="background-color:#f2f4f5"><?php echo $total; ?></td>
        </tr>
      <?php endwhile; ?>
      <tr style="font-weight:bold; text-align:right;">
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Summen:</td>
        <td><?php echo $durationSum;?></td>
        <td><?php echo $daySum;?></td>
        <td></td>
        <td><?php echo $kmSum;?></td>
        <td><?php echo $kmPaySum;?></td>
        <td><?php echo $hotelSum;?></td>
        <td><?php echo $hosting10Sum;?></td>
        <td><?php echo $hosting20Sum;?></td>
        <td><?php echo $expensesSum;?></td>
        <td><?php echo $totalSum;?></td>
      </tr>
    </tbody>
  </table>
<?php endif; ?>

</form>

<?php include 'footer.php'; ?>
