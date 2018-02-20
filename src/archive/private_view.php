<?php include dirname(__DIR__) . '/header.php'; ?>
<?php
require dirname(__DIR__) . "/misc/helpcenter.php";
$enabled = false;
$result = $conn->query("SELECT endpoint FROM archiveconfig");
if ($result) {
    $enabled = isset($result->fetch_assoc()['endpoint']);
}
$folderID = 0;
?>
<link href="plugins/bootstrap/css/bootstrap-treeview.min.css" rel="stylesheet"/>
<script src="plugins/bootstrap/js/bootstrap-treeview.min.js"></script>
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
        display: block;
    }
    .dropDiv{
        display: none;
        position: absolute;
        background-color: #f1f1f1;
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
        position: relative;
        display: inline-block;
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['folderid'])) {
        $folderID = intval($_POST['folderid']);
    } else {
        $folderID = 0;
    }
    if (isset($_POST['addFolder'])) {
        $foldername = $_POST['folderName'];
        $parentID = intval($_POST['folderid']);
        $newFolderID = $conn->query("SELECT parent_folder FROM archive_folders WHERE userid = $userID");
        $newFolderID = $newFolderID->num_rows;
        $conn->query("INSERT INTO archive_folders VALUES($newFolderID," . intval($userID) . ",'$foldername',$parentID)");


        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        }
        $folderID = $parentID;
    } elseif (isset($_POST['addDocument'])) {
        $filename = $_POST['docName'];
        $body = $_POST['body'];
        $hashkey = hash('md5', random_bytes(10));
        $conn->query("INSERT INTO archive_savedfiles VALUES(null,'$filename','html',$folderID," . intval($userID) . ",'$hashkey',0,null,'FALSE')");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            $conn->query("INSERT INTO archive_editfiles VALUES('$hashkey','$body',1)");
            if ($conn->error) {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
            }
        }
    } elseif (isset($_POST['editDocument'])) {
        $filename = $_POST['docName'];
        $body = $_POST['body'];
        $id = $_POST['editDocument'];
        echo "<script>console.log('$body')</script>";
        $result = $conn->query("SELECT hashid, version FROM archive_editfiles e JOIN archive_savedfiles s ON s.hashkey = e.hashid WHERE id = $id ORDER BY version DESC");
        $row = $result->fetch_assoc();
        $hash = $row['hashid'];
        $version = $row['version'];
        $conn->query("UPDATE archive_savedfiles SET name='$filename',uploaddate=CURRENT_TIMESTAMP WHERE id = $id");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            $conn->query("INSERT INTO archive_editfiles VALUES('$hash','$body',$version+1)");
            if ($conn->error) {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
            }
        }
    }
}
?>
<div class="page-header"><h3 id="title" ></h3>
    <div class="page-header-button-group">
        <div id="newBtn" class="simpleBtnWrapper">
            <button onblur="setTimeout(function () {
                        document.getElementById('dropDiv').classList.remove('show');
                    }, 300);" onclick="document.getElementById('dropDiv').classList.toggle('show');" type="button" class="btn btn-default" title="New..."><i class="fa fa-plus"></i><?php echo $lang['NEW'] ?><i class="fa fa-caret-down"></i></button>
            <div class="dropDiv" id="dropDiv" >
                <a data-toggle="modal" data-target="#new-document" onclick="clearDoc()" ><?php echo $lang['FILE'] ?></a>
                <a data-toggle="modal" data-target="#new-folder"><?php echo $lang['FOLDER'] ?></a>
            </div>
        </div>
        <div id="uploadBtn" class="simpleBtnWrapper"><?php if ($enabled)
    echo
    '<button onblur="setTimeout(function(){document.getElementById(\'dropUpload\').classList.remove(\'show\');},300);" onclick="document.getElementById(\'dropUpload\').classList.toggle(\'show\');" type="button" class="btn btn-default" title="Upload..."><i class="fa fa-arrow-up"></i>' . $lang['UPLOAD'] . '<i class="fa fa-caret-down"></i></button>
            <div class="dropDiv" id="dropUpload" >
                <input class="fileInput" type="file" id="uploadFile" onChange="uploadFiles(this)" multiple ><label class="lbl" for="uploadFile" >' . $lang['FILE'] . '</label></input>
                <input class="fileInput" type="file" id="uploadFolder" onChange="uploadFolder(this)" multiple ><label class="lbl" for="uploadFolder" >' . $lang['FOLDER'] . '</label></input>
            </div>';
