
<?php
require dirname(__DIR__) . "/connection.php";
$x = preg_replace("/[^A-Za-z0-9]/", '', $_GET['projectid']);
?>

<div id="infoModal-<?php echo $x; ?>" class="modal fade">
    <div class="modal-dialog modal-content modal-lg">
        <div class="modal-header h4">Verlauf</div>
        <div class="modal-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Benutzer</th>
                        <th>Von</th>
                        <th>Bis</th>
                        <th>Infotext</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    function carryOverAdder_Hours($a, $b) {
                        $b = round($b);
                        if ($a == '0000-00-00 00:00:00') {
                            return $a;
                        }
                        $date = new DateTime($a);
                        if ($b < 0) {
                            $b *= -1;
                            $date->sub(new DateInterval("PT" . $b . "H"));
                        } else {
                            $date->add(new DateInterval("PT" . $b . "H"));
                        }
                        return $date->format('Y-m-d H:i:s');
                    }
                    $result = $conn->query("SELECT p.start, p.end, infoText, internInfo, firstname, lastname, timeToUTC
                    FROM projectBookingData p INNER JOIN logs ON logs.indexIM = p.timestampID LEFT JOIN UserData ON logs.userID = UserData.id WHERE p.dynamicID = '$x' ORDER BY p.start");
                    while($result && ($row = $result->fetch_assoc())){
                        $A = substr(carryOverAdder_Hours($row['start'],$row['timeToUTC']),0, -3);
                        $B = 'Gerade in Arbeit';
                        if($row['end'] != '0000-00-00 00:00:00') $B = substr(carryOverAdder_Hours($row['end'],$row['timeToUTC']), 11, 5);

                        echo '<tr>';
                        echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
                        echo '<td>'.$A.'</td>';
                        echo '<td>'.$B.'</td>';
                        echo '<td>'.$row['infoText'].'</td>';
                        echo '<td>'.$row['internInfo'].'</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">O.K.</button>
        </div>
    </div>
</div>
