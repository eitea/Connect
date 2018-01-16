<?php //accountJournal, getProjects
if(isset($_GET['name'])){
  $name = $_GET['name'];
} else {
  $name = "export";
}

header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=$name.csv");
header("Content-Type: application/csv; charset=UTF-16LE");

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['csvDownload'])){
    $result = explode("\n", rawurldecode($_POST['csv']));
    $fp = fopen('php://output', 'w');
    foreach($result as $line){
      $val = explode(';', $line);
      fputcsv($fp, $val, ';');
    }
    fclose($fp);
  } else {
    echo rawurldecode($_POST['csv']);
  }
}
?>