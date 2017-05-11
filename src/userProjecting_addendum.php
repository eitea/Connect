<br>
<h4><?php echo $lang['GAPS_FOUND_PLEASE_CORRECT']; ?></h4>
<br><br>
<table class="table">
<thead>
  <th>Start</th>
  <th><?php echo $lang['END']; ?></th>
  <th>Info</th>
  <th>Intern</th>
</thead>
<tbody>
    <?php
    $result = $conn->query("SELECT indexIM, timeToUTC, time, timeEnd FROM logs WHERE indexIM = $request_addendum");
    echo mysqli_error($conn);
    $row = $result->fetch_assoc();
    $A = $row['time'];
    $res_b = $conn->query("SELECT * FROM projectBookingData WHERE timestampID = $request_addendum ORDER BY start ASC");
    while($row_b = $res_b->fetch_assoc()){
      $B = $row_b['start'];
      if(timeDiff_Hours($A, $B) > $bookingTimeBuffer/60){
        echo '<tr style="background-color:#feffec;"><td>?</td><td>?</td><td>?</td><td>?</td></tr>';
      }
      echo '<tr>';
      echo '<td>'.carryOverAdder_Hours($row_b['start'],$timeToUTC).'</td>';
      echo '<td>'.carryOverAdder_Hours($row_b['end'],$timeToUTC).'</td>';
      echo '<td>'.$row_b['infoText'].'</td>';
      echo '<td>'.$row_b['internInfo'].'</td>';
      echo '</tr>';
      $A = $row_b['end'];
    }
    $B = $row['timeEnd'];
    if(timeDiff_Hours($A, $B) > $bookingTimeBuffer/60){ //also check end
      echo '<tr style="background-color:#feffec;"><td>?</td><td>?</td><td>?</td><td>?</td></tr>';
    }
    ?>
</tbody>
</table>
