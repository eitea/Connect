<?php include 'header.php'; ?>
<link rel="stylesheet" type="text/css" href="../plugins/datepicker/css/datepicker.css">
<script src="../plugins/datepicker/js/bootstrap-datepicker.js"> </script>
<script src="../plugins/chartsjs/Chart.min.js"></script>
<!-- BODY -->

<div class="page-header">
<h3><?php echo $lang['PRODUCTIVITY']; ?></h3>
</div>

<?php
$currentTimeStamp = carryOverAdder_Hours(getCurrentTimestamp(), -24);
if(isset($_POST['newMonth'])){
  $currentTimeStamp = $_POST['newMonth']. '-01 05:00:00';
}

$filterIDs = $filterID = 0;
if(!empty($_POST['filterUserID'])){
  $filterID = $filterIDs = intval($_POST['filterUserID']);
}
?>

<form method="post" id="FILTER_FORM">
  <div class="container-fluid form-group">
    <div class="col-xs-6">
      <div class="input-group">
        <input id="calendar" type="text" class="form-control from" name="filterMonth_from" value=<?php echo substr($currentTimeStamp,0,10); ?> >
        <span class="input-group-addon"> - </span>
        <input id="calendar2" type="text" class="form-control"  name="filterMonth_to" value="<?php echo substr($currentTimeStamp,0,10); ?>">
      </div>
    </div>
    <div class="col-sm-3">
      <select name='filterUserID' style="width:200px" class="js-example-basic-single btn-block">
        <?php
        $result = mysqli_query($conn, "SELECT $userTable.* FROM $userTable, $roleTable WHERE userID = id AND canBook = 'TRUE';");
        echo "<option name='filterUserID' value='0'>Benutzer ... </option>";
        while($row = $result->fetch_assoc()){
          $i = $row['id'];
          if ($filterID == $i) {
            echo "<option name='filterUserID' value='$i' selected>".$row['firstname'] . " " . $row['lastname']."</option>";
          } else {
            echo "<option name='filterUserID' value='$i' >".$row['firstname'] . " " . $row['lastname']."</option>";
          }
        }
        ?>
      </select>
    </div>
    <div class="col-sm-3">
      <button class="btn btn-warning" type="submit" name="filterBtn" value="<?php echo $filterIDs; ?>">Filter</button>
    </div>
  </div>
</form>
<script>
$("#calendar").datepicker({
  format: "yyyy-mm-dd",
  viewMode: "days",
  minViewMode: "days"
});
$("#calendar2").datepicker({
  format: "yyyy-mm-dd",
  viewMode: "days",
  minViewMode: "days"
});
</script>
<br><br>

<div class="container-fluid">
  <canvas id="analysisChart" width="200" height="100" style="max-width:600px; max-height:300px;"></canvas>
</div>

<?php if($filterIDs):
$result = $conn->query("SELECT * FROM $logTable WHERE userID = $filterID && timeEnd != '0000-00-00 00:00:00'");

?>
<script>
$(function(){
  var ctx_analysis = document.getElementById("analysisChart");
  var myAnalysisChart = new Chart(ctx_analysis, {
    type: 'horizontalBar',
    options: {
      scales:{
        xAxes: [{
          stacked: true
        }]
      }
      legend:{
        display: false
      },
      title:{
        display:true,
        text: 'Durchschnittliche Stunden'
      }
    },
    data: {
      labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
      datasets: [{
        label: "Mittel",
        backgroundColor: [
          'rgba(255, 99, 169, 0.5)',
          'rgba(90, 163, 231, 0.5)',
          'rgba(189, 209, 71, 0.5)',
          'rgba(75, 192, 192, 0.5)',
          'rgba(154, 125, 210, 0.5)'
        ],
        data: [<?php echo $mean_mon.', '.$mean_tue.', '.$mean_wed.', '.$mean_thu.', '.$mean_fri.', '.$mean_sat.', '.$mean_sun; ?>]
      }
    ]}
  });
});
</script>
<?php endif; ?>

<!-- /BODY -->
<?php include 'footer.php'; ?>
