<?php include 'header.php'; ?>
<script src="../plugins/chartsjs/Chart.min.js"></script>
<!-- BODY -->

<div class="page-header">
<h3><?php echo $lang['PRODUCTIVITY']; ?></h3>
</div>

<?php
$filter_begin = $filter_end = carryOverAdder_Hours(getCurrentTimestamp(), -24);
if(isset($_POST['filterMonth_from'])){
  $filter_begin = $_POST['filterMonth_from']. '-01 05:00:00';
}
if(isset($_POST['filterMonth_to'])){
  $filter_end = $_POST['filterMonth_to']. '-01 05:00:00';
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
        <input id="calendar" type="text" class="form-control from" name="filterMonth_from" value=<?php echo substr($filter_begin,0,10); ?> >
        <span class="input-group-addon"> - </span>
        <input id="calendar2" type="text" class="form-control"  name="filterMonth_to" value="<?php echo substr($filter_end,0,10); ?>">
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
  <canvas id="analysisChart" width="1000" height="100"></canvas>
</div>

<?php if($filterIDs): //userID canBook
$full = $break = $productive = $nonproductive = $drive = 0;
$result_log = $conn->query("SELECT * FROM $logTable WHERE userID = $filterID AND status = '0' AND timeEnd != '0000-00-00 00:00:00' AND DATE('$filter_begin') <= DATE(time) AND Date(time) <= DATE('$filter_end')");
while($result_log && ($row_log = $result_log->fetch_assoc())){
  $full += timeDiff_Hours($row_log['time'], $row_log['timeEnd']);
  $result_proj = $conn->query("SELECT start, end, bookingType, status FROM $projectBookingTable LEFT JOIN $projectTable ON projectID = $projectTable.id
                                WHERE timestampID =".$row_log['indexIM']." AND start != '0000-00-00 00:00:00' AND end != '0000-00-00 00:00:00'");
  while($result_proj && ($row_proj = $result_proj->fetch_assoc())){
    if($row_proj['bookingType'] == 'project'){
      if(!empty($row_proj['status'])){
        $productive += timeDiff_Hours($row_proj['start'], $row_proj['end']);
      } else {
        $nonproductive += timeDiff_Hours($row_proj['start'], $row_proj['end']);
      }
    } elseif($row_proj['bookingType'] == 'drive') { //drive
      $drive += timeDiff_Hours($row_proj['start'], $row_proj['end']);
    } else {
      $break += timeDiff_Hours($row_proj['start'], $row_proj['end']);
    }
  }
}
//normalize numbers
echo 'Full: ' . $full;
echo '<br> Productive: '. $productive;
echo '<br> Not Productive: '. $nonproductive;
echo '<br> Breaks: ' . $break;
echo '<br> Drives: '. $drive;

if(($productive + $nonproductive + $break + $drive) > $full){
$full = $productive + $nonproductive + $break + $drive;
}

$break = floor(($break / $full) * 100);
$productive = $productive / $full * 100;
$drive = floor($drive / $full * 100);
$nonproductive = 100 - $productive - $break - $drive;

?>
<script>
$(function(){
var ctx = document.getElementById("analysisChart");
var myChart = new Chart(ctx, {
    type: 'horizontalBar',
    data: {
      labels: ["Person"],
      datasets: [{
        label: ["Produktiv"],
        data: [<?php echo $productive; ?>],
        backgroundColor: "#78cad9"
      }, {
        label: ["Nicht Produktiv"],
        data: [<?php echo $nonproductive; ?>],
        backgroundColor: "#af9acb"
      }, {
        label: ["Pausen"],
        data: [<?php echo $break; ?>],
        backgroundColor: "#acc46c"
      }, {
        label: ["Fahrzeiten"],
        data: [<?php echo $drive; ?>],
        backgroundColor: "#ffb73d"
      }]
    },
    options: {
      scales:{
        xAxes: [{
          stacked: true
        }],
        yAxes: [{
          stacked: true
        }]
      },
      tooltips: {
        callbacks: {
          label: function(tooltipItem, data) {
            return ' ' + data.datasets[tooltipItem.datasetIndex].label[0] +': ' + Math.round(data.datasets[tooltipItem.datasetIndex].data[0]*100)/100 + '%';
          }
        }
      },
      legend: {
        display: false
      }
    }
});

//---
});
</script>
<?php endif; ?>

<!-- /BODY -->
<?php include 'footer.php'; ?>
