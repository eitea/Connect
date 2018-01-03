<?php require 'header.php'; enableToDSGVO($userID); 
require 'vendor/autoload.php';
require __DIR__."/connection.php";

$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => 0); //set_filter requirement

if(isset($_GET['custID']) && is_numeric($_GET['custID'])){
  $filterings['client'] = test_input($_GET['custID']);
}
if(isset($_POST['filterClient'])){
  $filterings['client'] = intval($_POST['filterClient']);
}
?>
<link href="plugins/homeMenu/fileUpload.css" rel="stylesheet" />
<div class="page-header"><h3><?php echo $lang['SHARE']; ?><div class="page-header-button-group">
  <button type="button" data-toggle="modal" data-target="#new-group" class="btn btn-default" title="New..."><i class="fa fa-plus"></i></a>
</div></h3></div>

<script>
  document.onreadystatechange = () => {
  if (document.readyState === 'complete') {
    




    var isAdvancedUpload = function() {
  var div = document.createElement('div');
  return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;}();
  if(isAdvancedUpload){
    var $form = $('.fileBox');
    $form.addClass('has-advanced-upload');
    var droppedFiles = false;

  $form.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
  })
  .on('dragover dragenter', function() {
    $form.addClass('is-dragover');
  })
  .on('dragleave dragend drop', function() {
    $form.removeClass('is-dragover');
  })
  .on('drop', function(e) {
  droppedFiles = e.originalEvent.dataTransfer.files; // the files that were dropped
  showFiles( droppedFiles );
});
  
  var $input    = $form.find('input[type="file"]'),
    $label    = $form.find('label'),
    showFiles = function(files) {
      $label.text(files.length > 1 ? ($input.attr('data-multiple-caption') || '').replace( '{count}', files.length ) : files[ 0 ].name);
    };
    $input.on('change', function(e) {
    showFiles(e.target.files);
  });




  
}
  }
};
function handleCancel(){ // NICHT GEFUNDEN?!?!?!!
    var $form = $('.fileBox');
    var $input    = $form.find('input[type="file"]'),
    $label    = $form.find('label'),
    $name     = $form.find('input[type="text"]'),
    $radio    = $form.find('input:radio[name=ttl]');
    $label.text("Datei auswählen oder hier hin ziehen.");
    $input.val("");
    $name.val("");
    $radio.filter('[value=1]').prop('checked',true);
  }

  


</script>

<?php

 

   $s3 = new Aws\S3\S3Client($s3config);
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['addGroup']) && !empty($_POST['add_groupName']) && !empty($_FILES) && !empty($_POST['filterCompany'])){
      $companyID = intval($_POST['filterCompany']);
      $name = test_input($_POST['add_groupName']);
      $radio = test_input($_POST['ttl']);
      $url = hash('whirlpool',random_bytes(10));
      try{
      $conn->query("INSERT INTO sharedgroups VALUES (null,'$name', null, $radio, '$url', ".$_SESSION['userid'].", NULL, $companyID)");
      $groupID = $conn->insert_id;
      foreach($_FILES['files']['error'] as $key => $error){
        if($error == UPLOAD_ERR_OK){
          $filename = pathinfo($_FILES['files']['name'][$key], PATHINFO_FILENAME);
          $filetype = pathinfo($_FILES['files']['name'][$key], PATHINFO_EXTENSION);
          $filesize = $_FILES['files']['size'][$key];
          $hashkey = hash('md5',random_bytes(10));
          $conn->query("INSERT INTO sharedfiles VALUES (null,'$filename', '$filetype', ".$_SESSION['userid'].", $groupID, '$hashkey', $filesize, null)");
          $s3->putObject(array(
          'Bucket' => 'sharedFiles',
          'Key' => $hashkey,
          'SourceFile' => $_FILES['files']['temp_name'][$key]
          ));
          
        }
          
      }

    }catch(Exception $e){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
    }
      if($conn->error){
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
      } else {
        ?>
        <script>
        location.reload();
        </script>
        <?php
      }
    }
  }
?>

<table class="table">
<thead><tr>
<th>Name</th>
<th>Mandant</th>
<th>Vorschau</th>
<th>Restzeit</th>
<th></th>
</tr></thead>
<?php
  $doc_selects = '';
  $result = $conn->query("SELECT s.name AS name, s.uri AS uri, s.ttl AS ttl, s.id AS id, s.dateOfBirth AS dateOfBirth, c.name AS company FROM sharedGroups s JOIN companyData c ON s.company = c.id WHERE s.owner = $userID");
  while($row = $result->fetch_assoc()){
    echo '<tr>';
    echo '<td>'.$row['name'].'</td>';
    echo '<td>'.$row['company'].'</td>';
    $link = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $link = explode('/', $link);
    array_pop($link);
    $link = implode('/', $link) . "/files?n=".$row['uri'];
    echo '<td><a target="blank_" href='.$link.'> Click Me </a></td>';
    $daysLeft = ((int)(($row['ttl']*86400 - (strtotime(date('Y-m-d H:i:s')) - strtotime($row['dateOfBirth'])))/86400));
    $days = ' Tag';
    if($daysLeft>1) $days = $days . "e";
    echo '<td>'.$daysLeft. $days .' </td>';
    echo '<td>';
    echo '<button type="button" data-toggle="modal" data-target="#edit-group" class="btn btn-default" title="Bearbeiten"><i class="fa fa-pencil"></i></a>';
    echo '<button type="button" name="setSelect" value="'.$row['id'].'" data-toggle="modal" data-target="#send-as-mail" class="btn btn-default" title="Senden.."><i class="fa fa-envelope-o"></i></button>';
    echo '<button onclick="location.href=\'../archive/delete?n=' . $row['id'] . '\'" type="button" class="btn btn-default"  title="Bearbeiten"><i class="fa fa-trash-o"></i></a>';
    echo '</td>';
    echo '</tr>';
  }
?>
</table>


<form method="POST" multipart="" enctype="multipart/form-data">
  <div class="modal fade" id="new-group">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header h4"><?php echo "Gruppe". " " . $lang['ADD'];  ?></div>
      <div class="modal-body">
        <?php include 'misc/select_company.php';?>
        <label>Name der Gruppe</label>
        <input style="margin-bottom: 2%" type="text" class="form-control" name="add_groupName" autofocus/>
        <label>Lebenszeit der Gruppe</label>
        <input class="radioChoose" type="radio" name="ttl" value="1" checked>Ein Tag</input>
        <input class="radioChoose" type="radio" name="ttl" value="7">Eine Woche</input>
        <input class="radioChoose" type="radio" name="ttl" value="30">Ein Monat</input>
        <div class="fileBox">
          <input class="fileInput" type="file" name="files[]" id="file" data-multiple-caption="{count} files selected" multiple />
          <label class="lbl" for="file"><strong id="clickHere">Datei(en) auswählen</strong><span class="dragndrop"> oder hier hin ziehen</span>.</label>          
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-default" onclick="handleCancel()" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="addGroup"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
</form>


<?php require 'footer.php'; ?>
