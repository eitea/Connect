<?php include dirname(__DIR__) . '/header.php';?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; 
$result = $conn->query("SELECT endpoint FROM archiveconfig");
if($result){
  $enabled = $result->fetch_assoc();
  if(!isset($enabled['endpoint'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Modul Nicht Aktiv <a href="../system/advanced"><strong>Hier Ändern</strong></a></div>';
    include dirname(__DIR__) . '/footer.php';
    return;
  }
}
$folderID = 0;
?>


<style>
.dropbox{
    width: 100%;
    height: 100%;
    z-index: 0;
    
}
.dropbox .info{
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translateX(-50%) translateY(-50%);
    font-size: 1.7em;
    font-weight: bold;
    z-index: 0;
    overflow: hidden;
}
.dropbox .content{
    z-index:1;
}

.highlight{
    background-color: gray;
}
.folder{
    cursor:pointer;
}
.folder:hover{
    background-color: rgba(188, 188, 188, 0.49);
}
.back{
    text-align: center;
    text-decoration: underline;
}
.showDrop{
    display:block;
}
.dropDiv{
    display: none;
    position: relative;
    cursor: pointer;
    background-color: rgb(238, 238, 238);
    min-width: 160px;
    max-width: 180px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
}
.dropDiv a:hover {background-color: #ddd}
.dropDiv a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}
.lbl{
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    font-weight: 100;
    cursor: pointer;
}
.lbl:hover{
    background-color: #ddd
}
.simpleBtnWrapper{
    float:left;
    overflow: hidden;
    
}
.fileInput {
	width: 0.1px;
	height: 0.1px;
	opacity: 0;
	overflow: hidden;
    position: absolute;
    
    
    z-index: -1;
    -webkit-border-radius: 0!important;
    -moz-border-radius: 0!important;
    border-radius: 0!important;
}
.hidden{
    width: 0.1px;
	height: 0.1px;
	opacity: 0;
	overflow: hidden;
    position: absolute;
}
</style>
<?php 
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['folderid'])){
        $folderID = intval($_POST['folderid']);
    }else{
        $folderID = 0;
    }
    if(isset($_POST['addFolder'])){
        $foldername = $_POST['folderName'];
        $parentID = intval($_POST['parentID']);
        $newFolderID = $conn->query("SELECT parent_folder FROM archive_folders WHERE userid = $userID");
        $newFolderID = $newFolderID->num_rows;
        $conn->query("INSERT INTO archive_folders VALUES($newFolderID,".intval($userID).",'$foldername',$parentID)");

        $test = array();
        $test[0] = $foldername;
        $test[1] = $parentID;
        $test[2] = $newFolderID;
        $test[3] = intval($userID);
        echo "<script>console.log(".json_encode($test).")</script>";

        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
            $folderID = $parentID;
        }else{
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
            $folderID = $parentID;
        }
    }elseif(isset($_POST['addDocument'])){
        $filename = $_POST['docName'];
        $body = $_POST['body'];
        $hashkey = hash('md5',random_bytes(10));
        $conn->query("INSERT INTO archive_savedfiles VALUES(null,'$filename','html',$folderID,".intval($userID).",'$hashkey',0,null,'FALSE')");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        }else{
            $conn->query("INSERT INTO archive_editfiles VALUES('$hashkey','$body',1)");
            if ($conn->error) {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
            }else{
                echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
            }
        }
    }

}