?>
        </div>

        <div id="moveBtn" class="simpleBtnWrapper hidden">
            <button type="button" onClick="showFolders()" data-toggle="modal" data-target="#folder-view" class="btn btn-default" title="Move"><i class="fa fa-folder-open-o"></i><i style="margin-left: -10px;font-size:11px;" class="fa fa-reply"></i><?php echo $lang['MOVE'] ?></button>
        </div>
        <div id="deleteBtn" class="simpleBtnWrapper hidden">
            <button type="button" onClick="deleteSelected()" class="btn btn-default" title="Delete"><i class="fa fa-trash"></i><?php echo $lang['DELETE'] ?></button>
        </div>
    </div>
    <input type="number" id="currentFolder" value="<?php echo $folderID ?>" style="visibility: hidden; height:1px; width:1px;"/>
</div>
<div id="dropbox" class="dropbox" >
    <div class="content" >
        <table class="table" id="fileTable">
            <thead>
                <tr>
                    <td><input type="checkbox" class="form-control" onchange="selectAll(event)" /></td>
                    <td><i class='fa fa-file-o'></i></td>
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

<form id="docForm" method="POST">
    <div class="modal fade" id="new-folder">
        <div class="modal-dialog modal-content modal-sm">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?>
            </div>
            <div class="modal-body">
                <label>Name</label>
                <input class="form-control" name="folderName"/>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="addFolder"><?php echo $lang['ADD']; ?></button>
            </div>
        </div>
    </div>
    <input style="visibility:hidden;width:1px;height:1px;" value="<?php echo $folderID ?>" id="parentid" name="folderid" ></input>
    <div class="modal fade" id="new-document">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?>
            </div>
            <div class="modal-body">
                <label>Name</label>
                <input class="form-control" name="docName"/>
                <label>Inhalt</label>
                <textarea class="form-control Editor" name="body" maxlength="20000" ></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" id="addDoc" class="btn btn-warning " name="addDocument"><?php echo $lang['ADD']; ?></button>
                <button type="submit" id="editDoc" class="btn btn-warning hidden" name="editDocument"><?php echo $lang['EDIT']; ?></button>
            </div>
        </div>
    </div>
    <div class="modal fade" id="folder-view">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?>
            </div>
            <div class="modal-body">
                <label>Folder</label>
                <div name="folderView" id="folderTree" ></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" data-dismiss="modal" onClick="moveSelected()" ><?php echo $lang['MOVE']; ?></button>
            </div>
        </div>
    </div>
