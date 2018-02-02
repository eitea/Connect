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
}?>

<link href="plugins/homeMenu/fileUpload.css" rel="stylesheet" />
<style>
.dropbox{
    width: 100%;
    height: 100vh;
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
    position: absolute;
    cursor: pointer;
    background-color: rgb(238, 238, 238);
    min-width: 160px;
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
.dropDiv button {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
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
        }
    }

}



?>
<div id="title" class="page-header"><h3><?php echo $lang['YOUR_ARCHIVE']; ?>
</h3><div class="page-header-button-group">
    <button id="dropbtn" onblur="setTimeout(function(){document.getElementById('dropDiv').classList.remove('show');},100);" onclick="document.getElementById('dropDiv').classList.toggle('show');" type="button" class="btn btn-default" title="New..."><i class="fa fa-plus"></i><?php echo $lang['NEW'] ?><i class="fa fa-caret-down"></i></button><div class="dropDiv" id="dropDiv" >
    <a >Dokument</a>
    <a data-toggle="modal" data-target="#new-folder">Folder</a>
    </div>
  </div><input id="currentFolder" value="<?php echo $folderID ?>" style="visibility: hidden; height:1px; width:1px;"/></div>
<div id="dropbox" class="dropbox" >
    <div class="content" >
    <table class="table" >
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
        ->table archive_folders with FIRST being root without a parentfolder ** No AUTO_INCREMENT !!!DONE
        ->table archive_savedfiles with files like the shared ones, but also the information about with folder !!!DONE
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
    </form>
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
        //console.log(data);
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
        //console.log(parent);
        if(data[0]){
            for(i=0;i<data[0].length;i++){
                rows[i].className = "file";
                rows[i].children[0].innerHTML = "<i class='fa fa-file'></i>";
                rows[i].children[1].innerHTML = data[0][i]['name'];
                rows[i].children[2].innerHTML = new Date(data[0][i]['uploaddate']).toLocaleString();
                rows[i].children[3].innerHTML = data[0][i]['filesize'] + " KB";
                rows[i].children[4].innerHTML = "<button class='btn btn-default'><i class='fa fa-download'></i>";
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
                rows[i+data[0].length].children[4].innerHTML = "<button class='btn btn-default'><i class='fa fa-download'></i>";
            }
        }
        
        rows.sort(function(a,b){
            var x = a.children[1].innerHTML;
            var y = b.children[1].innerHTML;
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
</script>
<?php include dirname(__DIR__) . '/footer.php'; ?>
