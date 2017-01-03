<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToProject($userID); ?>

<div class="page-header">
  <h3>Select or Preview a Template</h3>
</div>

<?php
$templatePreview = "Click on a Template above to preview it, or create a new One.";
if(isset($_POST['prevTemplate'])){
  $tempelID = intval($_POST['prevTemplate']);
  $templatePreview = "<iframe src='templatePreview.php?prevTemplate=$tempelID' style='width:100%; border:none;' onload='resizeIframe(this)'></iframe>";
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
      echo "<td>" . $row['name'] . "</td>";
      echo "<td><button type='submit' value='$templID' name='prevTemplate' class='btn btn-warning'>Preview</button> ";
      echo " <button type='submit' class='btn btn-danger' name='removeTemplate' value='$templID'>Delete</button>";
      echo " <a href='templateEdit.php?id=$templID' class='btn btn-primary' >Edit</a>";
      echo '</tr>';
    }
    ?>
  </tbody>
</table>
</form>

<br><br><br><a href="templateEdit.php" class="btn btn-warning">Create a new Template</a><br>
<br><br><br><hr><h4 style="color:grey; font-weight:bold;">Preview:</h4><hr>

<div class="container text-center">
  <?php  echo $templatePreview;  ?>
</div>

<script>
  function resizeIframe(obj) {
    obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
  }
</script>

<?php include 'footer.php'; ?>
