<?php
require 'header.php';
require __DIR__ . "/Calculators/dynamicProjects_ProjectSeries.php";
$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0); //"project" => 0); //set_filter requirement
?>
<div class="page-header"><h3>Tasks<div class="page-header-button-group">
    <?php include 'misc/set_filter.php';?>
    <?php if($isDynamicProjectsAdmin == 'TRUE'): ?> <button class="btn btn-default" data-toggle="modal" data-target="#editingModal-" type="button"><i class="fa fa-plus"></i></button><?php endif; ?>
</div></h3></div>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!empty($_POST['play'])){
        $x = test_input($_POST['play']);
        $result = mysqli_query($conn, "SELECT indexIM FROM $logTable WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00' LIMIT 1");
        if($result && ($row = $result->fetch_assoc())){
            $indexIM = $row['indexIM'];
            $conn->query("INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType, dynamicID) VALUES(UTC_TIMESTAMP, '0000-00-00 00:00:00', $indexIM, 'Dummy Text' , 'project', '$x')");
            if($conn->error){
            	echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            } else {
            	echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Bitte einstempeln</strong> Tasks können nur angenommen werden, sofern man eingestempelt ist.</div>';
        }
    }
    if(!empty($_POST['createBooking']) && !empty($_POST['dynamicbooking-project'])){
        $x = test_input($_POST['createBooking']);

        $conn->query("UPDATE projectBookingData WHERE id = $x");
    }
    if($isDynamicProjectsAdmin == 'TRUE'){
        if(!empty($_POST['deleteProject'])){
            $val = test_input($_POST['deleteProject']);
            $conn->query("DELETE FROM dynamicProjects WHERE projectid = '$val'");
            if($conn->error){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            } else {
                echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
            }
        }
        if(isset($_POST['editDynamicProject'])){ //new projects
            if(isset($available_companies[1]) && !empty($_POST['name']) && !empty($_POST['description']) && !empty($_POST['owner']) && test_Date($_POST['start'], 'Y-m-d') && !empty($_POST['employees'])){
                $id = uniqid();
                if(!empty($_POST['editDynamicProject'])){ //existing
                    $id =  test_input($_POST['editDynamicProject']);
                    $conn->query("DELETE FROM dynamicProjects WHERE projectid = '$id'"); echo $conn->error; //fk does the rest
                }
                $null = null;
                $name = test_input($_POST["name"]);
                $description = $_POST["description"];
                $company = $_POST["filterCompany"] ?? $available_companies[1];
                $client = intval($_POST['filterClient']);
                $project = intval($_POST['filterProject']);
                $color = $_POST["color"] ? test_input($_POST['color']) : '#FFFFFF';
                $start = $_POST["start"];
                $end = $_POST["endradio"];
                $status = $_POST["status"];
                $priority = intval($_POST["priority"]); //1-5
                $parent = test_input($_POST["parent"]); //dynamproject id
                $owner = $_POST['owner'] ? intval($_POST["owner"]) : $userID;
                $percentage = intval($_POST['completed']);

                if ($end == "number") {
                    $end = $_POST["endnumber"] ?? "";
                } elseif ($end == "date") {
                    $end = $_POST["enddate"] ?? "";
                }

                $series = $_POST["series"] ?? "once";
                $series = new ProjectSeries($series, $start, $end);
                $series->daily_days = (int) $_POST["daily_days"] ?? 1;
                $series->weekly_weeks = (int) $_POST["weekly_weeks"] ?? 1;
                $series->weekly_day = $_POST["weekly_day"] ?? "monday";
                $series->monthly_day_of_month_day = (int) $_POST["monthly_day_of_month_day"] ?? 1;
                $series->monthly_day_of_month_month = (int) $_POST["monthly_day_of_month_month"] ?? 1;
                $series->monthly_nth_day_of_week_nth = (int) $_POST["monthly_nth_day_of_week_nth"] ?? 1;
                $series->monthly_nth_day_of_week_day = $_POST["monthly_nth_day_of_week_day"] ?? "monday";
                $series->monthly_nth_day_of_week_month = (int) $_POST["monthly_nth_day_of_week_month"] ?? 1;
                $series->yearly_nth_day_of_month_nth = (int) $_POST["yearly_nth_day_of_month_nth"] ?? 1;
                $series->yearly_nth_day_of_month_month = $_POST["yearly_nth_day_of_month_month"] ?? "JAN";
                $series->yearly_nth_day_of_week_nth = (int) $_POST["yearly_nth_day_of_week_nth"] ?? 1;
                $series->yearly_nth_day_of_week_day = $_POST["yearly_nth_day_of_week_day"] ?? "monday";
                $series->yearly_nth_day_of_week_month = $_POST["yearly_nth_day_of_week_month"] ?? "JAN";
                $nextDate = $series->get_next_date();
                $series = base64_encode(serialize($series));

                // PROJECT
                $stmt = $conn->prepare("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
                    projectpriority, projectparent, projectowner, projectnextdate, projectseries, projectpercentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssbiiissssisisbi", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $owner, $nextDate, $null, $percentage);
                $stmt->send_long_data(2, $description);
                $stmt->send_long_data(12, $series);
                $stmt->execute();

                if(!$stmt->error){
                    $stmt->close();
                    //PICTURES
                    $pictures = $_POST["imagesbase64"] ?? false;
                    if ($pictures) {
                        $stmt = $conn->prepare("INSERT INTO dynamicprojectspictures (projectid,picture) VALUES ('$id', ?)"); echo $conn->error;
                        $stmt->bind_param("b", $null);
                        foreach ($pictures as $picture) {
                            $stmt->send_long_data(0, $picture);
                            $stmt->execute();
                        }
                        $stmt->close();
                    }
                    //EMPLOYEES
                    $stmt = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$id', ?, ?)"); echo $conn->error;
                    $stmt->bind_param("is", $employee, $position);
                    $position = 'normal';
                    foreach ($_POST["employees"] as $employee) {
                        $emp_array = explode(";", $employee);
                        if ($emp_array[0] == "user") {
                            $employee = intval($emp_array[1]);
                            $stmt->execute();
                        } else {
                            $team = intval($emp_array[1]);
                            $conn->query("INSERT INTO dynamicprojectsteams (projectid, teamid) VALUES ('$id',$team)");
                        }
                    }
                    $position = 'optional';
                    foreach ($_POST['optionalemployees'] as $optional_employee) {
                        $employee = intval($optional_employee);
                        $stmt->execute();
                    }
                    echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
                } else {
                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$stmt->error.'</div>';
                }
                $stmt->close();
            } else {
                echo $_POST['description'] .' -descr<br>';
                echo $_POST['name'] .' -name<br>';
                echo !empty($_POST['employees']) .' -emps<br>';
                echo $available_companies[1] .' -compa<br>';
                echo test_Date($_POST['start'], 'Y-m-d') .' -date<br>';
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
            }
        }
    } // end if dynamic Admin

} //end if POST
?>

<table class="table table-hover">
    <thead>
        <tr>
            <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_NAME"]; ?></th>
            <th><?php echo $lang["DESCRIPTION"]; ?></th>
            <th><?php echo $lang["COMPANY"]; ?></th>
            <th><?php echo $lang["CLIENTS"]; ?></th>
            <th><?php echo $lang["BEGIN"]; ?></th>
            <th><?php echo $lang["END"]; ?></th>
            <th>Routine</th>
            <th>Status</th>
            <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?></th>
            <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OWNER"]; ?></th>
            <th><?php echo $lang["EMPLOYEE"]; ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $modals = $occupation = '';
        $stmt_team = $conn->prepare("SELECT name FROM dynamicprojectsteams INNER JOIN teamData ON teamid = teamData.id WHERE projectid = ?");
        $stmt_team->bind_param('s', $x);
        $stmt_employee = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) as name FROM dynamicprojectsemployees INNER JOIN UserData ON UserData.id = userid WHERE projectid = ? ");
        $stmt_employee->bind_param('s', $x);
        $stmt_booking = $conn->prepare("SELECT userID, p.id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND `end` = '0000-00-00 00:00:00' AND dynamicID = ?");
        $stmt_booking->bind_param('s', $x); //shall always return max 1 row
        $result = $conn->query("SELECT id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND logs.userID = $userID AND `end` = '0000-00-00 00:00:00' LIMIT 1");
        $hasActiveBooking = $result->num_rows;
        if($isDynamicProjectsAdmin == 'TRUE'){
            //see all access-legal tasks
            $result = $conn->query("SELECT d.projectid, projectname, projectdescription, projectcolor, projectstart, projectend, projectseries, projectstatus, projectpriority, projectowner, firstname, lastname,
                d.companyid, d.clientid, d.clientprojectid, companyData.name AS companyName, clientData.name AS clientName
                FROM dynamicprojects d LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN UserData ON UserData.id = projectowner
                WHERE d.companyid IN (".implode(', ', $available_companies).")");
        } else {
            //see open tasks user is part of
            $result = $conn->query("SELECT d.projectid, projectname, projectdescription, projectcolor, projectstart, projectend, projectseries, projectstatus, projectpriority, projectowner, firstname, lastname,
                d.companyid, d.clientid, d.clientprojectid, companyData.name AS companyName, clientData.name AS clientName
                FROM dynamicprojects d LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN UserData ON UserData.id = projectowner
                LEFT JOIN dynamicprojectsemployees ON dynamicprojectsemployees.projectid = d.projectid
                LEFT JOIN dynamicprojectsteams ON dynamicprojectsteams.projectid = d.projectid LEFT JOIN teamRelationshipData ON teamRelationshipData.teamID = dynamicprojectsteams.teamid
                WHERE d.projectstatus = 'ACTIVE' AND (dynamicprojectsemployees.userid = $userID OR d.projectowner = $userID OR teamRelationshipData.userID = $userID)");
        }
        echo $conn->error;
        while($row = $result->fetch_assoc()){
            $x = $row['projectid'];
            $completed = 0;
            echo '<tr>';
            echo '<td><i style="color:'.$row['projectcolor'].'" class="fa fa-circle"></i> '.$row['projectname'].'</td>';
            echo '<td><a type="button" class="btn btn-default" data-toggle="modal" data-target="#view-'.$x.'" >View</a></td>';
            echo '<td>'.$row['companyName'].'</td>';
            echo '<td>'.$row['clientName'].'</td>';
            echo '<td>'.$row['projectstart'].'</td>';
            echo '<td>'.$row['projectend'].'</td>';

            if($row['projectseries']){
                echo '<td><i class="fa fa-clock-o"></i></td>';
            } else {
                echo '<td><i class="fa fa-times" style="color:red" title="Keine Routine"></i></td>';
            }
            echo '<td>'.$row['projectstatus'].'</td>';
            echo '<td>'.$row['projectpriority'].'</td>';
            echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';

            echo '<td>';
            $stmt_team->execute();
            $employees = array_column($stmt_team->get_result()->fetch_all(), 0);
            $stmt_employee->execute();
            $employees = array_merge($employees, array_column($stmt_employee->get_result()->fetch_all(), 0));
            echo implode(',<br>', $employees);
            echo '</td>';
            //modal creation happens on demand
            echo '<td><form method="POST">';
            $stmt_booking->execute();
            $isInUse = $stmt_booking->get_result(); //max 1 row
            if(($useRow = $isInUse->fetch_assoc()) && $useRow['userID'] == $userID) {
                //if this task IsInUse and this user is the one using it
                echo '<button class="btn btn-default" type="button" value="" data-toggle="modal" data-target="#bookDynamicProject"><i class="fa fa-pause"></i></button>';
                $occupation = array('bookingID' => $useRow['id'], 'companyid' => $row['companyid'], 'clientid' => $row['clientid'], 'projectid' => $row['projectid']);
            } elseif($row['projectstatus'] == 'ACTIVE' && $isInUse->num_rows < 1 && !$hasActiveBooking){
                //only if project ist active, this task is NOT in use and user has no active bookings
                echo "<button class='btn btn-default' type='submit' title='Task starten' name='play' value='$x'><i class='fa fa-play' ></i></button>";
            }
            if($isDynamicProjectsAdmin == 'TRUE' || $row['projectowner'] == $userID) {
                echo '<button type="button" name="editModal" value="'.$x.'" class="btn btn-default" title="Bearbeiten"><i class="fa fa-pencil"></i></button> ';
                echo '<button type="submit" name="deleteProject" value="'.$x.'" class="btn btn-default" title="Löschen"><i class="fa fa-trash-o"></i></button>';
            }
            echo '</form></td>';
            echo '</tr>';

            $modals .= '<div id="view-'.$x.'" class="modal fade"><div class="modal-dialog modal-content modal-md">
            <div class="modal-header h4">'.$row['projectname'].'</div><div class="modal-body">'.$row['projectdescription'].'</div>
            <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">O.K.</button></div></div></div>';
        }
        ?>
    </tbody>
</table>

<div id="editingModalDiv">
    <?php echo $modals; ?>
    <!-- booking modal -->
    <?php if($occupation): ?>
    <div class="modal fade" id="bookDynamicProject" tabindex="-1">
        <div class="modal-dialog modal-content modal-md">
            <form method="POST">
                <div class="modal-header h4"><button type="button" class="close"><span>&times;</span></button><?php echo $lang["DYNAMIC_PROJECTS_BOOKING_PROMPT"]; ?></div>
                <div class="modal-body">
                    <textarea name="description" required class="form-control" style="max-width:100%; min-width:100%"></textarea>
                    <br>
                    <div class="input-group">
                        <input type="number" class="form-control" name="completed" min="0" max="100" id="bookDynamicProjectCompleted" />
                        <span class="input-group-addon" id="basic-addon2">% Abgeschlossen</span>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" id="bookDynamicProjectCompletedCheckbox"> Abgeschlossen</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="createBooking" value="<?php echo $occupation['bookingID']; ?>"><?php echo $lang['SAVE']; ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
$("#bookDynamicProjectCompletedCheckbox").change(function(event){
    $("#bookDynamicProjectCompleted").attr('disabled', this.checked);
    if(this.checked){
        $("#bookDynamicProjectCompleted").val(100);
    }
});
$("#bookDynamicProjectCompleted").keyup(function(event){
    if($("#bookDynamicProjectCompleted").val() == 100){
        $("#bookDynamicProjectCompletedCheckbox").prop('checked', true);
    } else {
        $("#bookDynamicProjectCompletedCheckbox").prop('checked', false);
    }
});
function formatState (state) {
    if (!state.id) { return state.text; }
    var $state = $(
        '<span><i class="fa fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
    );
    return $state;
};
function removeImg(event) {
    $(event.target).remove();
}
function getImageSrc(img) {
    var c = document.createElement("canvas");
    c.width = img.naturalWidth;
    c.height = img.naturalHeight;
    var ctx = c.getContext("2d");
    ctx.drawImage(img, 0, 0);
    return c.toDataURL();
}
function dynamicOnLoad(modID){
    if(typeof modID === 'undefined') modID = '';
    $("#projectPreview"+modID+" img").click(removeImg);
    $("#projectForm"+modID).submit(function (event) {
        $("#projectPreview"+modID).find("input").remove()
        $("#projectPreview"+modID).find("img").each(function (index, elem) {
            console.log(getImageSrc(elem).length);
            $("#projectPreview"+modID).append("<input type='hidden' value='" + getImageSrc(elem) + "' name='imagesbase64[]'>");
        });
    });
    $("#projectImageUpload"+modID).change(function (event) {
        var files = event.target.files;
        //$("#newProjectPreview").html(""); //delete old pictures
        for (var i = 0, f; f = files[i]; i++) { // Loop through the FileList and render image files as thumbnails.
            if (!f.type.match('image.*')) continue;
            var reader = new FileReader();
            // Closure to capture the file information.
            reader.onload = (function (theFile) {
                return function (e) { // Render thumbnail.
                    var span = document.createElement('span');
                    span.innerHTML = '<img class="img-thumbnail" style="width:49%;margin:0.5%" src="' + e.target.result + '" title="' + escape(theFile.name) + '"/>';
                    $("#projectPreview"+modID).append(span);
                    $("#projectPreview"+modID+" img").unbind("click").click(removeImg);
                };
            })(f);
            reader.readAsDataURL(f); // Read in the image file as a data URL.
        }
    });
    $(".select2-team-icons").select2({
        templateResult: formatState,
        templateSelection: formatState
    });
    tinymce.init({
        selector: '.projectDescriptionEditor',
        plugins: 'image code',
        plugins: 'paste',
        relative_urls: false,
        paste_data_images: true,
        toolbar: 'undo redo | link image file media | code',
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
} //end dnymaicOnLoad()

function appendModal(index){
    $.ajax({
    url:'ajaxQuery/AJAX_dynamicEditModal.php',
    data:{projectid: index, userid: <?php echo $userID; ?> },
    type: 'get',
    success : function(resp){
      $("#editingModalDiv").append(resp);
      existingModals.push(index);
      onPageLoad();
      dynamicOnLoad(index);
    },
    error : function(resp){},
    complete: function(resp){
        if(index){
            $('#editingModal-'+index).modal('show');
        }
    }
   });
}
var existingModals = new Array();
$('button[name=editModal]').click(function(){
    var index = $(this).val();
  if(existingModals.indexOf(index) == -1){
      appendModal(index);
  } else {
    $('#editingModal-'+index).modal('show');
  }
});

appendModal('');

$(document).ready(function() {
    dynamicOnLoad();
    $('.table').DataTable({
      order: [],
      ordering: false,
      language: {
        <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
      },
      responsive: true,
      dom: 'tf',
      autoWidth: false,
      fixedHeader: {
        header: true,
        headerOffset: 50,
        zTop: 1
      },
      paging: false
    });
});


  function showClients(company, client, place){
    if(company != ""){
      $.ajax({
        url:'ajaxQuery/AJAX_getClient.php',
        data:{companyID:company, clientID:client},
        type: 'get',
        success : function(resp){
          $("#"+place).html(resp);
        },
        error : function(resp){}
      });
    }
  }
  function showProjects(client, project, place){
    if(client != ""){
      $.ajax({
        url:'ajaxQuery/AJAX_getProjects.php',
        data:{clientID:client, projectID:project},
        type: 'get',
        success : function(resp){
          $("#"+place).html(resp);
        },
        error : function(resp){}
      });
    }
  }
</script>
<?php require 'footer.php'; ?>