?>
<div id="title" class="page-header"><h3><?php echo $lang['YOUR_ARCHIVE']; ?>
</h3><div class="page-header-button-group">
    <div class="simpleBtnWrapper">
    <button onblur="setTimeout(function(){document.getElementById('dropDiv').classList.remove('show');},100);" onclick="document.getElementById('dropDiv').classList.toggle('show');" type="button" class="btn btn-default" title="New..."><i class="fa fa-plus"></i><?php echo $lang['NEW'] ?><i class="fa fa-caret-down"></i></button><div class="dropDiv" id="dropDiv" >
    <a data-toggle="modal" data-target="#new-document" onclick="clearDoc()" ><?php echo $lang['FILE'] ?></a>
    <a data-toggle="modal" data-target="#new-folder"><?php echo $lang['FOLDER'] ?></a>
    </div></div><div class="simpleBtnWrapper">
    <button onblur="setTimeout(function(){document.getElementById('dropUpload').classList.remove('show');},500);" onclick="document.getElementById('dropUpload').classList.toggle('show');" type="button" class="btn btn-default" title="Upload..."><i class="fa fa-arrow-up"></i><?php echo $lang['UPLOAD'] ?><i class="fa fa-caret-down"></i></button><div class="dropDiv" id="dropUpload" >
    <input class="fileInput" type="file" id="uploadFile" onChange="uploadFiles(this)" multiple ><label class="lbl" for="uploadFile" ><?php echo $lang['FILE'] ?></label></input>
    <input class="fileInput" type="file" id="uploadFolder" onChange="uploadFolder(this)" multiple ><label class="lbl" for="uploadFolder" ><?php echo $lang['FOLDER'] ?></label></input>
    </div></div>
  </div><input id="currentFolder" value="<?php echo $folderID ?>" style="visibility: hidden; height:1px; width:1px;"/></div>
<div id="dropbox" class="dropbox" >
    <div class="content" >
    <table class="table" id="fileTable">
        <thead>
            <tr>
                <td></td>
                <td><label>Name</label></td>
                <td><label>Upload Datum</label></td>
                <td><label>File Size</label></td>
                <td></td>
            </tr>
        </thead>
        <tbody id="tableContent">
        </tbody>
    </table>
    </div>
    <!--<span class="info" >Drop Files to Upload</span>-->
</div>
<!-- LOGIC 
    TODO: Every Folder is a new Table / first one should be root(No Folder)
        ->ALSO drag and drop for folders NOT FILES(?)
        ->rename - possability ?
        ->dynamic File_icons ?
        ->File_Size_Limit ?
        ->possibly cross-share function (?!)
-->

<form method="POST">
<div class="modal fade" id="new-folder">
    <div class="modal-dialog modal-content modal-sm">
      <div class="modal-header h4"><?php echo $lang['ADD']; ?>
      </div>
      <div class="modal-body">
        <label>Name</label>
        <input class="form-control" name="folderName"/>
        <input style="visibility:hidden;width:1px;height:1px;" value="<?php echo $folderID ?>" id="parentID" name="parentID" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="addFolder"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
</div>
</form>
<form method="POST">
<div class="modal fade" id="new-document">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header h4"><?php echo $lang['ADD']; ?>
      </div>
      <div class="modal-body">
        <label>Name</label>
        <input class="form-control" name="docName"/>
        <label>Inhalt</label>
        <textarea class="form-control Editor" name="body" maxlength="20000" ></textarea>
        <input style="visibility:hidden;width:1px;height:1px;" value="<?php echo $folderID ?>" id="parentID" name="folderid" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" id="addDoc" class="btn btn-warning " name="addDocument"><?php echo $lang['ADD']; ?></button>
        <button type="submit" id="editDoc" class="btn btn-warning " name="editDocument"><?php echo $lang['EDIT']; ?></button>
      </div>
    </div>
