<?php require 'header.php'; ?>
<div class="page-header"><h4>E-Mail Vorlagen
 <div class="page-header-button-group"><form method="POST" style="display:inline"><button type="submit" name="create_report" class="btn btn-default"><i class="fa fa-plus"></i></button></form>
</h4></div>
<?php
if(empty($_GET['n']) || !in_array($_GET['n'], $available_companies)){ //eventually STRIKE
    echo "Invalid Access.";
    include 'footer.php';
    die();
}
$cmpID = intval($_GET['n']);

$action = $action_id = '';
if(isset($_POST['create_report'])){ $action = 'new'; }
if(isset($_POST['edit_report'])){ $action = 'edit'; $action_id = intval($_POST['edit_report']); }

if($action && !empty($_POST['report_content']) && !empty($_POST['report_name'])){
    $templateName = test_input($_POST['report_name']);
    $templateContent = $_POST['report_content'];
    if($action == 'new'){
        $stmt = $conn->prepare("INSERT INTO templateData (name, htmlCode, type, userIDs) VALUES(?, ?, 'document', $cmpID)");        
    } else {
        $stmt = $conn->prepare("UPDATE templateData SET name = ?, htmlCode = ? WHERE id = $action_id");
    }
    $stmt->bind_param("ss", $templateName, $templateContent);
    $stmt->execute();
    
    $action = '';
    if($conn->error){
        echo '<div class="alert alter-danger"><a href="" data-dismiss="alert" class="close">&times;</a></div>';
    } else {
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
} elseif(!empty($_POST['delete_report'])){
    $val = intval($_POST['delete_report']);
    $conn->query("DELETE FROM templateData WHERE id = $val");
    if($conn->error){
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
    }
}
?>

<form method="POST">
<table class="table table-hover">
    <thead><tr>
    <th>Name</th>
    <th></th>
    </tr></thead>
    <tbody>
    <?php
    $result = $conn->query("SELECT * FROM templateData WHERE type = 'document' AND userIDs = $cmpID"); //tiny misuse/recycling of table
    while($row = $result->fetch_assoc()){
        echo '<tr>';
        echo '<td>'.$row['name'].'</td>';        
        echo '<td>';
        echo '<button type="submit" name="delete_report" value="'.$row['id'].'" class="btn btn-default" title="Delete"><i class="fa fa-trash-o"></i></button> ';
        echo '<button type="submit" name="edit_report" value="'.$row['id'].'" class="btn btn-default" title="Edit" ><i class="fa fa-pencil"></i></button>';
        echo '</td>';
        echo '</tr>';
    }
    ?>
    </tbody>
</table><br>
</form>

<br><hr><br>
<?php if($action): ?>
<form method="POST">

<?php
if($action == 'edit'){
    echo '<input type="hidden" name="edit_report" value="'.$action_id.'" />';
    $result = $conn->query("SELECT htmlCode, name FROM templateData WHERE id = $action_id");
    $row = $result->fetch_assoc();
    $templateContent = $row['htmlCode'];
    $templateName = $row['name'];
} else {
    echo '<input type="hidden" name="create_report" />';
    $templateContent = $templateName = '';
}
?>
<div class="row">
    <div class="col-sm-1 text-right"><strong>Name</strong></div>
    <div class="col-xs-6"><input type="text" class="form-control" placeholder="Name of Template (Required)" name="report_name" value="<?php echo $templateName; ?>" /></div>
    <div class="col-sm-3"><button type="submit" name="save" class="btn btn-warning">Speichern</button></div>
</div>
<div class="row">
    <div class="col-sm-1 text-right"><strong>Inhalt</strong></div>
    <div class="col-sm-9" style="max-width:790px;"><textarea name="report_content"><?php echo $templateContent; ?></textarea></div>
    <div class="col-sm-2">
      <br>Click to Insert: <br><br>
      <button type="button" class="btn btn-warning btn-block btn-insert-text" value='[LINK]' >URL</button>
      <button type="button" class="btn btn-warning btn-block btn-insert-text" value='[FIRSTNAME]' ><?php echo $lang['FIRSTNAME']; ?></button>
      <button type="button" class="btn btn-warning btn-block btn-insert-text" value='[LASTNAME]' ><?php echo $lang['LASTNAME']; ?></button>
    </div>
</div>
</form>
<?php endif; ?>

<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
tinymce.init({
  selector: 'textarea',
  height: 1000,
  menubar: false,
  plugins: [
    'advlist autolink lists link image charmap print preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table contextmenu paste code'
  ],
  toolbar: 'undo redo | styleselect | outdent indent | bullist table',
  relative_urls: false,
  content_css: '../plugins/homeMenu/template.css'
});

$(".btn-insert-text").click(function (){
    var inText = $(this).val();
    tinymce.activeEditor.execCommand('mceInsertContent', false, inText);
});
</script>

<?php include 'footer.php'; ?>