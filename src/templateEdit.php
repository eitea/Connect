<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToProject($userID); ?>

<script src='../plugins/tinymce/tinymce.min.js'></script>
<div class="page-header">
  <h3>PDF Template</h3>
</div>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['firstPage']) && !empty($_POST['templateName'])){
    $input = $conn->real_escape_string($_POST['firstPage']);
    $namae = test_input($_POST['templateName']);
    $conn->query("INSERT INTO $pdfTemplateTable (name, htmlCode) VALUES('$namae', '$input')");
  } else {
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Error: </strong>Template or Name may not be Empty or False.';
    echo '</div>';
  }
}
?>
<form method="POST">
  <div class="container">
    <input type="text" class="form-control" placeholder="Name of Template (Required)" name="templateName" />
  </div>
  <br>
  <div class="container">
    <div class="col-md-9">
      <textarea id="page1" name="firstPage">
        <h1>This is a Header</h1>
        Create your template here and use it on the project page!
      </textarea>
    </div>
    <div class="col-xs-2 text-right">
      <br> Firstname
      <br> Lastname
      <br> Start
      <br> Ende
      <br> Text
    </div>
    <div class="col-xs-1">
      <br> #FN#
      <br> #LN#
      <br> #PBStart#
      <br> #PBEnd#
      <br> #PBInfoText#
    </div>
  </form>

  <script>
  tinymce.init({
    selector: 'textarea#page1',
    height: 350,
    menubar: false,
    plugins: [
      'advlist autolink lists link image charmap print preview anchor',
      'searchreplace visualblocks code fullscreen',
      'insertdatetime media table contextmenu paste code jbimages save'
    ],
    toolbar: 'undo redo | styleselect | outdent indent | numlist table | jbimages | save',
    relative_urls: false
  });
  </script>

  <?php include 'footer.php'; ?>
