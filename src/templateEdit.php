<?php include 'header.php'; enableToTemplate($userID); ?>

<script src='../plugins/tinymce/tinymce.min.js'></script>

<div class="page-header">
  <h3><?php echo $lang['TEMPLATES']; ?></h3>
</div>
<?php
$templateContent = "<h1>This is a Header</h1> Create your template here and use it on the project page. Include a repeat pattern, name your template, and preview it!";
$templateName = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['firstPage']) && !empty($_POST['templateName'])){
    $templateContent = $conn->real_escape_string($_POST['firstPage']);
    $templateName = test_input($_POST['templateName']);
    if(isset($_GET['id'])){ //did we edit?
      $templateID = intval($_GET['id']);
      $conn->query("UPDATE $pdfTemplateTable SET name = '$templateName', htmlCode = '$templateContent' WHERE id = $templateID");
    } else { //or create a new one
      $conn->query("INSERT INTO $pdfTemplateTable (name, htmlCode) VALUES('$templateName', '$templateContent')");
      $templateID = $conn->insert_id;
      redirect("templateEdit.php?id=$templateID");
    }
  } else {
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Error: </strong>Template or Name may not be Empty or False.';
    echo '</div>';
  }
}
if(isset($_GET['id'])){
  $templateID = intval($_GET['id']);
  $result = $conn->query("SELECT * FROM $pdfTemplateTable WHERE id = $templateID");
  if($result && ($row = $result->fetch_assoc())){
    $templateContent = $row['htmlCode'];
    $templateName = $row['name'];
  } else {
    $templateContent = "Do not fiddle with the URL. Thank you.";
  }
}

if($templateName == 'Main_Report'){
  die("Cannot edit Main Report");
}
?>

<form method="POST">
  <div class="container-fluid">
    <div class="col-xs-10">
      <input type="text" class="form-control" placeholder="Name of Template (Required)" name="templateName" value="<?php echo $templateName; ?>" />
    </div>
    <div class="col-xs-2 text-right">
      <a href='templateSelect.php' class='btn btn-info btn-block'><?php echo $lang['RETURN']; ?> <i class='fa fa-arrow-right'></i></a>
    </div>
  </div>
  <br>
  <div class="container-fluid">
    <div class="col-sm-10" id="droppableDiv" style="max-width:780px;">
      <textarea id="firstPage" name="firstPage"><?php echo $templateContent; ?></textarea>
    </div>
    <div class="col-sm-2">
      <br><br>Click to Insert: <br><br>
      <button type="button" class="btn btn-warning btn-block btn-insert" value='[BOOKINGS]'><?php echo $lang['PROJECT_BOOKINGS']; ?></button>
      <button type="button" class="btn btn-warning btn-block btn-insert" value='[TIMESTAMPS]'><?php echo $lang['TIMESTAMPS']; ?></button>
    </div>
  </form>
</div>

<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
tinymce.init({
  selector: 'textarea',
  height: 1000,
  menubar: false,
  plugins: [
    'advlist autolink lists link image charmap print preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table contextmenu paste code jbimages save'
  ],
  toolbar: 'undo redo | styleselect | outdent indent | bullist table | jbimages | save',
  relative_urls: false,
  content_css: '../plugins/homeMenu/template.css'
});

$('.btn-insert').click(function() {
    var inText = this.value;
    tinymce.activeEditor.execCommand('mceInsertContent', false, inText);
});
</script>

<?php include 'footer.php'; ?>
