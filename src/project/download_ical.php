<?php
require dirname(__DIR__).DIRECTORY_SEPARATOR.'connection.php';

$stmt = $conn->prepare("SELECT projectname, estimatedHours FROM dynamicprojects WHERE projectid = ? LIMIT 1");
$stmt->bind_param("s", $x);

$ical =
'BEGIN:VCALENDAR
VERSION:2.0
PRODID:https://www.eitea.at
CALSCALE:GREGORIAN
';

foreach($_POST['icalID'] as $x){
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $date = new DateTime($row['projectstart']);

    $ical .= 'BEGIN:VEVENT
LOCATION:
SUMMARY:'.$row['projectname'].'
DESCRIPTION: Geschätzte Zeit: '.$row['estimatedHours'].'
DTSTART;VALUE=DATE:'.$date->format('Ymd').'
DTEND;VALUE=DATE:'.$date->format('Ymd').'
DTSTAMP:'.$date->format("Ymd\THis\Z").'20170612T084410Z
END:VEVENT';
}

$ical .= 'END:VCALENDAR';

header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=schedule.ics");
header("Content-Type: text/calendar; charset=UTF-8");

echo $ical;
 ?>
