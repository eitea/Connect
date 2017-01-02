<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToProject($userID); ?>

<div class="page-header">
  <h3>Select or Preview a Template</h3>
</div>

<?php
$templatePreview = "Click on a Template above to preview it, or create a new One.";
if(isset($_POST['prevTemplate'])){
  $tempelID = $_POST['prevTemplate'];
  $result = $conn->query("SELECT * FROM $pdfTemplateTable WHERE id = $tempelID");
  $row = $result->fetch_assoc();
  $templatePreview = $row['htmlCode'];
}

if(isset($_POST['removeTemplate'])){
  $tempelID = $_POST['removeTemplate'];
  $conn->query("DELETE FROM $pdfTemplateTable WHERE id = $tempelID");
}
 ?>

<form method="POST">
<table class="table table-hover">
  <thead>
    <th>Select</th>
    <th>Name</th>
    <th>Options</th>
  </thead>
  <tbody>
    <?php
    $result = $conn->query("SELECT * FROM $pdfTemplateTable");
    while($result && ($row = $result->fetch_assoc())){
      $templID = $row['id'];
      echo '<tr>';
      echo "<td><input type='checkbox'  /></td>";
      echo "<td><a href='templateEdit.php?id=$templID' >" . $row['name'] . "</a></td>";
      echo "<td><button type='submit' value='$templID' name='prevTemplate' class='btn btn-default'>Preview</button>";
      echo "<button type='submit' class='btn btn-warning' name='removeTemplate' value='$templID'>Delete</button>";
      echo '</tr>';
    }
    ?>
  </tbody>
</table>
</form>

<a href="templateEdit.php">Create new Template</a>

<br><hr><br>

<div class="container">
<?php echo $templatePreview; ?>
</div>

<?php include 'footer.php'; ?>
