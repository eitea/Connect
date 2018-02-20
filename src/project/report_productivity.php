<?php include dirname(__DIR__) . '/header.php';
enableToReport($userID); ?>
<script src="../plugins/chartsjs/Chart.min.js"></script>
<!-- BODY -->

<div class="page-header">
    <h3><?php echo $lang['PRODUCTIVITY']; ?></h3>
</div>

<?php
$filter_begin = $filter_end = carryOverAdder_Hours(getCurrentTimestamp(), -24);
if (isset($_POST['filterMonth_from'])) {
    $filter_begin = $_POST['filterMonth_from'] . '-01 05:00:00';
}
if (isset($_POST['filterMonth_to'])) {
    $filter_end = $_POST['filterMonth_to'] . '-01 05:00:00';
}

$filterID = $filterIDs = 0;
if (!empty($_POST['filterUserID'])) {
    $filterID = $filterIDs = intval($_POST['filterUserID']);
}

if (!empty($_POST['filterUserIDs']) && !empty($_POST['addUserID'])) {
    $filterIDs = $_POST['filterUserIDs'] . ' ' . $_POST['addUserID'];
}
?>

<form method="post" id="FILTER_FORM">
    <div class="container-fluid form-group">
        <div class="col-xs-6">
            <div class="input-group">
                <input type="text" class="form-control datepicker" name="filterMonth_from" value=<?php echo substr($filter_begin, 0, 10); ?> >
                <span class="input-group-addon"> - </span>
                <input type="text" class="form-control datepicker"  name="filterMonth_to" value="<?php echo substr($filter_end, 0, 10); ?>">
            </div>
        </div>
        <div class="col-sm-3">
            <select name='filterUserID' style="width:200px" class="js-example-basic-single btn-block">
                <?php
                $result = mysqli_query($conn, "SELECT $userTable.* FROM $userTable, $roleTable WHERE userID = id AND canBook = 'TRUE' AND id IN (" . implode(', ', $available_users) . ");");
                echo "<option name='filterUserID' value='0'>Benutzer ... </option>";
                while ($row = $result->fetch_assoc()) {
                    $i = $row['id'];
                    if ($filterID == $i) {
                        echo "<option name='filterUserID' value='$i' selected>" . $row['firstname'] . " " . $row['lastname'] . "</option>";
                    } else {
                        echo "<option name='filterUserID' value='$i' >" . $row['firstname'] . " " . $row['lastname'] . "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="col-sm-3">
            <button class="btn btn-warning" type="submit" name="filterBtn" >Filter</button>
        </div>
    </div>
</form>

<?php
$arr_IDs = explode(' ', $filterIDs);
if ($filterIDs):
    $userNames = $breaks = $productives = $drives = $nonproductives = '';
    $height = 100;
    foreach ($arr_IDs as $i): //i canBook
        $result_name = $conn->query("SELECT firstname FROM $userTable WHERE id = $i");
        $row_name = $result_name->fetch_assoc();
        $userName = '"' . $row_name['firstname'] . '"';
        $full = $break_hours = $productive = $nonproductive = $drive = 0;
        $result_log = $conn->query("SELECT * FROM $logTable WHERE userID = $i AND status = '0' AND timeEnd != '0000-00-00 00:00:00' AND DATE('$filter_begin') <= DATE(time) AND Date(time) <= DATE('$filter_end')");
        while ($result_log && ($row_log = $result_log->fetch_assoc())) {
            $full += timeDiff_Hours($row_log['time'], $row_log['timeEnd']);

            $result_break = $conn->query("SELECT TIMESTAMPDIFF(MINUTE, start, end) as breakCredit FROM projectBookingData where bookingType = 'break' AND timestampID = " . $row_log['indexIM']);
            while ($result_break && ($row_break = $result_break->fetch_assoc()))
                $break_hours += $row_break['breakCredit'] / 60;

            $result_proj = $conn->query("SELECT start, end, bookingType, status FROM $projectBookingTable LEFT JOIN $projectTable ON projectID = $projectTable.id
                                 WHERE bookingType != 'break' AND timestampID =" . $row_log['indexIM'] . " AND start != '0000-00-00 00:00:00' AND end != '0000-00-00 00:00:00'");
            while ($result_proj && ($row_proj = $result_proj->fetch_assoc())) {
                if ($row_proj['bookingType'] == 'project') {
                    if (!empty($row_proj['status'])) {
                        $productive += timeDiff_Hours($row_proj['start'], $row_proj['end']);
                    } else {
                        $nonproductive += timeDiff_Hours($row_proj['start'], $row_proj['end']);
                    }
                } else { //drive
                    $drive += timeDiff_Hours($row_proj['start'], $row_proj['end']);
                }
            }
        }
        echo mysqli_error($conn);
        $height += 50;
        //normalize numbers

        if (($productive + $nonproductive + $break_hours + $drive) > $full) {
            $full = $productive + $nonproductive + $break_hours + $drive;
        }

        if ($full > 0) {
            $break_hours = ($break_hours / $full) * 100;
            $productive = $productive / $full * 100;
            $drive = $drive / $full * 100;
            $nonproductive = 100 - $productive - $break_hours - $drive;
        } else {
            $break_hours = $productive = $drive = $nonproductive = 0;
        }

        $userNames .= $userName . ', ';
        $breaks .= $break_hours . ', ';
        $productives .= $productive . ', ';
        $drives .= $drive . ', ';
        $nonproductives .= $nonproductive . ', ';
    endforeach;
    /*
      echo "Pausen: $breaks";
      echo "<br>Produktiv: $productives";
      echo "<br>Fahrzeiten: $drives";
      echo "<br>Nicht Produktiv: $nonproductives";
     */
    ?>

    <div class="container-fluid">
        <canvas id="analysisChart" width="1000" height="<?php echo $height; ?>"></canvas>
    </div>
    <div class="container-fluid" style="padding-top:50px">
        <div class="col-sm-3">
            <select name='addUserID' style="width:200px" class="js-example-basic-single" form="FILTER_FORM">
                <?php
                $result = mysqli_query($conn, "SELECT $userTable.* FROM $userTable, $roleTable WHERE userID = id AND canBook = 'TRUE';");
                echo "<option name='filterUserID' value='0'>Benutzer ... </option>";
                while ($row = $result->fetch_assoc()) {
                    $i = $row['id'];
                    echo "<option name='filterUserID' value='$i' >" . $row['firstname'] . " " . $row['lastname'] . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-sm-1">
            <button class="btn btn-warning " type="submit" name="filterUserIDs" value="<?php echo $filterIDs; ?>" form="FILTER_FORM"> + </button>
        </div>
    </div>

    <script>
        $(function () {
            var ctx = document.getElementById("analysisChart");
            var myChart = new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: [<?php echo $userNames; ?>],
                    datasets: [{
                            label: ["Produktiv"],
                            data: [<?php echo $productives; ?>],
                            backgroundColor: "rgba(255, 153, 0, 0.5)"
                        }, {
                            label: ["Nicht Produktiv"],
                            data: [<?php echo $nonproductives; ?>],
                            backgroundColor: "rgba(149, 149, 149, 0.5)"
                        }, {
                            label: ["Pausen"],
                            data: [<?php echo $breaks; ?>],
                            backgroundColor: "rgba(48, 122, 219, 0.5)"
                        }, {
                            label: ["Fahrzeiten"],
                            data: [<?php echo $drives; ?>],
                            backgroundColor: "rgba(172, 196, 108, 0.5)"
                        }]
                },
                options: {
                    scales: {
                        xAxes: [{
                                stacked: true
                            }],
                        yAxes: [{
                                stacked: true,
                                barPercentage: 0.4
                            }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                return ' ' + data.datasets[tooltipItem.datasetIndex].label[0] + ': ' + Math.round(data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index] * 100) / 100 + '%';
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            });

            //---
        });
    </script>
<?php endif; ?>

<!-- /BODY -->
<?php include dirname(__DIR__) . '/footer.php'; ?>
