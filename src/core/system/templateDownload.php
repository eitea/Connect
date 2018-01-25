<?php
if(isset($_GET['id'])){
  $id = $_GET['id'];

  include dirname(dirname(__DIR__)) . '/connection.php';
  $result = $conn->query("SELECT htmlCode, name FROM $pdfTemplateTable WHERE id = $id");
  if($result){
    $row = $result->fetch_assoc();
    $name = str_replace(' ', '_', $row['name']) .'.html';
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename='$name'");
    header("Content-Type: text/plain; charset=UTF-16LE");
    echo $row['htmlCode'];
  }
}
?>
