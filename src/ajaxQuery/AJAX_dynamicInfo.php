<?php
require dirname(__DIR__) . "/connection.php";
$x = preg_replace("/[^A-Za-z0-9]/", '', $_GET['projectid']);

session_start();
$userID = $_SESSION["userid"] or die("Session died");
$x = preg_replace("/[^A-Za-z0-9]/", '', $_GET['projectid']);
$result = $conn->query("SELECT activity, userID FROM dynamicprojectslogs WHERE projectID = '$x' AND
    ((activity = 'VIEWED' AND userid = $userID) OR ((activity = 'CREATED' OR activity = 'EDITED') AND userID != $userID)) ORDER BY logTime DESC LIMIT 1");//changes here have to be synced with dynamicProjects.php
if (($row = $result->fetch_assoc()) && $row['activity'] != 'VIEWED') {
        $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$x', 'VIEWED', $userID)");
}
echo $conn->error;
?>

<div id="infoModal-<?php echo $x; ?>" class="modal fade">
    <div class="modal-dialog modal-content modal-lg">
        <div class="modal-header h4">Verlauf</div>
        <div class="modal-body">
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#projectDescription<?php echo $x; ?>">Beschreibung</a></li>
                <li><a data-toggle="tab" href="#projectInfoBookings<?php echo $x; ?>">Buchungen</a></li>
                <li><a data-toggle="tab" href="#projectInfoLogs<?php echo $x; ?>">Logs</a></li>
            </ul>
            <div class="tab-content">
                <div id="projectDescription<?php echo $x; ?>" class="tab-pane fade in active"><br>
                    <?php
                    $result = $conn->query("SELECT projectdescription, projectstatus FROM dynamicprojects WHERE projectid = '$x'");
                    $dynrow =  $result->fetch_assoc();
                    echo $dynrow['projectdescription'];
                    ?>
                </div>
                <div id="projectInfoBookings<?php echo $x; ?>" class="tab-pane fade"><br>
                    <table class="table table-hover">
                        <thead><tr>
                                <th>Benutzer</th>
                                <th>Datum</th>
                                <th>Von</th>
                                <th>Bis</th>
                                <th>Infotext</th>
                                <th>%</th>
                            </tr></thead>
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
                            FROM projectBookingData p INNER JOIN logs ON logs.indexIM = p.timestampID LEFT JOIN UserData ON logs.userID = UserData.id WHERE p.dynamicID = '$x' ORDER BY p.start DESC");
                            while($result && ($row = $result->fetch_assoc())){
                                $A = carryOverAdder_Hours($row['start'],$row['timeToUTC']);
                                $B = 'Gerade in Arbeit';
                                if ($row['end'] != '0000-00-00 00:00:00') $B = substr(carryOverAdder_Hours($row['end'],$row['timeToUTC']), 11, 5);
                                echo '<tr>';
                                echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
                                echo '<td>'.substr($A,0,10).'</td>';
                                echo '<td>'.substr($A, 11, 5).'</td>';
                                echo '<td>'.$B.'</td>';
                                echo '<td>'.$row['infoText'].'</td>';
                                echo '<td>'.$row['internInfo'].'</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div id="projectInfoLogs<?php echo $x; ?>" class="tab-pane fade"><br>
                    <table class="table table-striped">
                        <thead><tr>
                                <th>Zeit <small>(System-Zeit)</small></th>
                                <th>Benutzer</th>
                                <th>Aktivität</th>
                        </tr></thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT firstname, lastname, p.activity, logTime FROM dynamicprojectslogs p LEFT JOIN UserData ON p.userID = UserData.id WHERE projectid = '$x'");
                            echo $conn->error;
                            while($result && ($row = $result->fetch_assoc())){
                                echo '<tr>';
                                echo '<td>'.$row['logTime'].'</td>';
                                echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
                                echo '<td>'.$row['activity'].'</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <form method="POST">
                <div class="pull-left"><?php echo $x; ?></div>
                <?php
                $result = $conn->query("SELECT id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND logs.userID = $userID AND `end` = '0000-00-00 00:00:00' LIMIT 1");
                $hasActiveBooking = $result->num_rows;
                $result = $conn->query("SELECT p.id FROM projectBookingData p WHERE `end` = '0000-00-00 00:00:00' AND dynamicID = '$x'");
                if($dynrow['projectstatus'] == 'ACTIVE' && $result->num_rows < 1 && !$hasActiveBooking){
                    echo "<button class='btn btn-default' type='submit' title='Task starten' name='play' value='$x'><i class='fa fa-play'></i></button>";
                }
                 ?>
                <button type="button" class="btn btn-default" data-dismiss="modal">O.K.</button>
            </form>
        </div>
    </div>
</div>
