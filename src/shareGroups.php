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
  var filesGotDropped = false;
  var droppedFiles = false;
  var droppedFiles2 = false;
  document.onreadystatechange = () => {
  if (document.readyState === 'complete') {
  
  var isAdvancedUpload = function() {
  var div = document.createElement('div');
  return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;}();
  if(isAdvancedUpload){
    var $formGroup = $('#fileBox');
    var fileForm = $('#dropBox');
    $formGroup.addClass('has-advanced-upload');
    fileForm.addClass('has-advanced-upload');
    

  fileForm.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
  })
  .on('dragover dragenter', function() {
    fileForm.addClass('is-dragover');
  })
  .on('dragleave dragend drop', function() {
    fileForm.removeClass('is-dragover');
  })
  .on('drop', function(e) {
  droppedFiles = e.originalEvent.dataTransfer.files; // the files that were dropped
  
  showFiles( droppedFiles );
});

  $formGroup.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
  })
  .on('dragover dragenter', function() {
    $formGroup.addClass('is-dragover');
  })
  .on('dragleave dragend drop', function() {
    $formGroup.removeClass('is-dragover');
  })
  .on('drop', function(e) {
  droppedFiles2 = e.originalEvent.dataTransfer.files; // the files that were dropped
  console.log(droppedFiles2);
  console.log("TEST");
  filesGotDropped = true;
  showFiles(droppedFiles2);
});
  
  var $input    = $formGroup.find('input[type="file"]'),
    $label    = $formGroup.find('label'),
    showFiles = function(files) {
      $label.text(files.length > 1 ? ($input.attr('data-multiple-caption') || '').replace( '{count}', files.length ) : files[ 0 ].name);
    };
    $input.on('change', function(e) {
    showFiles(e.target.files);
  });
  var $input2    = fileForm.find('#addFile'),
    $label2    = fileForm.find('label'),
    showFiles2 = function(files) {
      $label2.text(files.length > 1 ? ($input.attr('data-multiple-caption') || '').replace( '{count}', files.length ) : files[ 0 ].name);
    };
    $input2.on('change', function(e) {
    showFiles2(e.target.files);
  });





  
}
  }
};
function handleCancel(){ // NICHT GEFUNDEN?!?!?!!
    var $form = $('#fileBox');
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
    echo '<button type="button" data-toggle="modal" data-target="#edit-group" class="btn btn-default" title="Bearbeiten" onclick="editGroup(this,'. $row['id'] .')"><i class="fa fa-pencil"></i></a>';
    echo '<button type="button" name="setSelect" value="'.$row['id'].'" data-toggle="modal" data-target="#send-as-mail" class="btn btn-default" title="Senden.."><i class="fa fa-envelope-o"></i></button>';
    echo '<button onclick="location.href=\'../archive/delete?n=' . $row['id'] . '\'" type="button" class="btn btn-default"  title="Löschen"><i class="fa fa-trash-o"></i></a>';
    echo '</td>';
    echo '</tr>';
  }
?>
</table>