</div>
</form>
<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
    $("#dropbox").on({
        dragenter: function(event){
            event.preventDefault();  
            event.stopPropagation();
            console.log("dragenter");
            $("#dropbox").addClass('highlight');
        },
        dragleave: function(event){
            event.preventDefault();  
            event.stopPropagation();
            console.log("dragleave");
            $("#dropbox").removeClass('highlight');
        },
        drop: function(event){
            event.preventDefault();  
            event.stopPropagation();
            console.log(event);
        }
    });
    $(".folder").on({
        dragenter: function(event){
            event.preventDefault();  
            event.stopPropagation();
            console.log("dragenter");
            $(this).addClass('highlight');
        },
        dragleave: function(event){
            event.preventDefault();  
            event.stopPropagation();
            console.log("dragleave");
            $(this).removeClass('highlight');
        },
        dragstart: function(event){
            event.preventDefault();  
            event.stopPropagation();
            console.log("dragstart");
        },
        dragend: function(event){
            event.preventDefault();  
            event.stopPropagation();
            console.log("dragend");
        },
        drop: function(event){
            event.preventDefault();  
            event.stopPropagation();
            console.log(event);
        },
    });
    $(document).ready(function(){
        getCurrentFolderContent();
        
        // $("tbody").sortable({
        //     items: "> tr:not(:first)",
        //     appendTo: "parent",
        //     helper: "clone"
        // }).disableSelection();
        
        // $("#tabs ul li a").droppable({
        //     hoverClass: "drophover",
        //     tolerance: "pointer",
        //     drop: function(e, ui) {
        //         var tabdiv = $(this).attr("href");
        //         $(tabdiv + " table tr:last").after("<tr>" + ui.draggable.html() + "</tr>");
        //         ui.draggable.remove();
        //     }
        // });
        tinymce.init({
        selector: '.Editor',
        plugins: 'image code paste',
        relative_urls: false,
        paste_data_images: true,
        menubar: false,
        statusbar: false,
        height: 300,
        toolbar: 'undo redo | cut copy paste | styleselect | link image file media | code ',
        
        // enable title field in the Image dialog
        image_title: true,
        // enable automatic uploads of images represented by blob or data URIs
        automatic_uploads: true,
        // URL of our upload handler (for more details check: https://www.tinymce.com/docs/configure/file-image-upload/#images_upload_url)
        // images_upload_url: 'postAcceptor.php',
        // here we add custom filepicker only to Image dialog
        file_picker_types: 'file image media',
        // and here's our custom image picker
        file_picker_callback: function(cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', '*');
            input.onchange = function() {
                var file = this.files[0];
                var reader = new FileReader();
                reader.onload = function () {
                    // Note: Now we need to register the blob in TinyMCEs image blob
                    // registry. In the next release this part hopefully won't be
                    // necessary, as we are looking to handle it internally.
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                    console.log(reader.result.split(";")[0].split(":")[1]) //mime type
                    var base64 = reader.result.split(',')[1];
                    alert("Base64 size: "+base64.length+" chars")
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    // call the callback and populate the Title field with the file name
                    cb(blobInfo.blobUri(), { title: file.name, text:file.name,alt:file.name,source:"images/Question_Circle.jpg",poster:"images/Question_Circle.jpg" });
                };
                reader.readAsDataURL(file);
            };
            input.click();
        }
    });
    });
    function getCurrentFolderContent(){
        //console.log('start');
        var userid = <?php echo $userID; ?>;
        var currentFolder = document.getElementById("currentFolder").value;
        $.post("../archive/getFolderContent",{
            userid: userid,
            folderid: currentFolder
        },function(data){
            clearTable();
            //console.log(data);
            fillTable(JSON.parse(data));
        });
    }
    function clearTable(){
        var table = document.getElementById("tableContent");
        while(table.firstChild){
            table.removeChild(table.firstChild);
        }
    }
    function changeFolder(event){
        var id = event.target.parentElement.folderID;
        //console.log(event);
        if(event.target.parentElement.isBack){
            $("#title")[0].firstChild.innerText = ($("#title")[0].firstChild.innerText).substring(0,($("#title")[0].firstChild.innerText).lastIndexOf(">"));
        }else{
            var name = event.target.parentElement.folderName;
            $("#title")[0].firstChild.innerText = $("#title")[0].firstChild.innerText + " > " + name;
        }
            $("#currentFolder").val(id);
            $("#parentID").val(id);
            getCurrentFolderContent();
    }
    function fillTable(data){
        console.log(data);
        var rowcount = data[0].length + data[1].length;
        var parent = data[2];
        var rows = [];
        for(i = 0;i<rowcount;i++){
            var row = document.createElement("tr");
            var icon = document.createElement("td");
            var name = document.createElement("td");
            var upload_date = document.createElement("td");
            var file_size = document.createElement("td");
            var buttons = document.createElement("td");
            row.appendChild(icon);
            row.appendChild(name);
            row.appendChild(upload_date);
            row.appendChild(file_size);
            row.appendChild(buttons);
            rows.push(row);
        }
        
        if(data[0]){
            for(i=0;i<data[0].length;i++){
                data[0][i]['isS3']=="TRUE" ? isS3 = true : isS3 = false;
                rows[i].className = "file";
                if(isS3){rows[i].children[0].innerHTML = "<i class='fa fa-file'></i>";}else{rows[i].children[0].innerHTML = "<i class='fa fa-paperclip'></i>";}
                rows[i].children[1].innerHTML = data[0][i]['name'];
                rows[i].children[2].innerHTML = new Intl.DateTimeFormat('de-AT').format(new Date(data[0][i]['uploaddate']));
                rows[i].children[3].innerHTML = data[0][i]['filesize'] + " KB";
                if(isS3){
                    rows[i].children[4].innerHTML = "<a class='btn btn-default' download href='../private/files?n="+data[0][i]['hashkey']+"' ><i class='fa fa-download'></i></a>";
                }else{
                    rows[i].children[4].innerHTML = "<button class='btn btn-default' onclick='fillEditor("+data[0][i]['id']+")' data-toggle='modal' data-target='#new-document' ><i class='fa fa-edit'></i></button>";
                }
                rows[i].children[4].innerHTML = rows[i].children[4].innerHTML + "<button class='btn btn-default' onclick='deleteFile("+data[0][i]['id']+")' ><i class='fa fa-trash'></i></button>";
            }
        }
        if(data[1]){
            for(i=0;i<rowcount-data[0].length;i++){
                rows[i+data[0].length].className = "folder";
                rows[i+data[0].length].addEventListener("click",changeFolder);
                rows[i+data[0].length].folderName = data[1][i]['name'];
                rows[i+data[0].length].folderID = data[1][i]['folderid'];
                rows[i+data[0].length].isBack = false;
                rows[i+data[0].length].children[0].innerHTML = "<i class='fa fa-folder'></i>";
                rows[i+data[0].length].children[1].innerHTML = data[1][i]['name'];
                rows[i+data[0].length].children[2].innerHTML = "---";
                rows[i+data[0].length].children[3].innerHTML = "---";
                rows[i+data[0].length].children[4].innerHTML = "<a class='btn btn-default'><i class='fa fa-download'></i></a>";
                rows[i+data[0].length].children[4].innerHTML = rows[i+data[0].length].children[4].innerHTML + "<button class='btn btn-default' ><i class='fa fa-trash'></i></button>";
            }
        }
        
        rows.sort(function(a,b){
            var x = a.children[1].innerHTML.toLowerCase();
            var y = b.children[1].innerHTML.toLowerCase();
            if(x<y) {return -1;}
            if(y<x) {return 1;}
            return 0;
        });
        var table = document.getElementById("tableContent");
        if(parent!=="-1"){
            row = document.createElement("tr");
            row.className = "folder back";
            row.addEventListener("click",changeFolder);
            row.folderID = parent;
            row.isBack = true;
            back = document.createElement("td");
            back.colSpan = 5;
            back.innerText = "Zurück";
            row.appendChild(back);
            table.appendChild(row);
        }
        for(i = 0;i<rowcount;i++){
            table.appendChild(rows[i]);
        }
    }
    function uploadFiles(element){
        //Upload selected files
        console.log(element.files);
        //evt.preventDefault();
        var formData = new FormData();
        var fileIndex = [];
        for(var i = 0;i<element.files.length;i++){
        formData.append('file'+i,element.files[i]);
        }
        formData.append('amount',element.files.length);
        formData.append('userID',<?php echo $userID ?>);
        formData.append('function','addFile');
        formData.append('fileIndex',fileIndex);
        formData.append('folderid',document.getElementById('currentFolder').value);
        $.ajax({
        url: '../private/files',
        type: 'POST',
        async: true,
        data: formData,
        cache: false,
        contentType: false,
        encType: 'multipart/form-data',
        processData: false,
        success: function(response){
            //alert(response);
            console.log(response);
            getCurrentFolderContent();
        }
        });
    }
    function deleteFile(id){
        $.post("../private/files",{
            id: id,
            function: "deleteFile",
        },function(data){
            getCurrentFolderContent();
        });
    }
    function fillEditor(id){
        $.post("../private/files",{
            id: id,
            function: "getFileData",
        },function(data){
            var info = JSON.parse(data);
            console.log(data);
            $("[name='docName']").val(info[0]);
            tinymce.activeEditor.execCommand('mceSetContent', false, info[1]); 
            document.getElementById("addDoc").classList.toggle("hidden");
            document.getElementById("editDoc").classList.remove("hidden");
        });
    }
    function clearDoc(){
        $("[name='docName']").val('');
        tinymce.activeEditor.execCommand('mceSetContent', false, ''); 
        document.getElementById("addDoc").classList.remove("hidden");
        document.getElementById("editDoc").classList.toggle("hidden");
    }
</script>
<?php include dirname(__DIR__) . '/footer.php'; ?>
