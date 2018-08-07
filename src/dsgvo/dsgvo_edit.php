<?php include dirname(__DIR__) . '/header.php';
?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
$docID = 0;
if(!empty($_GET['d'])){
    $docID = intval($_GET['d']);
    $result = $conn->query("SELECT * FROM documents WHERE id = $docID AND companyID IN (".implode(', ', $available_companies).") LIMIT 1");
    if($result && ($row = $result->fetch_assoc())){
        $documentContent = secure_data('DSGVO', $row['txt'], 'decrypt', $userID, $privateKey);
        $name = secure_data('DSGVO', $row['name'], 'decrypt');
        $version = $row['version'];
        $cmpID = $row['companyID'];
        $isBase = $row['isBase'];
        $doc_ident = $row['docID'];
    } else {
        $docID = 0;
    }
}

if(!$docID){
    echo "Invalid access";
    include dirname(__DIR__) . '/footer.php';
    die();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['documentContent']) && !empty($_POST['templateName']) && !empty($_POST['templateVersion'])){
    $documentContent = secure_data('DSGVO', $_POST['documentContent']);
    $name = secure_data('DSGVO', test_input($_POST['templateName']));
    $newVersion = test_input($_POST['templateVersion']);
    $stmt = $conn->prepare("UPDATE documents SET txt = ?, name = ?, version = ? WHERE id = $docID");
    $stmt->bind_param("sss", $documentContent, $name, $version);
    $stmt->execute();
    $stmt->close();
    if($conn->error){
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
    $documentContent = $_POST['documentContent'];
    $name = $_POST['templateName'];
  } elseif(!empty($_POST['documentContent'])) {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
  }
}
?>
<br>
<form method="POST">
    <div class="page-header"><h3><?php echo $name ?><div class="page-header-button-group"><button type="submit" name="save" class="btn btn-default blinking"><i class="fa fa-floppy-o"></i></button></div></h3></div>
    <div class="row">
        <?php if($isBase == 'FALSE'): ?>
        <div class="col-sm-8"><label><?php echo mc_status(); ?> Name</label><input type="text" class="form-control" placeholder="Name of Template (Required)" name="templateName" value="<?php echo $name; ?>" /><br></div>
        <div class="col-sm-2"><label>Version</label><input type="text" class="form-control" name="templateVersion" value="<?php echo $version; ?>" placeholder="Version" /><br></div>
        <div class="col-sm-2"><label><?php echo $lang['DOCUMENTS']; ?></label><a href='documents?cmp=<?php echo $cmpID; ?>' class='btn btn-info btn-block'><?php echo $lang['RETURN']; ?> <i class='fa fa-arrow-right'></i></a><br></div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-10" style="max-width:780px;"><label><?php echo mc_status().' '.$name; ?></label><textarea name="documentContent"><?php echo $documentContent; ?></textarea></div>
        <div class="col-md-2">
            <br><br>Click to Insert: <br><br>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[LINK]'>URL</button>
            <!--button type="button" class="btn btn-warning btn-block btn-insert" value='[ANREDE]'>Herr/ Frau</button-->
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[FIRSTNAME]'><?php echo $lang['FIRSTNAME'] ?></button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[LASTNAME]'><?php echo $lang['LASTNAME'] ?></button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[Companyname]'><?php echo $lang['COMPANY_NAME'] ?></button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[Companystreet]'><?php echo $lang['STREET'] ?></button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[Companyplace]'><?php echo $lang['CITY'] ?></button>
            <button type="button" class="btn btn-warning btn-block btn-insert" value='[Companypostcode]'><?php echo $lang['PLZ'] ?></button>
        </div>
        <?php elseif(preg_match_all("/\[CUSTOMTEXT_\d+\]/", $documentContent, $matches)): ?>
        <div class="col-md-10 col-md-offset-1">
            <?php
            $res = $conn->query("SELECT id, identifier, content FROM document_customs WHERE doc_id = '$doc_ident' AND companyID = $cmpID ");
            $result = array();
            while($res && ($row = $res->fetch_assoc())){
                $result['identifier']['id'] = $row['id'];
                $result['identifier']['content'] = secure_data('DSGVO', $row['content'], 'decrypt');
            }

            for($i = 0; $i < count($matches[0]); $i++){
                $match = $matches[0][$i];
                //split off the document parts
                $split = explode($match, $documentContent, 2);
                echo $split[0];
                $documentContent = $split[1];
                //remove the braces for html & sql conform posting
                $match = substr($match, 1, -1);
                $customtext = isset($result[$match]) ? $result[$match]['content'] : '';
                if(isset($_POST['save']) && isset($_POST[$match])){
                    $customtext = secure_data('DSGVO', test_input($_POST[$match]));
                    if(isset($result[$match])){
                        $val = $result[$match]['id'];
                        $conn->query("UPDATE document_customs SET content = '$customtext' WHERE id = $val");
                    } else {
                        $conn->query("INSERT INTO document_customs (doc_id, companyID, identifier, content, status) VALUES ('$doc_ident', $cmpID, '$match', '$customtext', 'visible') ");
                    }
                    $customtext = secure_data('DSGVO', test_input($_POST[$match]), 'decrypt');
                }
                echo '<textarea name="'.$match.'" class="form-control" rows="3" >'.$customtext.'</textarea>';
            }
            ?>
        </div>
        <?php else: ?>
            <div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Dieses Dokument ist nicht editierbar.</div>
            <div class="col-sm-2"><a href='documents?cmp=<?php echo $cmpID; ?>' class='btn btn-info btn-block'><?php echo $lang['RETURN']; ?> <i class='fa fa-arrow-right'></i></a><br></div>
        <?php endif; ?>
    </div>
</form>

<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
tinymce.init({
  selector: 'textarea[name=documentContent]',
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

<?php include dirname(__DIR__) . '/footer.php'; ?>