<form method="POST" enctype="multipart/form-data">
  <div class="modal" id="edit-group">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header h4"><?php echo "Gruppe". " " . $lang['EDIT'];  ?></div>
      <div class="modal-body">
        <label>Name der Gruppe</label>
        <input id="editName" style="margin-bottom: 2%" type="text" class="form-control" name="edit_groupName" autofocus/>
        <input id="groupID" style="visibility: hidden; width:1px; height:1px;" type="number" name="groupID"/>
        <label>Lebenszeit der Gruppe</label>
        <input class="radioChoose" type="radio" name="ttlE" value="1">Ein Tag</input>
        <input class="radioChoose" type="radio" name="ttlE" value="7">Eine Woche</input>
        <input class="radioChoose" type="radio" name="ttlE" value="30">Ein Monat</input>
        <br>
        <table class="table">
          <thead><tr>
          <th>Name</th>
          <th>Erstellungsdatum</th>
          <th></th>
          </tr></thead>
          <tbody id="fileTable" >
          </tbody>
        </table>
        <div class="modal-footer">
        <div style="text-align: left; margin-bottom: -35px">
        <button type="button" class="btn btn-warning" id="openNewFile" data-toggle="modal" data-target="#new-file" ><i class="fa fa-plus"></i></button>
        </div>
        <div>
        <button type="button" class="btn btn-default" onclick="handleCancel()" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="editGroup" onclick="finishEditGroup(event)"><?php echo $lang['EDIT']; ?></button>
        </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="new-file">
      <div class="modal-dialog modal-content modal-sm" >
        <div class="modal-header h4"><?php echo "Datei". " ".$lang['ADD']; ?></div>
        <div class="modal-body" >
          <div class="fileBox" id="dropBox" ondrop="guideFiles(event)" ondragover="dragover(event)" ondragend="dragend(event)">
            <input class="fileInput" type="file" name="files[]" id="addFile" onChange="uploadFiles(this)" data-multiple-caption="{count} files uploaded" multiple />
            <label class="lbl" for="addFile" id="lblNewFile"><strong id="clickHere">Datei(en) auswählen</strong><span class="dragndrop"> oder hier hin ziehen</span>.</label>          
          </div>
          <div class="modal-footer" >
            <button type="button" onClick="fade(this)" class="btn btn-default">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="new-group">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header h4"><?php echo "Gruppe". " " . $lang['ADD'];  ?></div>
      <div class="modal-body">
        <?php include 'misc/select_company.php';?>
        <label>Name der Gruppe</label>
        <input style="margin-bottom: 2%" type="text" class="form-control" name="add_groupName" id="add_groupName" autofocus/>
        <label>Lebenszeit der Gruppe</label>
        <input class="radioChoose" type="radio" name="ttl" value="1" checked>Ein Tag</input>
        <input class="radioChoose" type="radio" name="ttl" value="7">Eine Woche</input>
        <input class="radioChoose" type="radio" name="ttl" value="30">Ein Monat</input>
        <div id="fileBox" class="fileBox">
          <input class="fileInput" type="file" name="files" id="file" data-multiple-caption="{count} files selected" multiple />
          <label class="lbl" for="file"><strong id="clickHere">Datei(en) auswählen</strong><span class="dragndrop"> oder hier hin ziehen</span>.</label>          
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-default" onclick="handleCancel()" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" onclick="createGroup(event)" name="addGroup"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
</form>








