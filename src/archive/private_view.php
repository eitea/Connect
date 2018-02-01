<?php include dirname(__DIR__) . '/header.php';?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>

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
</style>

<div id="title" class="page-header"><h3><?php echo $lang['YOUR_ARCHIVE']; ?><div class="page-header-button-group">
</div></h3></div>
<div id="dropbox" class="dropbox" >
    <div class="content" >
    <table class="table" >
        <thead>
            <tr>
                <td></td>
                <td><label>Name</label></td>
                <td><label>Last Download</label></td>
                <td><label>File Size</label></td>
                <td></td>
            </tr>
        </thead>
        <tbody id="tableContent">
            <tr class="file">
                <td><i class="fa fa-server"></i></td>
                <td>Test</td>
                <td>DD-mm-YY</td>
                <td>0 KB</td>
                <td><button class="btn btn-default"><i class="fa fa-download"></i></button></td>
            </tr>
            <tr class="folder">
                <td><i class="fa fa-folder"></i></td>
                <td>Test Folder</td>
                <td>DD-mm-YY</td>
                <td>0 KB</td>
                <td><button class="btn btn-default"><i class="fa fa-download"></i></button></td>
            </tr>
            <tr class="file">
                <td><i class="fa fa-server"></i></td>
                <td>Test</td>
                <td>DD-mm-YY</td>
                <td>0 KB</td>
                <td><button class="btn btn-default"><i class="fa fa-download"></i></button></td>
            </tr>
            <tr class="file">
                <td><i class="fa fa-server"></i></td>
                <td>Test</td>
                <td>DD-mm-YY</td>
                <td>0 KB</td>
                <td><button class="btn btn-default"><i class="fa fa-download"></i></button></td>
            </tr>
            <?php ?>
        </tbody>
    </table>
    </div>
    <!--<span class="info" >Drop Files to Upload</span>-->
</div>


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
        click: function(event){
            console.log(event);
            $("#title")[0].firstChild.innerText = $("#title")[0].firstChild.innerText + " > " + event.currentTarget.children[1].innerText;
            content = document.getElementById("tableContent");
            while(content.firstChild){
                content.removeChild(content.firstChild);
            }
            row = document.createElement("tr");
            row.className = "folder back";
            back = document.createElement("td");
            back.colSpan = 5;
            back.innerText = "Zurück";
            row.appendChild(back);
            document.getElementById("tableContent").appendChild(row);
        },
    });
</script>
<?php include dirname(__DIR__) . '/footer.php'; ?>
