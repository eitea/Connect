<?php
if(isset($_POST["templateUpload"])) {
  if ($_FILES["fileToUpload"]["error"] > 0){
    echo "Error: " . $_FILES["file"]["error"] . "<br />";
  } elseif ($_FILES["fileToUpload"]["size"] > 500000) {
    echo "Sorry, your file is too large.";
  } elseif ($_FILES["fileToUpload"]["type"] != "text/html") {
    echo "File must be .html";
  }

  $fp = file_get_contents($_FILES['fileToUpload']['tmp_name'], 'rb');
  $name = basename($_FILES["fileToUpload"]["name"]);
  if($fp){
    include 'connection.php';
    $fp = $conn->real_escape_string($fp);
    $conn->query("INSERT INTO $pdfTemplateTable (name, htmlCode) VALUES('$name', '$fp')");
    echo mysqli_error($conn);
  }
}

header("Location: templateSelect.php");
?>