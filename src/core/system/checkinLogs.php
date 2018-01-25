<?php require dirname(dirname(__DIR__)).'/header.php'; ?>
<div class="page-header"><h3>Checkin - Logs</h3></div>
<table class="table table-hover">
<thead><tr>
    <th><?php echo $lang['USERS']; ?></th>
    <th><?php echo $lang['TIMES']; ?></th>
    <th>IP</th>
    <th>Header</th>
    </tr></thead>
    <tbody>
    <?php
    $result = $conn->query("SELECT firstname, lastname, remoteAddr, userAgent, time FROM checkinLogs LEFT JOIN logs ON indexIM = timestampID LEFT JOIN UserData ON userID = UserData.id"); echo $conn->error;
    while($row = $result->fetch_assoc()){
        echo '<tr>';
        echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
        echo '<td>'.$row['time'].'</td>';
        echo '<td>'.$row['remoteAddr'].'</td>';
        echo '<td>'.$row['userAgent'].'</td>';
        echo '</tr>';
    }
    ?>
    </tbody>
</table>
<?php require dirname(dirname(__DIR__)).'/footer.php'; ?>
