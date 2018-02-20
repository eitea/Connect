<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>

<div class="page-header">
    <h3><?php echo $lang['USER_INACTIVE']; ?></h3>
</div>

<?php
if (isset($_POST['delete']) && isset($_POST['indeces'])) {
    foreach ($_POST['indeces'] as $e) {
        $conn->query("DELETE FROM $deactivatedUserTable WHERE id = $e");
    }
} elseif (isset($_POST['activate']) && isset($_POST['indeces'])) {
    foreach ($_POST['indeces'] as $x) {
        $acc = true;
        //insert user
        $sql = "INSERT INTO $userTable(id, firstname, lastname, psw, sid, email, gender, beginningDate, preferredLang, terminalPin, kmMoney)
    SELECT id, firstname, lastname, psw, sid, email, gender, beginningDate, preferredLang, terminalPin, kmMoney FROM $deactivatedUserTable WHERE id = $x";
        if (!$conn->query($sql)) {
            $acc = false;
            echo 'userErr: ' . mysqli_error($conn);
        }

        //insert logs
        $sql = "INSERT INTO $logTable (userID, time, timeEnd, status, timeToUTC, indexIM)
    SELECT userID, time, timeEnd, status, timeToUTC, indexIM FROM $deactivatedUserLogs WHERE userID = $x";
        if (!$conn->query($sql)) {
            $acc = false;
            echo 'logErr: ' . mysqli_error($conn);
        }

        //insert projectBookings
        $sql = "INSERT IGNORE INTO $projectBookingTable (start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit)
    SELECT start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit
    FROM $deactivatedUserProjects INNER JOIN $deactivatedUserLogs ON $deactivatedUserLogs.indexIM = $deactivatedUserProjects.timestampID WHERE $deactivatedUserLogs.userID = $x";
        if (!$conn->query($sql)) {
            $acc = false;
            echo '<br>projErr: ' . mysqli_error($conn);
        }

        //insert travelling expenses
        $sql = "INSERT IGNORE INTO $travelTable (userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses)
    SELECT userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses FROM $deactivatedUserTravels WHERE userID = $x";
        if (!$conn->query($sql)) {
            $acc = false;
            echo '<br>travelErr: ' . mysqli_error($conn);
        }

        //insert interval
        $sql = "INSERT IGNORE INTO $intervalTable (userID, mon, tue, wed, thu, fri, sat, sun, overTimeLump, pauseAfterHours, hoursOfRest, vacPerYear, startDate, endDate)
    SELECT userID, mon, tue, wed, thu, fri, sat, sun, overTimeLump, pauseAfterHours, hoursOfRest, vacPerYear, startDate, endDate FROM $deactivatedUserDataTable WHERE userID = $x";
        if (!$conn->query($sql)) {
            $acc = false;
            echo '<br>vacErr: ' . mysqli_error($conn);
        }

        //insert roles if not present
        if ($conn->query("SELECT userID from $roleTable WHERE userID = $x")->num_rows == 0) {
            $sql = "INSERT IGNORE INTO $roleTable (userID) VALUES($x);";
            if (!$conn->query($sql)) {
                $acc = false;
                echo '<br>roleErr: ' . mysqli_error($conn);
            }
        }

        //insert socialprofile if not present
        if ($conn->query("SELECT userID from socialprofile WHERE userID = $x")->num_rows == 0) {
            $sql = "INSERT IGNORE INTO socialprofile (userID, isAvailable, status) VALUES($x, 'TRUE', '-');";
            if (!$conn->query($sql)) {
                $acc = false;
                echo '<br>socialErr: ' . mysqli_error($conn);
            }
        }

        if ($acc) {
            $conn->query("DELETE FROM $deactivatedUserTable WHERE id = $x");
        }
    }
}
?>

<form method="post">
    <table class="table table-hover">
        <thead>
        <th>Auswahl</th>
        <th>Benutzer</th>
        <th>Austrittsdatum</th>
        </thead>
        <tbody>
<?php
$result = $conn->query("SELECT * FROM $deactivatedUserTable");
while ($result && ($row = $result->fetch_assoc())) {
    echo '<tr>';
    echo '<td><input type="checkbox" name="indeces[]" value="' . $row['id'] . '" /></td>';
    echo '<td>' . $row['firstname'] . ' ' . $row['lastname'] . '</td>';
    echo '<td> - </td>';
    echo '</tr>';
}
?>
        </tbody>
    </table>
    <br><br>
    <div class="text-right">
        <button type="submit" class="btn btn-danger" name="delete">Permanent Löschen</button> <button type="submit" class="btn btn-warning" name="activate">Re-Aktivieren</button>
    </div>

</form>

<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