</form>
<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
                    $("#dropbox").on({
                        dragenter: function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            //console.log("dragenter");
                            $("#dropbox").addClass('highlight');
                        },
                        dragleave: function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            //console.log("dragleave");
                            $("#dropbox").removeClass('highlight');
                        },
                        drop: function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            //console.log(event);
                        }
                    });
                    $(".folder").on({
                        dragenter: function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            //console.log("dragenter");
                            $(this).addClass('highlight');
                        },
                        dragleave: function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            //console.log("dragleave");
                            $(this).removeClass('highlight');
                        },
                        dragstart: function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            //console.log("dragstart");
                        },
                        dragend: function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            //console.log("dragend");
                        },
                        drop: function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            //console.log(event);
                        },
                    });
                    $(document).ready(function () {
                        getCurrentFolderContent();
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
                            file_picker_callback: function (cb, value, meta) {
                                var input = document.createElement('input');
                                input.setAttribute('type', 'file');
                                input.setAttribute('accept', '*');
                                input.onchange = function () {
                                    var file = this.files[0];
                                    var reader = new FileReader();
                                    reader.onload = function () {
                                        // Note: Now we need to register the blob in TinyMCEs image blob
                                        // registry. In the next release this part hopefully won't be
                                        // necessary, as we are looking to handle it internally.
                                        var id = 'blobid' + (new Date()).getTime();
                                        var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                                        //console.log(reader.result.split(";")[0].split(":")[1]) //mime type
                                        var base64 = reader.result.split(',')[1];
                                        alert("Base64 size: " + base64.length + " chars")
                                        var blobInfo = blobCache.create(id, file, base64);
                                        blobCache.add(blobInfo);
                                        // call the callback and populate the Title field with the file name
                                        cb(blobInfo.blobUri(), {title: file.name, text: file.name, alt: file.name, source: "images/Question_Circle.jpg", poster: "images/Question_Circle.jpg"});
                                    };
                                    reader.readAsDataURL(file);
                                };
                                input.click();
                            }
                        });
                        getTitle();
                    });
                    function getCurrentFolderContent() {
                        //console.log('start');
                        var userid = <?php echo $userID; ?>;
                        var currentFolder = document.getElementById("currentFolder").value;
                        $.post("../archive/getFolderContent", {
                            userid: userid,
                            folderid: currentFolder
                        }, function (data) {
                            clearTable();
                            //console.log(data);
                            fillTable(JSON.parse(data));
                        });
                    }
                    function clearTable() {
                        var table = document.getElementById("tableContent");
                        while (table.firstChild) {
                            table.removeChild(table.firstChild);
                        }
                    }
                    function changeFolder(event) {
                        if (event.target.nodeName == "TD") {
                            var id = event.target.parentElement.folderID;
                            //console.log(event);
                            document.getElementById("currentFolder").value = id;
                            var deleteE = document.getElementById("parentid");
                            var form = document.getElementById("docForm");
                            form.removeChild(deleteE);
                            var parentID = document.createElement("input");
                            parentID.setAttribute("name", "folderid");
                            parentID.setAttribute("value", id);
                            parentID.setAttribute("id", "parentid");
                            parentID.setAttribute("class", "hidden");
                            form.appendChild(parentID);
                            //console.log(document.getElementById("parentid").value);
                            $("thead :checkbox")[0].checked = false;
                            getTitle();
                            getCurrentFolderContent();
                        }
                    }
                    function fillTable(data) {
                        //console.log(data);
                        var rowcount = data[0].length + data[1].length;
                        var parent = data[2];
                        var rows = [];
                        for (i = 0; i < rowcount; i++) {
                            var row = document.createElement("tr");
                            var icon = document.createElement("td");
                            var select = document.createElement("td");
                            var name = document.createElement("td");
                            var upload_date = document.createElement("td");
                            var file_size = document.createElement("td");
                            var buttons = document.createElement("td");
                            row.appendChild(select);
                            row.appendChild(icon);
                            row.appendChild(name);
                            row.appendChild(upload_date);
                            row.appendChild(file_size);
                            row.appendChild(buttons);
                            rows.push(row);
                        }

                        if (data[0]) {
                            for (i = 0; i < data[0].length; i++) {
                                data[0][i]['isS3'] == "TRUE" ? isS3 = true : isS3 = false;
<?php if (!$enabled) echo "if(!isS3){" ?>
                                rows[i].className = "file";
                                rows[i].fileID = data[0][i]['id'];
                                rows[i].children[0].innerHTML = '<input type="checkbox" class="form-control" onchange="changeMenu()" />';
                                if (isS3) {
                                    rows[i].children[1].innerHTML = "<i class='fa fa-file'></i>";
                                } else {
                                    rows[i].children[1].innerHTML = "<i class='fa fa-paperclip'></i>";
                                }
                                rows[i].children[2].innerHTML = data[0][i]['name'];
                                rows[i].children[3].innerHTML = new Intl.DateTimeFormat('de-AT').format(new Date(data[0][i]['uploaddate']));
                                rows[i].children[4].innerHTML = data[0][i]['filesize'] + " KB";
                                if (isS3) {
                                    rows[i].children[5].innerHTML = "<a class='btn btn-default' download href='../private/files?n=" + data[0][i]['hashkey'] + "' ><i class='fa fa-download'></i></a>";
                                } else {
                                    rows[i].children[5].innerHTML = "<button class='btn btn-default' onclick='fillEditor(" + data[0][i]['id'] + ")' data-toggle='modal' data-target='#new-document' ><i class='fa fa-edit'></i></button>";
                                }
<?php if (!$enabled) echo "}" ?>
                            }
                        }
                        if (data[1]) {
                            for (i = 0; i < rowcount - data[0].length; i++) {
                                rows[i + data[0].length].className = "folder";
                                rows[i + data[0].length].addEventListener("click", changeFolder);
                                rows[i + data[0].length].folderName = data[1][i]['name'];
                                rows[i + data[0].length].folderID = data[1][i]['folderid'];
                                rows[i + data[0].length].isBack = false;
                                rows[i + data[0].length].children[0].innerHTML = '<input type="checkbox" class="form-control" onchange="changeMenu()" />'
                                rows[i + data[0].length].children[1].innerHTML = "<i class='fa fa-folder'></i>";
                                rows[i + data[0].length].children[2].innerHTML = data[1][i]['name'];
                                rows[i + data[0].length].children[3].innerHTML = "---";
                                rows[i + data[0].length].children[4].innerHTML = "---";
                                rows[i + data[0].length].children[5].innerHTML = "<a class='btn btn-default'><i class='fa fa-download'></i></a>";
                            }
                        }

                        rows.sort(function (a, b) {
                            var x = a.children[2].innerHTML.toLowerCase();
                            var y = b.children[2].innerHTML.toLowerCase();
                            if (x < y) {
                                return -1;
                            }
                            if (y < x) {
                                return 1;
                            }
                            return 0;
                        });
                        var table = document.getElementById("tableContent");
                        if (parent !== "-1") {
                            row = document.createElement("tr");
                            row.className = "folder back";
                            row.addEventListener("click", changeFolder);
                            row.folderID = parent;
                            row.isBack = true;
                            back = document.createElement("td");
                            back.colSpan = 6;
                            back.innerText = "Zurück";
                            row.appendChild(back);
                            table.appendChild(row);
                        }
                        for (i = 0; i < rowcount; i++) {
                            if (rows[i].classList.length > 0) {
                                table.appendChild(rows[i]);
                            }
                        }
                        changeMenu();
                    }
                    function uploadFiles(element) {
                        //Upload selected files
                        //console.log(element.files);
                        //evt.preventDefault();
                        var formData = new FormData();
                        var fileIndex = [];
                        for (var i = 0; i < element.files.length; i++) {
                            formData.append('file' + i, element.files[i]);
                        }
                        formData.append('amount', element.files.length);
                        formData.append('userID',<?php echo $userID ?>);
                        formData.append('function', 'addFile');
                        formData.append('fileIndex', fileIndex);
                        formData.append('folderid', document.getElementById('currentFolder').value);
                        $.ajax({
                            url: '../private/files',
                            type: 'POST',
                            async: true,
                            data: formData,
                            cache: false,
                            contentType: false,
                            encType: 'multipart/form-data',
                            processData: false,
                            success: function (response) {
                                //alert(response);
                                //console.log(response);
                                getCurrentFolderContent();
                            }
                        });
                    }
                    function deleteFile(id) {
                        if (Array.isArray(id))
                        {
                            for (i = 0; i < id.length; i++) {
                                $.post("../private/files", {
                                    id: id[i],
                                    function: "deleteFile",
                                }, function (data) {
                                    getCurrentFolderContent();
                                });
                            }
                        } else {
                            $.post("../private/files", {
                                id: id,
                                function: "deleteFile",
                            }, function (data) {
                                getCurrentFolderContent();
                            });
                        }
                    }
                    function fillEditor(id) {
                        document.getElementById("editDoc").value = id;
                        $.post("../private/files", {
                            id: id,
                            function: "getFileData",
                        }, function (data) {
                            var info = JSON.parse(data);
                            //console.log(data);
                            $("[name='docName']").val(info[0]);
                            tinymce.activeEditor.execCommand('mceSetContent', false, info[1]);
                            if (document.getElementById("editDoc").classList.contains("hidden")) {
                                document.getElementById("addDoc").classList.toggle("hidden");
                                document.getElementById("editDoc").classList.remove("hidden");
                            }

                        });
                    }
                    function clearDoc() {
                        $("[name='docName']").val('');
                        tinymce.activeEditor.execCommand('mceSetContent', false, '');
                        if (document.getElementById("addDoc").classList.contains("hidden")) {
                            document.getElementById("addDoc").classList.remove("hidden");
                            document.getElementById("editDoc").classList.toggle("hidden");
                        }
                    }
                    function selectAll(event) {
                        //console.log(event);
                        for (i = 0; i < document.getElementById("tableContent").childNodes.length; i++) {
                            document.getElementById("tableContent").childNodes[i].childNodes[0].firstChild.checked = event.target.checked;
                        }
                        changeMenu();
                    }
                    function deleteFolder(id) {


                        //console.log(id);
                        $.post("../private/files", {
                            id: id,
                            userID: <?php echo $userID ?>,
                            function: "deleteFolder"
                        }, function (data) {
                            //console.log(data);
                            ids = JSON.parse(data);
                            deleteFile(ids);
                        });

                    }
                    function getTitle() {
                        title = "<?php echo $lang['YOUR_ARCHIVE']; ?>";
                        id = document.getElementById("currentFolder").value;
                        $.post("../private/files", {
                            id: id,
                            userID: <?php echo $userID; ?>,
                            title: title,
                            function: "getTitle",
                        }, function (response) {
                            document.getElementById("title").innerHTML = response;
                        });
                    }
                    function anySelected() {
                        isSelected = false;
                        if ($("#tableContent :checked").length > 0)
                            isSelected = true;
                        //console.log($(".dropbox :checked"));
                        return isSelected;
                    }
                    function changeMenu() {
                        setTimeout(function () {
                            if (anySelected() && document.getElementById("deleteBtn").classList.contains("hidden")) {
                                document.getElementById("deleteBtn").classList.toggle("hidden");
                                document.getElementById("moveBtn").classList.toggle("hidden");
                                document.getElementById("uploadBtn").classList.toggle("hidden");
                                document.getElementById("newBtn").classList.toggle("hidden");
                            } else if (!anySelected() && document.getElementById("newBtn").classList.contains("hidden")) {
                                document.getElementById("deleteBtn").classList.toggle("hidden");
                                document.getElementById("moveBtn").classList.toggle("hidden");
                                document.getElementById("uploadBtn").classList.toggle("hidden");
                                document.getElementById("newBtn").classList.toggle("hidden");
                            }
                        });
                    }
                    function deleteSelected() {
                        var selectedFiles = $("#tableContent :checked");
                        for (i = 0; i < selectedFiles.length; i++) {
                            element = selectedFiles[i].parentElement.parentElement;
                            //console.log(element);
                            if (element.classList.contains("file")) {
                                deleteFile(element.fileID)
                            } else {
                                if (window.confirm("Are you sure you want to delete this Folder?")) {
                                    deleteFolder(element.folderID)
                                }

                            }

                        }
                        getCurrentFolderContent();
                    }
                    function moveSelected() {
                        var selectedNodes = $("#tableContent :checked");
                        var selectedFolder = $('#folderTree').treeview('getSelected')[0];
                        var folderID = selectedFolder.tags[0];
                        for (i = 0; i < selectedNodes.length; i++) {
                            element = selectedNodes[i].parentElement.parentElement;
                            //console.log(element);
                            if (element.classList.contains("file")) {
                                moveFile(element.fileID, folderID)
                            } else {
                                moveFolder(element.folderID, folderID)
                            }
                        }
                    }
                    function moveFile(fileid, folderid) {
                        $.post("../private/files", {
                            id: fileid,
                            folder: folderid,
                            function: "moveFile"
                        }, function (data) {
                            //console.log(data);
                            getCurrentFolderContent();
                        });
                    }
                    function moveFolder(folder, moveto) {
                        // $.post("../private/files",{
                        //     id:
                        // })
                    }
                    function showFolders() {
                        $.post("../private/files", {
                            userID: <?php echo $userID ?>,
                            function: "getFolders"
                        }, function (data) {
                            //console.log('['+data+']');
                            $("#folderTree").treeview({
                                data: '[' + data + ']',

                            });
                            $("#folderTree .badge").each(function () {
                                this.classList.add("hidden");
                            });
                        })
                    }
</script>
<?php include dirname(__DIR__) . '/footer.php'; ?>
