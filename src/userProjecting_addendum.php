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
    echo '<tr>';
    echo '<td>'.carryOverAdder_Hours($request_addendum['prev_row']['start'],$request_addendum['timeToUTC']).'</td>';
    echo '<td>'.carryOverAdder_Hours($request_addendum['prev_row']['end'],$request_addendum['timeToUTC']).'</td>';
    echo '<td>'.$request_addendum['prev_row']['infoText'].'</td>';
    echo '<td>'.$request_addendum['prev_row']['internInfo'].'</td>';
    echo '</tr>';
    echo '<tr style="background-color:#feffec;"><td>?</td><td>?</td><td>?</td><td>?</td></tr>';
    echo '<tr>';
    echo '<td>'.carryOverAdder_Hours($request_addendum['cur_row']['start'],$request_addendum['timeToUTC']).'</td>';
    echo '<td>'.carryOverAdder_Hours($request_addendum['cur_row']['end'],$request_addendum['timeToUTC']).'</td>';
    echo '<td>'.$request_addendum['cur_row']['infoText'].'</td>';
    echo '<td>'.$request_addendum['cur_row']['internInfo'].'</td>';
    echo '</tr>';
    $start = substr(carryOverAdder_Hours($request_addendum['prev_row']['end'], $request_addendum['timeToUTC']), 11, 5);
    $end = substr(carryOverAdder_Hours($request_addendum['cur_row']['start'], $request_addendum['timeToUTC']), 11, 5);
    ?>
</tbody>
</table>