<script>
  function finishEditGroup(evt){
    editGroup(evt,document.getElementById('groupID').value);
  }

  function editGroup(evt, groupID){
    var ttl = null;
    if(document.querySelector('input[name = "ttlE"]:checked')){
      ttl = document.querySelector('input[name = "ttlE"]:checked').value
    }
    $.ajax({
      type: "POST",
      url: "../misc/sharedfiles",
      data: { groupID: groupID,
              'function': 'editGroup',
              'editName': document.getElementById('editName').value,
              'ttl': ttl}
    }).done(function(info){
      document.getElementById('editName').value = info;
      info = JSON.parse(info);
      document.getElementById('editName').value = info[0]['name'];
      document.getElementById('groupID').value = groupID;
      var br = document.createElement("br"); 
      var tbody = document.getElementById("fileTable");
      while(tbody.firstChild) tbody.removeChild(tbody.firstChild);
      for(var i=1;i<info.length;i++){
        var tableRow = document.createElement("tr");
        var tableName = document.createElement("td");
        var tableDate = document.createElement("td");
        var tableButton = document.createElement("td");
        var button = document.createElement("button");
        button.setAttribute("class","btn btn-default");
        button.setAttribute("title","Löschen");
        button.setAttribute("type","button");
        button.setAttribute("onclick","deleteFile(this,"+info[i]['id']+")");
        button.appendChild(document.createElement("i"));
        button.firstElementChild.setAttribute("class","fa fa-trash-o");
        tableName.appendChild(document.createTextNode(info[i]['name']));
        tableDate.appendChild(document.createTextNode(info[i]['uploaddate']));
        tableButton.appendChild(button);
        tableRow.appendChild(tableName);
        tableRow.appendChild(tableDate);
        tableRow.appendChild(tableButton);
        tableRow.appendChild(tableButton);
        document.getElementById("fileTable").appendChild(tableRow);
        document.getElementById("fileTable").appendChild(br);
      }
    });
  }           

  function deleteFile(evt,fileID){
    $.ajax({
      type: "POST",
      url: "../misc/sharedfiles",
      data: { fileID: fileID,
              'function': 'deleteFile'}
    }).done(function(groupID){
      if(groupID)editGroup(null,groupID);
      
    });
  }

  function uploadFiles(evt){
    //evt.preventDefault();
    var formData = new FormData();
    var fileIndex = [];
    for(var i = 0;i<document.getElementById('addFile').files.length;i++){
      formData.append('file'+i,document.getElementById('addFile').files[i]); 
    }
    formData.append('amount',document.getElementById('addFile').files.length);
    formData.append('userID',<?php echo $userID ?>);
    formData.append('function','sendFiles');
    formData.append('fileIndex',fileIndex);
    formData.append('groupID',document.getElementById('groupID').value);
    $.ajax({
      url: '../misc/sharedfiles',
      type: 'POST',
      async: true,
      data: formData,
      cache: false,
      contentType: false,
      encType: 'multipart/form-data',
      processData: false,
      success: function(response){
        //alert(response);
        editGroup(null,response)
      }
    });
  }

  function sendFile(file){
    var formData = new FormData();
    formData.append('function','sendFiles');
    formData.append('file0',file);    
    formData.append('amount',1);
    formData.append('userID',<?php echo $userID ?>)
    formData.append('groupID',document.getElementById('groupID').value);
    $.ajax({
      url: '../misc/sharedfiles',
      type: 'POST',
      async: true,
      data: formData,
      cache: false,
      contentType: false,
      encType: 'multipart/form-data',
      processData: false,
      success: function(response){
        //alert(response);
        editGroup(null,response)
      }
    });
  }

  function createGroup(evt){
    var formData = new FormData();
    var amount = 0;
    if(filesGotDropped){
      for(var i = 0;i<droppedFiles2.length;i++){
        formData.append('file'+i,droppedFiles2[i]);
      }
      amount = droppedFiles2.length;
      console.log(droppedFiles2);
    }else{
      for(var i = 0;i<document.getElementById('file').files.length;i++){
        formData.append('file'+i,document.getElementById('file').files[i]); 
      }
      amount = document.getElementById('file').files.length;
      console.log(document.getElementById('file').files);
    }   
    formData.append('amount',amount);
    formData.append('function','addGroup');
    formData.append('userid',<?php echo $userID ?>);
    formData.append('ttl',document.querySelector('input[name = "ttl"]:checked').value);
    formData.append('add_groupName',document.getElementById('add_groupName').value);
    formData.append('filterCompany',document.getElementsByName('filterCompany')[0].value);
    $.ajax({
      url: '../misc/sharedfiles',
      type: 'POST',
      async: true,
      data: formData,
      cache: false,
      contentType: false,
      encType: 'multipart/form-data',
      processData: false,
      success: function(res){
        //alert(res);
        location.reload();
      }
    });
  }

  function guideFiles(evt){
    evt.preventDefault();
    var dt = evt.dataTransfer;
    if(dt.items){
      for(var i=0;i<dt.items.length;i++){
        if(dt.items[i].kind=="file"){
          var f = dt.items[i].getAsFile();
          sendFile(f);
        }
      }
    }else{
      for(var i=0;i<dt.files.length;i++){
        sendFile(dt.files[i]);
      }
    }
  }

  function fade(evt){
    var btn = document.getElementById('openNewFile');
    btn.click();
    document.getElementById('lblNewFile').innerHTML = "Datei auswählen oder hier hin ziehen.";
    //var backdrop = document.getElementsByClassName('modal-backdrop fade in')[0];
    //var body = document.getElementsByTagName('body')[0];
    //body.classList.remove("modal-open");
    //backdrop.parentNode.removeChild(backdrop);
    //div.setAttribute('style','display: hidden');
  }

  function dragover(evt){
    //evt.preventDefault();
  }

  function dragend(evt){
    var dt = evt.dataTransfer;
    if (dt.items) {
      for (var i = 0; i < dt.items.length; i++) {
        dt.items.remove(i);
      }
    } else {
      ev.dataTransfer.clearData();
    }
  }
</script>



<?php require 'footer.php'; ?>
