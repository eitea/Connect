<?php include 'header.php'; ?>
<?php enableToBookings($userID);?>
<style>
.robot-control{
  border:none;
  background:none;
  text-decoration: none;
  box-shadow:none;
}
</style>
<!-- BODY -->
<div class="page-header">
  <h3><?php echo $lang['TRAVEL_FORM']; ?></h3>
</div>
<?php
$currentMonth = substr(getCurrentTimestamp(),0,7);

$sql = "SELECT kmMoney FROM $userTable WHERE id = $userID";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

$kmMoney = $row['kmMoney'];

if($_SERVER['REQUEST_METHOD'] == 'POST'){
   if(!empty($_POST['captcha'])){
    die("Bot detected. Aborting all running Operations.");
  }

  if(isset($_POST['addDrive']) && !empty($_POST['addInfoText']) && !empty($_POST['addTimeStart']) && !empty($_POST['addTimeEnd'])){
    $timeStart = test_input($_POST['addDate'].' '.$_POST['addTimeStart']) .'.00';
    $timeEnd = test_input($_POST['addDate'].' '.$_POST['addTimeEnd']) .'.00';

    $kmStart = intval($_POST['addKmStart']);
    $kmEnd = intval($_POST['addKmEnd']);
    $countryID = intval($_POST['addCountry']);

    $infotext = test_input($_POST['addInfoText']);

    $expenses = floatval($_POST['addExpenses']);
    $hosting10 = floatval($_POST['addHosting10']);
    $hosting20 = floatval($_POST['addHosting20']);
    $hotel = floatval($_POST['addHotel']);

    if($kmStart <= $kmEnd){
      $sql = "INSERT INTO $travelTable (userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses)
      VALUES($userID, $countryID, '$timeStart', '$timeEnd', '$kmStart', '$kmEnd', '$infotext', '$hotel', '$hosting10', '$hosting20', '$expenses')";

      if(!$conn->query($sql)){
        echo mysqli_error($conn);
      }
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Falsche Eingabe: </strong>km-Stand Anfang muss kleiner sein als km-Stand Ende.';
      echo '</div>';
    }
  } else {
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Fehler: </strong>Orange Felder dürfen nicht leer oder null sein.';
    echo '</div>';
  }
}
?>

<div class="container-fluid">
  <div class="col-md-4">
    Name: <?php echo $_SESSION['firstname']; ?>
    <br><br>
    Monat: <?php echo $lang['MONTH_TOSTRING'][intval(substr($currentMonth,5,2))]; ?>
  </div>

  <div class="col-md-4">
    <strong>Kilometergeld: <?php echo $kmMoney; ?> <small> pro km </small></strong>
  </div>
</div>
<br><br>
<table class="table table-condensed table-hover h6">
  <thead style="border-width:medium; border-top-style:solid; border-color:#e8e8e8">
    <th>Reiseantritt</th>
    <th>Reiseende</th>
    <th style="text-center">km Anfang</th>
    <th style="text-center">km Ende</th>

    <th>Grund-Ort-Firma</th>

    <th style="background-color:#f1f3f4">Dauer</th>
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
    $sql = "SELECT * FROM $travelTable INNER JOIN $travelCountryTable ON $travelCountryTable.id = $travelTable.countryID WHERE travelDayStart LIKE '$currentMonth%' AND userID = $userID";
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

        <td style="background-color:#f2f4f5"><?php echo sprintf("%.2f", $timeDiff); ?></td>
        <td style="background-color:#f2f4f5"><?php if(!empty($row['identifier'])){echo $row['identifier'];} ?></td>
        <td style="background-color:#f2f4f5"><?php echo sprintf("%.2f", $dayPay); ?></td>
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
      <td><?php echo sprintf("%.2f", $durationSum); ?></td>
      <td></td>
      <td><?php echo sprintf("%.2f", $daySum);?></td>
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
<br>
<hr><hr>
<br>
<form method="POST">
  <div class="row">
    <div class="col-md-3">
      <input type="date" class="form-control required-field" onkeydown='if (event.keyCode == 13) return false;' name="addDate" value="<?php echo substr(getCurrentTimestamp(),0,10); ?>">
    </div>
    <div class="col-md-3">
      <div class="input-group input-daterange">
        <input type="time" class="form-control required-field" onkeydown='if (event.keyCode == 13) return false;' name="addTimeStart" >
        <span class="input-group-addon"> - </span>
        <input type="time" class="form-control required-field" onkeydown='if (event.keyCode == 13) return false;' name="addTimeEnd">
      </div>
    </div>
    <div class="col-md-5">
      <div class="input-group input-daterange">
        <input type="number" class="form-control" name="addKmStart" placeholder="km-Stand Anfang">
        <span class="input-group-addon"> - </span>
        <input type="number" class="form-control" name="addKmEnd" placeholder="km-Stand Ende">
      </div>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-md-2">
      <select class="js-example-basic-single btn-block" name="addCountry">
        <?php
        $result = mysqli_query($conn, "SELECT * FROM $travelCountryTable");
        while($result && ($row = $result->fetch_assoc())){
          echo "<option value=".$row['id'].">".$row['countryName']."</option>";
        }
        ?>
      </select>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-md-9">
      <textarea class="form-control required-field" rows="6" name="addInfoText" placeholder="Grund - Besuchte Orte - Firmen"></textarea><br>
    </div>
    <div class="col-md-2">
      <input type="number" step="any" class="form-control" name="addHotel" placeholder="Hotelkosten">
      <input type="number" step="any" class="form-control" name="addHosting10" placeholder="Bewirtung 10%">
      <input type="number" step="any" class="form-control" name="addHosting20" placeholder="Bewirtung 20%">
      <input type="number" step="any" class="form-control" name="addExpenses" placeholder="Spesen">
    </div>
  </div>
  <br><br>
  <div class="text-right">
    <button type="submit" class="btn btn-warning" name="addDrive"><?php echo $lang['ADD']; ?></button>
  </div>

  <input class="robot-control" type="text" name="captcha" value="" />
</form>
<!-- /BODY -->
<?php include 'footer.php'; ?>
