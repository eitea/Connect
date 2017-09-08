<?php
require dirname(__DIR__)."/connection.php";
require dirname(__DIR__)."/createTimestamps.php";
require dirname(__DIR__)."/language.php";

$x = intval($_GET['bookingID']);
$userID = intval($_GET['userID']);

$result = $conn->query("SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while($result && ($row = $result->fetch_assoc())){
  $available_companies[] = $row['companyID'];
}

$result = $conn->query("SELECT projectBookingData.*, clientID, companyID, timeToUTC FROM projectBookingData 
LEFT JOIN projectData ON projectData.id = projectID LEFT JOIN clientData ON clientData.id = projectData.clientID INNER JOIN logs ON logs.indexIM = timestampID WHERE projectBookingData.id = $x");
$row = $result->fetch_assoc(); //if this doesnt work, something really bad happened

?>
  <form method="post">
    <div class="modal fade editingModal-<?php echo $x ?>" role="dialog" aria-labelledby="mySmallModalLabel">
      <div class="modal-dialog modal-lg modal-content" role="document">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo substr($row['start'], 0, 10); ?></h4>
        </div>
        <div class="modal-body" style="max-height: 80vh;  overflow-y: auto;">
          <div class="row">
          <?php
          if(!empty($row['projectID'])){ //if this is no break, display client/project selection
            if(count($available_companies) > 1){
              echo "<div class='col-md-4'><select class='js-example-basic-single' onchange='showClients(\"#newClient$x\", this.value, 0, 0, \"#newProjectName$x\");' >";
              $companyResult = $conn->query("SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
              while($companyRow = $companyResult->fetch_assoc()){
                $selected = '';
                if($companyRow['id'] == $row['companyID']){
                  $selected = 'selected';
                }
                echo "<option $selected value=".$companyRow['id'].">".$companyRow['name']."</option>";
              }
              echo '</select></div>';
            }
            echo "<div class='col-md-4'><select id='newClient$x' class='js-example-basic-single' onchange='showProjects(\" #newProjectName$x \", this.value, 0);' >";
            $sql = "SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).") ORDER BY NAME ASC";
            if($filterings['company']){
              $sql = "SELECT * FROM $clientTable WHERE companyID = ".$filterings['company']." ORDER BY NAME ASC";
            }
            $clientResult = $conn->query($sql);
            while($clientRow = $clientResult->fetch_assoc()){
              $selected = '';
              if($clientRow['id'] == $row['clientID']){
                $selected = 'selected';
              }
              echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
            }
            echo "</select></div><div class='col-md-4'> <select id='newProjectName$x' class='js-example-basic-single' name='editing_projectID_$x'>";
            $sql = "SELECT * FROM $projectTable WHERE clientID =".$row['clientID'].'  ORDER BY NAME ASC';
            $clientResult = $conn->query($sql);
            while($clientRow = $clientResult->fetch_assoc()){
              $selected = '';
              if($clientRow['id'] == $row['projectID']){
                $selected = 'selected';
              }
              echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
            }
            echo "</select></div> <br><br>";
          } //end if(!break)

          $A = carryOverAdder_Hours($row['start'],$row['timeToUTC']);
          $B = carryOverAdder_Hours($row['end'],$row['timeToUTC']);

          if($row['chargedTimeStart'] == '0000-00-00 00:00:00'){
            $A_charged = '0000-00-00 00:00:00';
          } else {
            $A_charged = carryOverAdder_Hours($row['chargedTimeStart'],$row['timeToUTC']);
          }
          if($row['chargedTimeEnd'] == '0000-00-00 00:00:00'){
            $B_charged = '0000-00-00 00:00:00';
          } else {
            $B_charged = carryOverAdder_Hours($row['chargedTimeEnd'],$row['timeToUTC']);
          }
          ?>
        </div>
          <label><?php echo $lang['DATE']; ?>:</label>
          <div class="row">
            <div class="col-xs-6"><input type='text' class='form-control datetimepicker' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name="editing_time_from_<?php echo $x;?>" value="<?php echo substr($A,0,16); ?>"></div>
            <div class="col-xs-6"><input type='text' class='form-control datetimepicker' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='editing_time_to_<?php echo $x;?>' value="<?php echo substr($B,0,16); ?>"></div>
          </div>
          <br>
          <?php if($row['bookingType'] != 'break'): ?>
            <label><?php echo $lang['DATE'] .' '. $lang['CHARGED']; ?>:</label>
            <div class="row">
              <div class="col-xs-6"><input type='text' class='form-control datetimepicker' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='editing_chargedtime_from_<?php echo $x;?>' value="<?php echo substr($A_charged,0,16); ?>"></div>
              <div class="col-xs-6"><input type='text' class='form-control datetimepicker' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='editing_chargedtime_to_<?php echo $x;?>' value="<?php echo substr($B_charged,0,16); ?>"></div>
            </div>
          <?php endif; ?>
          <br>
          <label>Infotext</label>
          <textarea style='resize:none;' name='editing_infoText_<?php echo $x;?>' class='form-control' rows="5"><?php echo $row['infoText']; ?></textarea>
          <br>
          <?php
          if($row['bookingType'] != 'break' && $row['booked'] == 'FALSE'){//cant charge a break, can you
            echo "<div class='row'><div class='col-xs-2 col-xs-offset-8'><input id='".$x."_1' type='checkbox' onclick='toggle2(\"".$x."_2\")' name='editing_charge' value='".$x."' /> <label>".$lang['CHARGED']. "</label> </div>";
            echo "<div class='col-xs-2'><input id='".$x."_2' type='checkbox' onclick='toggle2(\"".$x."_1\")' name='editing_nocharge' value='".$x."' /> <label>".$lang['NOT_CHARGEABLE']. "</label> </div></div>";
          }
          ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" name="editing_save" value="<?php echo $x;?>"><?php echo $lang['SAVE']; ?></button>
        </div>
      </div>
    </div>
  </form>