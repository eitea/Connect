<?php include dirname(__DIR__) . '/header.php'; ?>
<div class="page-header"><h3>Projektbuchungen - Unbestimmt</h3></div>
<small>Alle Buchungen ohne Infotext oder gültigem Zeitstempel.</small>
<br><br>
<?php
//TODO: ADD USER AND DATE!!
if (isset($_POST['delete'])) {
    $conn->query("DELETE FROM projectBookingData WHERE id = " . intval($_POST['delete']));
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<div class="alert alert-success" class="close"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_DELETE'] . '</div>';
    }
}
?>
<table class="table table-hover">
    <thead><tr>
            <th>ID</th>
            <th>Zeitstempel</th>
            <th>Infotext</th>
            <th>Uhrzeit</th>
        </tr></thead>
    <tbody>
    <form method="POST">
        <?php
        $result = $conn->query("SELECT id, timestampID, infoText, start, end FROM projectBookingData WHERE infoText = '' OR timestampID IS NULL OR timestampID = 0 OR start IS NULL OR start = '0000-00-00 00:00:00' ");
        while ($result && ($row = $result->fetch_assoc())) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $row['timestampID'] . '</td>';
            echo '<td>' . $row['infoText'] . '</td>';
            echo '<td>' . $row['start'] . ' - ' . $row['end'] . '</td>';
            echo '<td><button type="submit" name="delete" value="' . $row['id'] . '" class="btn btn-default" ><i class="fa fa-trash-o"></i></button></td>';
            echo '</tr>';
        }
        ?>
    </form>
</tbody>
</table>

<div class="page-header"><h3>Projektbuchungen - Log</h3></div>
<table class="table table-hover">
    <thead><tr>
            <th>ID</th>
            <th>Statement</th>
        </tr></thead>
    <tbody>
        <?php
        $result = $conn->query("SELECT * FROM projectBookingData_audit ORDER BY id ASC");
        while ($result && ($row = $result->fetch_assoc())) {
            echo '<tr>';
            echo '<td>' . $row['bookingID'] . '</td>';
            echo '<td>' . $row['statement'] . '</td>';
            echo '</tr>';
        }
        ?>
    </tbody>
</table>

<?php include dirname(__DIR__) . '/footer.php'; ?>