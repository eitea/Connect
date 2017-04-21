<?php include 'header.php'; enableToTime($userID); ?>
<div class="page-header">
  <h3><?php echo $lang['ADJUSTMENTS']; ?></h3>
</div>

<?php
$filterID = $filterName = '';
$activeTab = 'log';

//all forms set this on submit
if(isset($_POST['filterUserID'])){
  $inp = explode(', ', $_POST['filterUserID']);
  $filterID = $inp[0];
  $filterName = $inp[1];
}

if(!empty($_POST['creatInfoText']) && !empty($_POST['creatFromTime']) && test_Date($_POST['creatTimeTime']. "-01 12:00:00")){
  $accept = true;
  //vacation/ log come from different forms, button name tells us which one was submitted
  if(isset($_POST['creatSignVac'])){
    $cType = $activeTab = 'vac';
    $addOrSub = intval($_POST['creatSignVac']);
  } elseif(isset($_POST['creatSignLog'])){
    $cType = $activeTab = 'log';
    $addOrSub = intval($_POST['creatSignLog']);
  } else {
    $accept = false;
  }
  if($accept){
    $hours = floatval($_POST['creatFromTime']);
    $date = $_POST['creatTimeTime']. "-01 12:00:00";
    if($hours > 0){
      $infoText = test_input($_POST['creatInfoText']);
      $conn->query("INSERT INTO $correctionTable (userID, hours, infoText, addOrSub, createdOn, cType) VALUES($filterID, $hours, '$infoText', '$addOrSub', '$date', '$cType')");
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Please do not enter hours less or equal to 0.';
      echo '</div>';
    }
  }
}

echo mysqli_error($conn);
?>


<form method="POST">
  <select name='filterUserID' style="width:200px" class="js-example-basic-single">
    <?php
    $result = mysqli_query($conn, "SELECT id, firstname, lastname FROM $userTable WHERE id IN (".implode(', ', $available_users).")");
    echo "<option name='filterUserID' value='0'>Benutzer ... </option>";
    while($row = $result->fetch_assoc()){
      $i = $row['id'];
      echo "<option name='filterUserID' value='".$row['id'].', '.$row['firstname']. " " .$row['lastname']."' >".$row['firstname'] . " " . $row['lastname']."</option>";
    }
    ?>
  </select>
    <button type="submit" class="btn btn-warning btn-sm">Select</button>
</form>

<br><br><br>

<?php if($filterID): ?>
<ul class="nav nav-tabs">
  <li <?php if($activeTab == 'log'){echo 'class="active"';}?>><a data-toggle="tab" href="#log"><?php echo $lang['HOURS']; ?></a></li>
  <li <?php if($activeTab == 'vac'){echo 'class="active"';}?>><a data-toggle="tab" href="#vac"><?php echo $lang['VACATION']; ?></a></li>
</ul>

