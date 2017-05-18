<?php
function editing_save($x){ //$x = bookingID
  $result = $conn->query("SELECT $logTable.timeToUTC FROM $logTable, $projectBookingTable WHERE $projectBookingTable.id = $x AND $projectBookingTable.timestampID = $logTable.indexIM");
  $row = $result->fetch_assoc();
  $toUtc = $row['timeToUTC'] * -1;
  if(test_Date($_POST["editing_time_from_".$x].':00') && test_Date($_POST["editing_time_to_".$x].':00')){
    if(!empty($_POST["editing_projectID_".$x])){
      $new_projectID = $_POST["editing_projectID_".$x];
    } else { //break
      $new_projectID = 'NULL';
    }
    $new_A = carryOverAdder_Hours($_POST["editing_time_from_".$x].':00', $toUtc);
    $new_B = carryOverAdder_Hours($_POST["editing_time_to_".$x].':00', $toUtc);

    $chargedTimeStart= '0000-00-00 00:00:00';
    $chargedTimeFin = '0000-00-00 00:00:00';
    if($_POST['editing_chargedtime_from_'.$x] != '0000-00-00 00:00'){
      $chargedTimeStart = carryOverAdder_Hours($_POST['editing_chargedtime_from_'.$x].':00', $toUtc);
    }
    if($_POST['editing_chargedtime_to_'.$x] != '0000-00-00 00:00'){
      $chargedTimeFin = carryOverAdder_Hours($_POST['editing_chargedtime_to_'.$x].':00', $toUtc);
    }
    $new_text = test_input($_POST['editing_infoText_'.$x]);

    $new_charged = 'FALSE';
    if(isset($_POST['editing_charge']) || isset($_POST['editing_nocharge'])){
      $new_charged = 'TRUE';
    }
    $conn->query("UPDATE $projectBookingTable SET start='$new_A', end='$new_B', projectID=$new_projectID, infoText='$new_text', booked='$new_charged', chargedTimeStart='$chargedTimeStart', chargedTimeEnd='$chargedTimeFin' WHERE id = $x");

    //update charged
    if(isset($_POST['editing_charge'])){
      if($chargedTimeStart != '0000-00-00 00:00:00'){
        $new_A = $chargedTimeStart;
      }
      if($chargedTimeFin != '0000-00-00 00:00:00'){
        $new_B = $chargedTimeFin;
      }
      $hours = timeDiff_Hours($new_A, $new_B);
      $sql = "UPDATE $projectTable SET hours = hours - $hours WHERE id = $x";
      $conn->query($sql);
    }
    if(!mysqli_error($conn)){
      echo '<div class="alert alert-success alert-over fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo $lang['OK_SAVE'];
      echo '</div>';
    }
  } else {
    echo '<div class="alert alert-danger alert-over fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Error: </strong>Input incorrect.';
    echo '</div>';
  }
  echo mysqli_error($conn);
}

?>
