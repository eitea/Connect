<?php

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $result = rawurldecode($_POST['csv']);
}

header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename='export.csv'");
header("Content-Type: application/csv; charset=UTF-16LE");
echo $result;
?>
