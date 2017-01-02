<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToStamps($userID); ?>
<!-- BODY -->
<link rel="stylesheet" type="text/css" href="../plugins/dhtmlxCalendar/codebase/dhtmlxcalendar.css">
<script src="../plugins/dhtmlxCalendar/codebase/dhtmlxcalendar.js"> </script>

<div class="page-header">
  <h3><?php echo $lang['VACATION'] .' '. $lang['REQUESTS']?></h3>
</div>

<form method="post">
  <?php
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['makeRequest']) && !empty($_POST['start']) && !empty($_POST['end'])){
      if(test_Date($_POST['start'].' 08:00:00') && test_Date($_POST['end'].' 08:00:00')){
        $begin = test_input($_POST['start']);
        $end = test_input($_POST['end']);
        $infoText = test_input($_POST['requestText']);
        $sql = "INSERT INTO $userRequests (userID, fromDate, toDate, requestText) VALUES($userID, '$begin 04:00:00', '$end 04:00:00', '$infoText')";
        $conn->query($sql);
        echo mysqli_error($conn);
      } else {
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Failed! </strong>Invalid Dates.';
        echo '</div>';
      }
    } else {
      $sql = "SELECT * FROM $userRequests WHERE userID = $userID";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0){
        while($row = $result->fetch_assoc()){
          if(isset($_POST['del'.$row['id']])){
            $sql = "DELETE FROM $userRequests WHERE id =". $row['id'];
            $conn->query($sql);
            echo mysqli_error($conn);
          }
        }
      }
    }
  }
  ?>

  <br><br>


  <div class="row">
    <div class="col-xs-6">
      <div class="input-group input-daterange">
        <input id='calendar' type="date" class="form-control" value="" placeholder="Von" name="start">
        <span class="input-group-addon"> - </span>
        <input id='calendar2' type="date" class="form-control" value="" placeholder="Bis" name="end">
      </div>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-xs-6">
        <input type="text" class="form-control" placeholder="Info... (Optional)" name="requestText">
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-xs-6 text-right">
      <button class="btn btn-warning" type="submit" name="makeRequest"><?php echo $lang['REQUESTS']; ?></button>
    </div>
  </div>

  <script>
  var myCalendar = new dhtmlXCalendarObject(["calendar","calendar2"]);
  myCalendar.setSkin("material");
  myCalendar.setDateFormat("%Y-%m-%d");
  </script>

<?php
$sql = "SELECT * FROM $userRequests WHERE userID = $userID";
$result = $conn->query($sql);
if($result && $result->num_rows > 0): ?>

  <div class="container">
    <br><br>
    <div>
      <table  class="table table-hover">
        <tr>
          <th><?php echo $lang['FROM']; ?></th>
          <th><?php echo $lang['TO']; ?></th>
          <th>Status</th>
          <th><?php echo $lang['REPLY_TEXT']; ?> </th>
          <th class="text-center"><?php echo $lang['REQUESTS']. ' '. $lang['DELETE']; ?></th>
        </tr>
        <tbody>
          <?php
            while($row = $result->fetch_assoc()){
              $style = "";
              if($row['status'] == 0) {
                $style="";
              } elseif ($row['status'] == 1) {
                $style="#b52140";
              } elseif ($row['status'] == 2) {
                $style="#13b436";
              }
              echo "<tr>";
              echo '<td>' . substr($row['fromDate'],0,10) .'</td>';
              echo '<td>' . substr($row['toDate'],0,10) . '</td>';
              echo "<td style='color:$style'>" . $lang_vacationRequestStatus[$row['status']] .'</td>';
              echo "<td>" . $row['answerText'] . '</td>';
              echo '<td class="text-center"> <button type="submit" name="del'.$row['id'].'" class="btn btn-warning" data-toggle="tooltip" data-placement="top" title="Only deletes the Request!">
              <i class="fa fa-trash-o ></i>"</button> </td>';
              echo '</tr>';
            }
          ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

</form>
<!-- /BODY -->
<?php include 'footer.php'; ?>
