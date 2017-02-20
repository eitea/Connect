
<?php include 'header.php'; enableToTime($userID); ?>
<div class="page-header">
  <h3><?php echo $lang['ADJUSTMENTS']; ?></h3>
</div>

<?php
$filterID = 0;
if(isset($_POST['filterUserID'])){
  $filterID = $_POST['filterUserID'];
}

if(isset($_POST['creatSign']) && !empty($_POST['creatInfoText']) && !empty($_POST['creatFromTime'])){
  $hours = floatval($_POST['creatFromTime']);
  if(isset($_POST['creatTimeTime']) && test_Date($_POST['creatTimeTime']. ":00")){
    $date = "'".$_POST['creatTimeTime']. ":00'";
  } else {
    $date = 'UTC_TIMESTAMP';
  }
  if($hours > 0){
    $addOrSub = intval($_POST['creatSign']);
    $infoText = test_input($_POST['creatInfoText']);
    $conn->query("INSERT INTO $correctionTable (userID, hours, infoText, addOrSub, cOnDate) VALUES($filterID, $hours, '$infoText', '$addOrSub', $date)");
  } else {
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Please do not enter hours less or equal to 0.';
    echo '</div>';
  }
} elseif(isset($_POST['delete']) && !empty($_POST['checkedIDs'])){
  foreach($_POST['checkedIDs'] as $id){
    $conn->query("DELETE FROM $correctionTable WHERE id = $id");
  }
}
echo mysqli_error($conn);
?>

<form method="POST">
  <div class="col-xs-4">
    <select name="filterUserID" class="js-example-basic-single btn-block">
      <option value='0'><?php echo $lang['USERS']; ?>...</option>
      <?php
      $result = mysqli_query($conn, "SELECT id, firstname, lastname FROM $userTable;");
      while($row = $result->fetch_assoc()){
        $i = $row['id'];
        if ($filterID == $i) {
          echo "<option value='$i' selected>".$row['firstname'] . " " . $row['lastname']."</option>";
        } else {
          echo "<option value='$i'>".$row['firstname'] . " " . $row['lastname']."</option>";
        }
      }
      ?>
    </select>
  </div>
  <div class="col-xs-8">
    <button type="submit" class="btn btn-warning btn-sm" name="applyFilter">Filter</button>
  </div>
  <br><br><br>

  <?php if($filterID != 0): ?>
    <div class="col-xs-5">
      <input type="text" class="form-control" placeholder="Infotext" name="creatInfoText" />
    </div>
    <div class="col-xs-3">
      <input id="calendar" type="text" class="form-control" name="creatTimeTime" value='<?php echo substr(getCurrentTimestamp(),0,16); ?>' />
    </div>
    <div class="col-xs-2">
      <input type="number" step="any" class="form-control" placeholder="Hours" size='2' name="creatFromTime" />
    </div>
    <div class="col-xs-2">
      <div class="dropdown">
        <a href="#" class="btn btn-warning dropdown-toggle" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
         ( + / - )
        </a>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
          <li><button type="submit" class="btn-link" style="white-space: nowrap;" name="creatSign" value="1"><?php echo $lang['ABSOLVED_HOURS']; ?> (+)</button></li>
          <li><button type="submit" class="btn-link" style="white-space: nowrap;" name="creatSign" value="-1"><?php echo $lang['EXPECTED_HOURS']; ?> (-)</button></li>
        </ul>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-info" role="alert">Select a user to add adjustments.</div>
  <?php endif; ?>

  <h1 style="color:white">Tuck Frump</h1>
  <table class="table table-hover">
    <thead>
      <th><?php echo $lang['DELETE']; ?></th>
      <th>Name</th>
      <th><?php echo $lang['CORRECTION'] .' '. $lang['DATE']; ?> (UTC)</th>
      <th><?php echo $lang['ADJUSTMENTS'].' '. $lang['HOURS']; ?></th>
      <th>Info</th>
    </thead>
    <tbody>
      <?php
      if($filterID != 0){
        $userID_query = " AND userID = $filterID";
      } else {
        $userID_query = "";
      }
      $result = $conn->query("SELECT $correctionTable.*, $userTable.firstname FROM $correctionTable, $userTable WHERE $userTable.id = $correctionTable.userID $userID_query");
      echo mysqli_error($conn);
      while($result && ($row = $result->fetch_assoc())){
        $hours = $row['hours'] * $row['addOrSub'];
        echo '<tr>';
        echo '<td><input type="checkbox" value="'.$row['id'].'" name="checkedIDs[]" /></td>';
        echo '<td>'.$row['firstname'].'</td>';
        echo '<td>'.substr($row['cOnDate'],0,16).'</td>';
        echo '<td>'.sprintf("%+.2f",$hours).'</td>';
        echo '<td>'.$row['infoText'].'</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <br><br>
  <div class="text-right">
    <button type="submit" class="btn btn-warning" name="delete"><?php echo $lang['DELETE']; ?></button>
  </div>
</form>

<?php include 'footer.php'; ?>
