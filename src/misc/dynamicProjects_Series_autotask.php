<?php
// this script is intended to be called every day (because all rules are day based), but it can also be called every minute
require dirname(__DIR__) . '/connection.php';
require "dynamicProjects_ProjectSeries.php";

$result = $conn->query("SELECT * FROM dynamicprojects");
while ($row = $result->fetch_assoc()) {
    $id = $row["projectid"];
    $name = $row["projectname"];
    $start = $row["projectstart"];
    $end = $row["projectend"];
    $status = $row["projectstatus"];
    $seriesResult = $conn->query("SELECT * FROM dynamicprojectsseries WHERE projectid='$id'");
    echo $conn->error;
    $parent = $row["projectparent"];
    // $completed = $row["projectcompleted"];
    $series = null;
    $previous_date = "no previous date";
    echo "<br>Checking project ${name}: ";
    if ($seriesResult) {
        $seriesRow = $seriesResult->fetch_assoc();
        $series = $seriesRow["projectseries"];
        $series = base64_decode($series);
        $series = unserialize($series, array("allowed_classes" => array("ProjectSeries")));
        $previous_date = $seriesRow["projectnextdate"];
    } else {
        echo "series couldn't be queried, aborting";
        continue;
    }
    if (!$series) {
        echo "series couldn't be unserialized, aborting";
        continue;
    } else {
        $next_date = $series->get_next_date();
    }
    echo "<br> $series";
    echo "<br> Previous Next: $previous_date";
    echo "<br> Current Next: $next_date";
    $series = base64_encode(serialize($series));
    $id = $conn->real_escape_string($id);

    $stmt = $conn->prepare("UPDATE dynamicprojectsseries SET projectnextdate = '$next_date', projectseries = ? WHERE projectid = '$id'");
    echo $conn->error;
    $null = null;
    $stmt->bind_param("b", $null);
    $stmt->send_long_data(0, $series);
    $stmt->execute();
    echo $stmt->error;
    echo "<br>done<br>";
}

?>

<!-- for testing in browser -->
<script>
setTimeout(function() {
    document.write("<br>reloading...")
}, 9000);
setTimeout(function() {
    window.location.reload();
}, 10000);
</script>
