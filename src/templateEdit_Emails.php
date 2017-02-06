<?php include 'header.php'; ?>
<?php enableToCore($userID); ?>

<script src='../plugins/jQuery/jquery-ui-1.12.1/jquery-ui.min.js'></script>
<script src='../plugins/tinymce/tinymce.min.js'></script>

<style>
.draggable {
    cursor: pointer;
    background-color: #e9a954;
    color:white;
    margin-bottom: 1em;
    padding: 10px;
}
.repeat{
  background-color: #bf564c;
}
</style>

<div class="page-header">
  <h3><?php echo $lang['PDF_TEMPLATES']; ?></h3>
</div>

<?php
$templateContent = "<h1>E-Mail Report 1</h1>";
$templateName = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['firstPage']) && !empty($_POST['templateName'])){
    $templateContent = $conn->real_escape_string($_POST['firstPage']);
    $templateName = test_input($_POST['templateName']);
    if(isset($_GET['id'])){ //did we edit?
      $templateID = intval($_GET['id']);
      $conn->query("UPDATE $mailReportsTable SET name = '$templateName', htmlMail = '$templateContent' WHERE id = $templateID");
    } else { //or create a new one
      $conn->query("INSERT INTO $mailReportsTable (name, htmlMail) VALUES('$templateName', '$templateContent')");
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
  $result = $conn->query("SELECT * FROM $mailReportsTable WHERE id = $templateID");
  if($result && ($row = $result->fetch_assoc())){
    $templateContent = $row['htmlMail'];
    $templateName = $row['name'];
  } else {
    $templateContent = "Do not fiddle with the URL. Thank you.";
  }
}
?>

<form method="POST">
  <div class="container">
    <div class="col-xs-10">
      <input type="text" class="form-control" placeholder="Name of Template (Required)" name="templateName" value="<?php echo $templateName; ?>" />
    </div>
    <div class="col-xs-2 text-right">
      <a href='reportEmails.php' class='btn btn-info btn-block'>Return <i class='fa fa-arrow-right'></i></a>
    </div>
  </div>
  <br>
  <div class="container">

    <div class="col-sm-10" id="droppableDiv" style="max-width:780px;">
      <textarea id="firstPage" name="firstPage"><?php echo $templateContent; ?></textarea>
    </div>

    <div class="col-sm-2">
      <br> Drag and Drop: <br><br>
      <div class="draggable repeat">Repeat</div>
      <div class="draggable">Name</div>
      <div class="draggable">Infotext</div>
      <div class="draggable">Date</div>
      <div class="draggable">From</div>
      <div class="draggable">To</div>
      <div class="draggable">Client</div>
      <div class="draggable">Project</div>
    </div>
  </form>
</div>

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

$(function() {
    $(".draggable").draggable({
        helper: 'clone',
        start: function(event, ui) {
            $(this).fadeTo('fast', 0.5);
        },
        stop: function(event, ui) {
            $(this).fadeTo(0, 1);
        }
    });

    $("#droppableDiv").droppable({
      hoverClass: 'active',
      drop: function(event, ui) {
        var inText = '[' + $(ui.draggable).text().toUpperCase()  + ']';

        if($(ui.draggable).text() == "Repeat"){
          inText += "<br>[REPEAT END]";
        }
        tinymce.activeEditor.execCommand('mceInsertContent', false, inText);
      }
    });
});
</script>

<?php include 'footer.php'; ?>
