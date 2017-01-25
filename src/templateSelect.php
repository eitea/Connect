<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToTemplate($userID); ?>

<div class="page-header">
  <h3>PDF Templates</h3>
</div>

<?php
$templatePreview = "Click on a Template above to preview it, or create a new One.";
if(isset($_POST['prevTemplate'])){
  $tempelID = intval($_POST['prevTemplate']);
  $templatePreview = "<iframe src='templatePreview.php?prevTemplate=$tempelID' style='width:100%; border:none;' scrolling='no' onload='resizeIframe(this)'></iframe>";
}
if(isset($_POST['removeTemplate'])){
  $tempelID = $_POST['removeTemplate'];
  $conn->query("DELETE FROM $pdfTemplateTable WHERE id = $tempelID");
}
?>
<form method="POST">
<table class="table table-hover">
  <thead>
    <th style="width:40%">Options</th>
    <th>Name</th>
  </thead>
  <tbody>
    <?php
    $result = $conn->query("SELECT * FROM $pdfTemplateTable");
    while($result && ($row = $result->fetch_assoc())){
      $templID = $row['id'];
      echo '<tr>';
      echo '<td>';
      echo "<button type='submit' class='btn btn-default' name='removeTemplate' value='$templID' title='Delete'> <i class='fa fa-trash-o'></i></button> ";
      echo "<a href='templateDownload.php?id=$templID' target='_blank' class='btn btn-default' title='Export'> <i class='fa fa-download'></i></a> ";
      echo "<a href='templateEdit.php?id=$templID' class='btn btn-default' title='Edit'> <i class='fa fa-pencil'></i></a> ";
      echo "<button type='submit' value='$templID' name='prevTemplate' class='btn btn-default' title='Preview'> <i class='fa fa-search'></i></button> ";
      echo '</td>';
      echo "<td>" . $row['name'] . "</td>";
      echo '</tr>';
    }
    ?>
  </tbody>
</table>
</form>

<br><br><br>
<div class="row">
  <div  class="col-md-6">
    <div class="collapse" id="importTemplate">
        <form action="templateUpload.php" method="post" enctype="multipart/form-data">
          <div class="col-md-8">
            <input type="file" name="fileToUpload" id="fileToUpload">
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-warning" name="templateUpload">Upload</button>
          </div>
        </form>
    </div>
  </div>
  <div class="col-md-6 text-right">
    <a href="templateEdit.php" class="btn btn-warning">Create a new Template</a>
    <button class="btn btn-warning" type="button" data-toggle="collapse" data-target="#importTemplate" aria-expanded="false" aria-controls="collapseExample">
      <i class='fa fa-upload'></i> <i class="fa fa-caret-down"></i>
    </button>
  </div>
</div>
<br><br><br>

<hr><h4 style="color:grey; font-weight:bold;">Preview:</h4><hr>

<div class="container text-center">
  <?php  echo $templatePreview;  ?>
</div>

<script>
  function resizeIframe(obj) {
    obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
  }
</script>

<?php include 'footer.php'; ?>
