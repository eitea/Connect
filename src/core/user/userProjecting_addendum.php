<br>
<h4><?php echo $lang['GAPS_FOUND_PLEASE_CORRECT']; ?></h4>
<br><br>
<table class="table">
    <thead>
        <th>Start</th>
        <th><?php echo $lang['END']; ?></th>
        <?php if(count($available_companies) > 2) echo '<th>'.$lang['COMPANY'].'</th>'; ?>
        <th><?php echo $lang['CLIENT']; ?></th>
        <th><?php echo $lang['PROJECT']; ?></th>
        <th>Info</th>
        <th>Intern</th>
    </thead>
    <tbody>
        <?php
        $result = $conn->query("SELECT indexIM, timeToUTC, time, timeEnd FROM logs WHERE indexIM = $request_addendum");
        echo mysqli_error($conn);
        $row = $result->fetch_assoc();
        $A = $row['time'];
        $val = (count($available_companies) > 2) ? '<td>-</td>' : ''; //5acc434437ddf

        $sql = "SELECT p.*, clientData.companyID, projectData.name AS projectName, p.id AS bookingTableID, companyData.name AS companyName, clientData.name AS clientName
        FROM projectBookingData p
        LEFT JOIN projectData ON (p.projectID = projectData.id)
        LEFT JOIN clientData ON (projectData.clientID = clientData.id)
        LEFT JOIN companyData ON companyData.id = clientData.companyID
        WHERE p.timestampID = $request_addendum ORDER BY end, start;";
        $res_b = $conn->query($sql); echo $conn->error;
        if($res_b && $res_b->num_rows == 0){
            echo '<tr style="background-color:#eeeeee;"><td>'.carryOverAdder_Hours($A,$timeToUTC).'</td><td>-</td>'.$val.'<td>-</td><td>-</td><td>'.$lang['CHECK_IN'].'</td><td>-</td></tr>';
            echo '<tr style="background-color:#fffced;"><td>?</td><td>?</td><td>?</td>'.$val.'<td>?</td><td>?</td><td>?</td></tr>';
            echo '<tr style="background-color:#eeeeee;"><td>-</td><td>'.carryOverAdder_Hours($row['timeEnd'],$timeToUTC).'</td>'.$val.'<td>-</td><td>-</td><td>'.$lang['CHECK_OUT'].'</td><td>-</td></tr>';
        } elseif($res_b && ($row_b = $res_b->fetch_assoc())){
            $B = $row_b['start']; //first booking of the day
            if(timeDiff_Hours($A, $B) > $bookingTimeBuffer/60){
                echo '<tr style="background-color:#eeeeee;"><td>'.carryOverAdder_Hours($A,$timeToUTC).'</td>'.$val.'<td>-</td><td>-</td><td>-</td><td>'.$lang['CHECK_IN'].'</td><td>-</td></tr>';
                echo '<tr style="background-color:#fffced;"><td>?</td>'.$val.'<td>?</td><td>?</td><td>?</td><td>?</td><td>?</td></tr>';
                $A = $B;
            }
            do {
                $B = $row_b['start'];
                if(timeDiff_Hours($A, $B) > $bookingTimeBuffer/60){
                    echo '<tr style="background-color:#fffced;"><td>?</td><td>?</td><td>?</td><td>?</td><td>?</td><td>?</td></tr>';
                }
                echo '<tr>';
                echo '<td>'.carryOverAdder_Hours($row_b['start'],$timeToUTC).'</td>';
                echo '<td>'.carryOverAdder_Hours($row_b['end'],$timeToUTC).'</td>';
                if(count($available_companies) > 2) echo '<td>'.$row_b['companyName'].'</td>'; //5acc434437ddf
                echo '<td>'.$row_b['clientName'] .'</td>';
                echo "<td>".$row_b['projectName'] ."</td>";
                echo '<td>'.$row_b['infoText'].'</td>';
                echo '<td>'.$row_b['internInfo'].'</td>';
                echo '</tr>';
                $A = $row_b['end'];
            } while($row_b = $res_b->fetch_assoc());
            $B = $row['timeEnd']; //checkout time
            if(timeDiff_Hours($A, $B) > $bookingTimeBuffer/60){
                echo '<tr style="background-color:#fffced;"><td>?</td><td>?</td><td>?</td><td>?</td><td>?</td><td>?</td></tr>';
                echo '<tr style="background-color:#eeeeee;"><td>-</td><td>'.carryOverAdder_Hours($B,$timeToUTC).'</td><td>-</td><td>-</td><td>'.$lang['CHECK_OUT'].'</td><td>-</td></tr>';
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        }
        ?>
    </tbody>
</table>

<input type="hidden" name="confirm_addendum" value="true" />
