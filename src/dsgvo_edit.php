<?php include 'header.php'; enableToDSGVO($userID); ?>

<?php
$docID = 0;
if(!empty($_GET['d'])){
    $docID = intval($_GET['d']);
    $result = $conn->query("SELECT * FROM documents WHERE id = $docID AND companyID IN (".implode(', ', $available_companies).")");
    if($result && ($row = $result->fetch_assoc())){
        $documentContent = $row['txt'];
        $name = $row['name'];
        $version = $row['version'];
        $cmpID = $row['companyID'];
    } else {
        $docID = 0;
    }
}

if(!$docID){
    echo "Invalid access";
    include 'footer.php';
    die();
}

//TODO: overwrite changes if doc has not been sent to anyone yet, else create new
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['documentContent']) && !empty($_POST['templateName']) && !empty($_POST['templateVersion'])){
    $documentContent = $_POST['documentContent'];
    $name = test_input($_POST['templateName']);
    $newVersion = test_input($_POST['templateVersion']);
    if($newVersion == $version){
        $stmt = $conn->prepare("UPDATE documents SET txt = ?, name = ?, version = ? WHERE id = $docID");
    } else {
        $stmt = $conn->prepare("INSERT INTO documents (txt, name, version, companyID) VALUES(?, ?, ?, $cmpID)");
    }
    $stmt->bind_param("sss", $documentContent, $name, $version);
    $stmt->execute();
    $stmt->close();
    if($conn->error){
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
  } else {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
  }
}
?>
<br>
<form method="POST">
    <div class="page-header"><h3><?php echo $lang['TEMPLATES']; ?><div class="page-header-button-group"><button type="submit" name="save" class="btn btn-default blinking"><i class="fa fa-floppy-o"></i></button></div></h3></div>
    <div class="row">
        <div class="col-xs-8"><input type="text" class="form-control" placeholder="Name of Template (Required)" name="templateName" value="<?php echo $name; ?>" /></div>
        <div class="col-sm-2"><input type="text" class="form-control" name="templateVersion" value="<?php echo $version; ?>" placeholder="Version" /></div>
        <div class="col-xs-2"><a href='documents?n=<?php echo $cmpID; ?>' class='btn btn-info btn-block'><?php echo $lang['RETURN']; ?> <i class='fa fa-arrow-right'></i></a></div>
    </div>
    <br><br>
    <div class="row">
        <div class="col-sm-10" style="max-width:780px;"><textarea name="documentContent"><?php echo $documentContent; ?></textarea></div>
        <div class="col-sm-2">
            <br><br>Click to Insert: <br><br>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[LINK]'>Url des Dokumentes</button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[ANREDE]'>Herr/ Frau</button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[FIRSTNAME]'>Vorname</button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[LASTNAME]'>Nachname</button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[Companyname]'>Firmenname</button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[Companystreet]'>Straße</button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[Companyplace]'>Ort</button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[Companypostcode]'>PLZ</button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[CUSTOMTEXT]'>Freitext</button>            
        </div>
    </div>
</form>

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
  content_css: '../plugins/homeMenu/template.css',
  init_instance_callback: function (editor) {
    editor.on('keyup', function (e) {
        var blink = $('.blinking');
        blink.attr('class', 'btn btn-warning blinking');
        setInterval(function() {
        blink.fadeOut(500, function() {
            blink.fadeIn(500);
        });
        }, 1000);
    });
  }
});

$('.btn-insert').click(function() {
    var inText = this.value;
    tinymce.activeEditor.execCommand('mceInsertContent', false, inText);
});
</script>

<?php include 'footer.php'; ?>