<div class="tab-content">
  <div id="log" class="tab-pane fade <?php if($activeTab == 'log'){echo 'in active';}?>">
    <div class="container-fluid">
      <form method="POST">
        <?php echo "<br><h3>$filterName - <small>".$lang['TIMES']."</small></h3><br><br>"; ?>

          <input type="text" readonly style="display:none" name="filterUserID" value="<?php echo "$filterID, $filterName"; ?>" />
          <div class="row">
            <div class="col-xs-5">
              <?php echo $lang['ADD']; ?> :<br><br>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-5">
              <label>Infotext</label>
              <input type="text" class="form-control" placeholder="Infotext" name="creatInfoText" />
            </div>
            <div class="col-xs-3">
              <label><?php echo $lang['AFFECTED_MONTH']; ?></label>
              <input id="calendar" type="month" class="form-control" name="creatTimeTime" value='<?php echo substr(getCurrentTimestamp(),0,7); ?>' />
            </div>
            <div class="col-xs-2">
              <label><?php echo $lang['HOURS']; ?></label>
              <input type="number" step="any" class="form-control" size='2' name="creatFromTime" />
            </div>
            <div class="col-xs-2">
              <label><?php echo $lang['ADD']; ?></label>
              <div class="dropdown">
                <a href="#" class="btn btn-warning dropdown-toggle" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                  ( + / - )
                </a>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                  <li><button type="submit" class="btn-link" style="white-space: nowrap;" name="creatSignLog" value="1"><?php echo $lang['ABSOLVED_HOURS']; ?> (+)</button></li>
                  <li><button type="submit" class="btn-link" style="white-space: nowrap;" name="creatSignLog" value="-1"><?php echo $lang['EXPECTED_HOURS']; ?> ( - )</button></li>
                </ul>
              </div>
            </div>
          </div>
          <br><br><br>

          <table class="table table-hover">
            <thead>
              <th>Name</th>
              <th><?php echo $lang['CORRECTION'] .' '. $lang['DATE']; ?> (UTC)</th>
              <th><?php echo $lang['ADJUSTMENTS'].' '. $lang['HOURS']; ?></th>
              <th><?php echo $lang['AFFECTED_MONTH']; ?></th>
              <th>Info</th>
            </thead>
            <tbody>
              <?php
              $result = $conn->query("SELECT $correctionTable.*, $userTable.firstname FROM $correctionTable, $userTable WHERE cType = 'log' AND $userTable.id = $correctionTable.userID  AND userID = $filterID");
              echo mysqli_error($conn);
              while($result && ($row = $result->fetch_assoc())){
                $hours = $row['hours'] * $row['addOrSub'];
                echo '<tr>';
                echo '<td>'.$row['firstname'].'</td>';
                echo '<td>'.substr($row['cOnDate'],0,16).'</td>';
                echo '<td>'.sprintf("%+.2f",$hours).'</td>';
                echo '<td>'.substr($row['createdOn'],0,7).'</td>';
                echo '<td>'.$row['infoText'].'</td>';
                echo '</tr>';
              }
              ?>
            </tbody>
          </table>
          <br><br>
      </form>
    </div>
  </div>
  <div id="vac" class="tab-pane fade <?php if($activeTab == 'vac'){echo 'in active';}?>">
    <form method="POST">
      <div class="container-fluid">
        <form method="POST">
          <?php echo "<br><h3>$filterName - <small>".$lang['VACATION']."</small></h3><br><br>"; ?>
            <input type="text" readonly style="display:none" name="filterUserID" value="<?php echo "$filterID, $filterName"; ?>" />
            <div class="row">
              <div class="col-xs-5">
                <?php echo $lang['ADD']; ?> :<br><br>
              </div>
            </div>
            <div class="row">
              <div class="col-xs-5">
                <label>Infotext</label>
                <input type="text" class="form-control" placeholder="Infotext" name="creatInfoText" />
              </div>
              <div class="col-xs-3">
                <label><?php echo $lang['AFFECTED_MONTH']; ?></label>
                <input id="calendar2" type="month" class="form-control" name="creatTimeTime" value='<?php echo substr(getCurrentTimestamp(),0,7); ?>' />
              </div>
              <div class="col-xs-2">
                <label><?php echo $lang['DAYS']; ?></label>
                <input type="number" step="any" class="form-control" size='2' name="creatFromTime" />
              </div>
              <div class="col-xs-2">
                <label><?php echo $lang['ADD']; ?></label>
                <div class="dropdown">
                  <a href="#" class="btn btn-warning dropdown-toggle" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    ( + / - )
                  </a>
                  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                    <li><button type="submit" class="btn-link" style="white-space: nowrap;" name="creatSignVac" value="1"> <?php echo $lang['DAYS'].' '.$lang['AVAILABLE']; ?> (+)</button></li>
                    <li><button type="submit" class="btn-link" style="white-space: nowrap;" name="creatSignVac" value="-1"> <?php echo $lang['DAYS'].' '.$lang['CONSUMED']; ?> ( - )</button></li>
                  </ul>
                </div>
              </div>
            </div>
            <script>
            $("#calendar").datepicker({
              format: "yyyy-mm",
              viewMode: "months",
              minViewMode: "months"
            });
            $("#calendar2").datepicker({
              format: "yyyy-mm",
              viewMode: "months",
              minViewMode: "months"
            });
            </script>
            <br><br><br>

            <table class="table table-hover">
              <thead>
                <th>Name</th>
                <th><?php echo $lang['CORRECTION'] .' '. $lang['DATE']; ?> (UTC)</th>
                <th><?php echo $lang['ADJUSTMENTS'].' '. $lang['DAYS']; ?></th>
                <th><?php echo $lang['AFFECTED_MONTH']; ?></th>
                <th>Info</th>
              </thead>
              <tbody>
                <?php
                $result = $conn->query("SELECT $correctionTable.*, $userTable.firstname FROM $correctionTable, $userTable WHERE cType='vac' AND $userTable.id = $correctionTable.userID  AND userID = $filterID");
                echo mysqli_error($conn);
                while($result && ($row = $result->fetch_assoc())){
                  $hours = $row['hours'] * $row['addOrSub'];
                  echo '<tr>';
                  echo '<td>'.$row['firstname'].'</td>';
                  echo '<td>'.substr($row['cOnDate'],0,16).'</td>';
                  echo '<td>'.sprintf("%+.2f",$hours).'</td>';
                  echo '<td>'.substr($row['createdOn'],0,7).'</td>';
                  echo '<td>'.$row['infoText'].'</td>';
                  echo '</tr>';
                }
                ?>
              </tbody>
            </table>
            <br><br>
        </form>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php include 'footer.php'; ?>
