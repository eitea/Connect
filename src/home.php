<?php include 'header.php'; ?>
<script src="../plugins/chartsjs/Chart.min.js"></script>
<!-- BODY -->


<div style="width:600px; padding:50px 0px 50px 0px">
  <canvas id="analysisChart" ></canvas>
</div>

<?php //style="border:1px solid #000000"
$curID = $userID;
include 'tableSummary.php'; //this is how it goes
?>

<script>
var ctx_analysis = document.getElementById("analysisChart");
var myRadarChart = new Chart(ctx_analysis, {
  type: 'horizontalBar',
  options: {
    legend:{
      display: false
    }
  },
  data: {
    labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        datasets: [
            {
                label: "Durschnittliche Stunden",
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)'
                ],
                borderColor: [
                    'rgba(255,99,132,1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1,
                data: [65, 59, 80, 81, 56, 55, 0,0]
            }
        ]
  }
});
</script>

  <!-- /BODY -->
  <?php include 'footer.php'; ?>
