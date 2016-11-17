<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToStamps($userID); ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['VACATION'] .' '. $lang['REQUESTS']?></h3>
</div>

<form method="post">
  <?php
  if(isset($_POST['makeRequest']) && !empty($_POST['start']) && !empty($_POST['end'])){
    if(test_Date($_POST['start'].' 08:00:00') && test_Date($_POST['end'].' 08:00:00')){
      $sql = "INSERT INTO $userRequests (userID, fromDate, toDate, requestText) VALUES($userID, '".$_POST['start'].' 04:00:00'."', '" .$_POST['end'].' 04:00:00' . "', '".$_POST['requestText']."')";
      $conn->query($sql);
      echo mysqli_error($conn);
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Failed! </strong>Invalid Dates.';
      echo '</div>';
    }
  }
  ?>

  <br><br>


  <div class="row">
    <div class="col-xs-6">
      <div class="input-group input-daterange">
        <span class="input-group-btn">
          <button class="btn btn-warning" type="submit" name="makeRequest"><?php echo $lang['REQUESTS']; ?></button>
        </span>
        <input id='calendar' type="text" class="form-control" value="" placeholder="Von" name="start">
        <span class="input-group-addon"> - </span>
        <input id='calendar2' type="text" class="form-control" value="" placeholder="Bis" name="end">
      </div>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-xs-6">
        <input type="text" class="form-control" placeholder="Info... (Optional)" name="requestText">
    </div>
  </div>

  <script>
  $('.input-daterange input').each(function() {
    $(this).datepicker({
      format: "yyyy-mm-dd",
      viewMode: "days"
    });
  });
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
                $style="style=danger";
              } elseif ($row['status'] == 2) {
                $style="success";
              }
              echo "<tr class=$style>";
              echo '<td>' . substr($row['fromDate'],0,10) .'</td>';
              echo '<td>' . substr($row['toDate'],0,10) . '</td>';
              echo '<td>' . $lang_vacationRequestStatus[$row['status']] .'</td>';
              echo '<td>' . $row['answerText'] . '</td>';
              echo '<td  class="text-center"> <button type="button" class="btn btn-warning" data-toggle="tooltip" data-placement="top" title="Only deletes the Request!"> x </button> </td>';
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
