<?php
require 'header.php';
$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0); //"project" => 0); //set_filter requirement
?>
<div class="page-header"><h3>Tasks<div class="page-header-button-group">
    <?php include 'misc/set_filter.php';?>
    <?php if($isDynamicProjectsAdmin): ?> <button class="btn btn-default" data-toggle="modal" data-target="#dynamicProject" type="button"><i class="fa fa-plus"></i></button><?php endif; ?>
</div></h3></div>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if($isDynamicProjectsAdmin && isset($_POST['newDynamicProject'])){ //only an admin can trigger this
        require __DIR__ . "/misc/dynamicProjects_ProjectSeries.php";
        if(isset($available_companies[1]) && !empty($_POST['name']) && !empty($_POST['description']) && !empty($_POST['owner']) && test_Date($_POST['start'], 'Y-m-d') && !empty($_POST['employees'])){
            $id = uniqid();
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

            if ($end == "number") {
                $end = $_POST["endnumber"] ?? "";
            } elseif ($end == "date") {
                $end = $_POST["enddate"] ?? "";
            }
            //serialize object for easy storage
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
                projectpriority, projectparent, projectowner, projectnextdate, projectseries) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssbiiissssisisb", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $owner, $nextDate, $null);
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
                foreach ($_POST["employees"] as $employee) {
                    $position = 'normal';
                    $emp_array = explode(";", $employee);
                    if ($emp_array[0] == "user") {
                        $employee = intval($emp_array[1]);
                        $stmt->execute();
                    } else {
                        $team = intval($emp_array[1]);
                        $conn->query("INSERT INTO dynamicprojectsteams (projectid, teamid) VALUES ('$id',$team)");
                    }
                }
                foreach ($_POST['optionalemployees'] as $optional_employee) {
                    $position = 'optional';
                    $employee = intval($optional_employee);
                    $stmt->execute();
                }
                $stmt->close();

            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$stmt->error.'</div>';
                $stmt->close();
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
        }
    }

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
            <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_SERIES"]; ?></th>
            <th>Status</th>
            <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?></th>
            <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OWNER"]; ?></th>
            <th><?php echo $lang["EMPLOYEE"]; ?></th>
            <th></th> <!-- space for edit, bookings and play/pause -->
        </tr>
    </thead>
    <tbody>
        <?php
        $result = $conn->query("SELECT projectname, projectdescription, companyid, projectcolor, projectstart, projectend, companyData.name AS companyName, clientData.name AS clientName
            FROM dynamicprojects LEFT JOIN companyData ON companyData.id = companyid LEFT JOIN clientData ON clientData.id = clientid
            WHERE companyid IN (".implode(', ', $available_companies).")");
        while($row = $result->fetch_assoc()){
            $completed = 0;
            echo '<tr>';
            echo '<td style="background-color:'.$row['projectcolor'].'">'.$row['projectname'].'</td>';
            echo '<td>'.$row['projectdescription'].'</td>';
            echo '<td>'.$row['companyName'].'</td>';
            echo '<td>'.$row['clientName'].'</td>';
            echo '<td>'.$row['projectstart'].'</td>';
            echo '<td>'.$row['projectend'].'</td>';

            echo "<td>";
             //percentage of overall project completed 0-100
            $completed = intval($completed / ((count($clients) > 0) ? count($clients) : 1)); // average completion
            echo "</td>";
            echo "<td>$start</td>";
            echo "<td>$end</td>"; // no end = ""
            echo "<td>$series</td>";
            echo "<td>$status</td>";
            echo "<td>$priority</td>";
            echo "<td>$owner</td>";
            echo "<td>";
            while ($employeeRow = $employeesResult->fetch_assoc()) {
                array_push($employees, "user;" . $employeeRow["id"]);
                $employee = "${employeeRow['firstname']} ${employeeRow['lastname']}";
                echo "$employee, ";
            }
            while ($teamRow = $teamsResult->fetch_assoc()) {
                array_push($employees, "team;" . $teamRow["id"]);
                $team = $teamRow["name"];
                echo "$team, ";
            }
            echo "</td>";
            echo "<td>";
            echo "</td>";
            echo '</tr>';
        }
        ?>
    </tbody>
</table>


<div class="modal fade" id="dynamicProject">
    <div class="modal-dialog modal-lg" role="form">
        <div class="modal-content">
            <form method="POST" id="projectForm">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Neuer Task</h4>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#projectBasics">Grundliegendes*</a></li>
                    <li><a data-toggle="tab" href="#projectDescription"><?php echo $lang["DESCRIPTION"]; ?>*</a></li>
                    <li><a data-toggle="tab" href="#projectAdvanced">Erweiterte Optionen</a></li>
                    <li><a data-toggle="tab" href="#projectSeries">Routine Aufgabe</a></li>
                </ul>
                <div class="tab-content">
                    <div id="projectBasics" class="tab-pane fade in active"><br>
                        <?php include __DIR__ .'/misc/select_project.php'; ?>
                        <div class="col-md-12"><small>*Auswahl ist Optional. Falls kein Projekt angegeben, entscheidet der Benutzer.</small><br><br></div>
                        <div class="col-md-12"><label>Task Name*</label><input class="form-control" type="text" name="name" placeholder="Bezeichnung" required><br></div>
                        <?php
                        $modal_options = '';
                        $result = $conn->query("SELECT id, firstname, lastname FROM UserData");
                        while ($row = $result->fetch_assoc()) $modal_options .= '<option value="user;'.$row['id'].'" data-icon="user">'.$row['firstname'] .' '. $row['lastname'].'</option>';
                        ?>
                        <div class="col-md-4">
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OWNER"]; ?>*</label>
                            <select class="select2-team-icons" name="owner" required>
                                <?php echo str_replace('<option value="user;'.$userID.'" ', '<option value="'.$userID.'" selected ', $modal_options); ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label><?php echo $lang["EMPLOYEE"]; ?>*</label>
                            <select class="select2-team-icons" name="employees[]" multiple="multiple" required>
                                <?php
                                echo $modal_options;
                                $result = $conn->query("SELECT id, name FROM $teamTable");
                                while ($row = $result->fetch_assoc()) echo '<option value="team;'.$row['id'].'" data-icon="group">'.$row['name'].'</option>';
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OPTIONAL_EMPLOYEES"]; ?></label>
                            <select class="select2-team-icons" name="optionalemployees[]" multiple="multiple">
                                <?php echo $modal_options; ?>
                            </select>
                        </div>
                    </div>
                    <div id="projectDescription" class="tab-pane fade"><br>
                        <label><?php echo $lang["DESCRIPTION"]; ?>*</label>
                        <textarea id="projectDescriptionEditor" class="form-control" rows="10" name="description" required> </textarea>
                        <br>
                        <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PICTURES"]; ?></label>
                        <br>
                        <label class="btn btn-default" role="button"><?php echo $lang["DYNAMIC_PROJECTS_CHOOSE_PICTURES"]; ?>
                            <input type="file" name="images" multiple class="form-control" style="display:none;" id="projectImageUpload" accept=".jpg,.jpeg,.png">
                        </label>
                        <div id="projectPreview">
                            <?php
                            if(isset($_POST['imagesbase64'])){
                                foreach ($_POST['imagesbase64'] as $modal_picture) {
                                    echo "<span><img src='$modal_picture' alt='Previously uploaded' class='img-thumbnail' style='width:48%;margin:0.45%'></span>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div id="projectAdvanced" class="tab-pane fade"><br>
                        <div class="col-md-6">
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?>*</label>
                            <select class="form-control js-example-basic-single" name="priority">
                                <option value='1'>Sehr niedrig</option>
                                <option value='2'>Niedrig</option>
                                <option value='3' selected >Normal</option>
                                <option value='4'>Hoch</option>
                                <option value='5'>Sehr hoch</option>
                            </select><br>
                        </div>
                        <div class="col-md-6">
                            <label>Status*</label>
                            <div class="input-group">
                                <select class="form-control" name="status" required >
                                    <option value="DEACTIVATED">Deaktiviert</option>
                                    <option value="DRAFT">Entwurf</option>
                                    <option value="ACTIVE" selected >Aktiv</option>
                                    <option value="COMPLETED">Abgeschlossen</option>
                                </select>
                                <span class="input-group-addon text-warning"><?php echo $lang["DYNAMIC_PROJECTS_PERCENTAGE_FINISHED"]; ?></span>
                                <input type='number' class="form-control" name='completed' value="0" min="0" max="100" step="1"/>
                            </div><br>
                        </div>
                        <div class="col-md-6">
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_COLOR"]; ?></label>
                            <input type="color" class="form-control" value="#ededed" name="color"><br>
                        </div>
                        <div class="col-md-6">
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PARENT"]; ?>:</label>
                            <select class="form-control js-example-basic-single" name="parent">
                                <option value=''>Keines</option>
                                <?php
                                $result = $conn->query("SELECT projectid, projectname FROM dynamicprojects");
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="'.$row["projectid"].'" >'.$row["projectname"].'</option>';
                                }
                                ?>
                            </select><br>
                        </div>
                    </div>
                    <div id="projectSeries" class="tab-pane fade"><br>
                        <div class="well">
                            <div class="row">
                                <div class="col-md-6">
                                    <label><?php echo $lang["BEGIN"]; ?></label>
                                    <input type='text' class="form-control datepicker" name='start' placeholder='Anfangsdatum' value="<?php echo date('Y-m-d'); ?>" /><br>
                                </div>
                                <div class="col-md-6">
                                    <label><?php echo $lang["END"]; ?></label><br>
                                    <label><input type="radio" name="endradio" value="" checked ><?php echo $lang["DYNAMIC_PROJECTS_SERIES_NO_END"]; ?></label><br>
                                    <input type="radio" name="endradio" value="date">
                                    <label><input type='text' class="form-control datepicker" name='enddate' placeholder="Enddatum" value="<?php echo date('Y-m-d'); ?>"/></label><br>
                                    <input type="radio" name="endradio" value="number" >
                                    <label><input type='number' class="form-control" name='endnumber' placeholder="<?php echo $lang["DYNAMIC_PROJECTS_SERIES_REPETITIONS"]; ?>" ></label><br>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12"><br> <!-- Once -->
                            <label><?php echo $lang["SCHEDULE_TOSTRING"][0]; ?></label><br>
                            <label><input type="radio" checked name="series" value="once" >Keine Wiederholungen</label><br>
                        </div>
                        <div class="col-md-12"><br> <!-- Daily -->
                            <label><?php echo $lang["SCHEDULE_TOSTRING"][1]; ?></label><br>
                            <input type="radio" name="series" value="daily_every_nth" >Jeden
                            <label><input class="form-control" type="number" min="1" max="365" value="1" name="daily_days"></label> -ten Tag
                            <br>
                            <input type="radio" name="series" value="daily_every_weekday" >Montag bis Freitag <br>
                        </div>
                        <div class="col-md-12"><br> <!-- Weekly -->
                            <label><?php echo $lang["SCHEDULE_TOSTRING"][2]; ?></label><br>
                            <input type="radio" name="series" value="weekly" >Alle
                            <label><input name="weekly_weeks" type="number" class="form-control" min="1" max="52" value="1" ></label> Wochen am
                            <label>
                                <select class="form-control" name="weekly_day">
                                    <?php
                                    $modal_weeks = '';
                                    $days_of_the_week = array("monday" => "Montag", "tuesday" => "Dienstag", "wednesday" => "Mittwoch", "thursday" => "Donnerstag", "friday" => "Freitag", "saturday" => "Samstag", "sunday" => "Sonntag");
                                    foreach ($days_of_the_week as $key => $val) {
                                        $modal_weeks .= "<option value='$key'>$val</option>";
                                    }
                                    echo $modal_weeks;
                                    ?>
                                </select>
                            </label>
                            <br>
                        </div>
                        <div class="col-md-12"><br> <!-- Monthly -->
                            <label><?php echo $lang["SCHEDULE_TOSTRING"][3]; ?></label><br>
                            <input type="radio" name="series" value="monthly_day_of_month">Am
                            <label><input name="monthly_day_of_month_day" class="form-control" value="1" type="number" min="1" max="31"></label> -ten Tag jedes
                            <label><input name="monthly_day_of_month_month" value="1" class="form-control" type="number" min="1" max="12"></label> -ten Monats
                            <br>
                            <input type="radio"  name="series" value="monthly_nth_day_of_week">Am
                            <label><input name="monthly_nth_day_of_week_nth" value="1" class="form-control" type="number" min="1" max="5"></label> -ten
                            <label>
                                <select class="form-control" name="monthly_nth_day_of_week_day">
                                    <?php echo $modal_weeks; ?>
                                </select>
                            </label> jeden
                            <label><input name="monthly_nth_day_of_week_month" value="1" class="form-control" type="number" min="1" max="12"></label> -ten Monat
                            <br>
                        </div>
                        <div class="col-md-12"><br> <!-- Yearly -->
                            <label><?php echo $lang["SCHEDULE_TOSTRING"][4]; ?></label><br>
                            <input type="radio" name="series" value="yearly_nth_day_of_month">Jeden
                            <label><input name="yearly_nth_day_of_month_nth" class="form-control" min="1" max="31" type="number"></label> -ten
                            <label>
                                <select class="form-control" name="yearly_nth_day_of_month_month" required>
                                    <?php
                                    $months_of_the_year = array("JAN" => "Jänner", "FEB" => "Februar", "MAR" => "März", "APR" => "April", "MAY" => "Mai", "JUN" => "Juni", "JUL" => "Juli", "AUG" => "August", "SEPT" => "September", "OCT" => "Oktober", "NOV" => "November", "DEC" => "Dezember");
                                    $modal_months = '';
                                    foreach ($months_of_the_year as $key => $val) $modal_months .= "<option value='$key'>$val</option>";
                                    echo $modal_months;
                                    ?>
                                </select>
                            </label>
                            <br>
                            <input type="radio" name="series" value="yearly_nth_day_of_week">Am
                            <label><input name="yearly_nth_day_of_week_nth" value="1" class="form-control" min="1" max="5" type="number"></label> -ten
                            <label>
                                <select class="form-control" name="yearly_nth_day_of_week_day">
                                    <?php echo $modal_weeks; ?>
                                </select>
                            </label> im
                            <label>
                                <select name="yearly_nth_day_of_week_month" class="form-control" name="month" required>
                                    <?php echo $modal_months; ?>
                                </select>
                            </label>
                            <br>
                        </div>
                    </div>
                </div>
            </div><!-- /modal-body -->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                <button type="submit" class="btn btn-warning show-required-fields" name="newDynamicProject" ><?php echo $lang['SAVE']; ?></button>
            </div>
            </form>
        </div>
    </div>
</div>


<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
function formatState (state) {
    if (!state.id) { return state.text; }
    var $state = $(
        '<span><i class="fa fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
    );
    return $state;
};
$(function(){
    $(".select2-team-icons").select2({
        templateResult: formatState,
        templateSelection: formatState
    });
})
$("#projectImageUpload").change(function (event) {
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
                $("#projectPreview").append(span);
                $("#projectPreview img").unbind("click").click(removeImg);
            };
        })(f);
        reader.readAsDataURL(f); // Read in the image file as a data URL.
    }
});
$(function () {
    $("#projectPreview img").click(removeImg);
})
function removeImg(event) {
    $(event.target).remove();
}
$("#projectForm").submit(function (event) {
    $("#projectPreview").find("input").remove()
    $("#projectPreview").find("img").each(function (index, elem) {
        console.log(getImageSrc(elem).length);
        $("#projectPreview").append("<input type='hidden' value='" + getImageSrc(elem) + "' name='imagesbase64[]'>");
    });
});
function getImageSrc(img) {
    var c = document.createElement("canvas");
    c.width = img.naturalWidth;
    c.height = img.naturalHeight;
    var ctx = c.getContext("2d");
    ctx.drawImage(img, 0, 0);
    return c.toDataURL();
}

$(".ask-before-submit").click(function askUser(event) {
    if(confirm("Are you sure?") && confirm("This can not be reverted")){
        return true;
    }
    event.preventDefault();
    return false;
});

$(".disable-required-fields").click(function (event){
    $("#projectForm input, #projectForm textarea, #projectForm select").filter("[required]").each(function(index,elem){
        $(elem).attr("required",false);
    });
    $("#projectForm select").each(function(index,elem){
        $(elem).attr("required",false);
    });
    $("#projectForm input").each(function(index,elem){
        if($(elem).attr("min") || $(elem).attr("max"))
        $(elem).attr("min", false);
        $(elem).attr("max", false);
    });
});

$(".show-required-fields").click(function (event){
    var fields = [];
    $("#projectForm input, #projectForm textarea, #projectForm select").filter("[required]").each(function(index,elem){
        if($(elem).val() == ""){
            var name = $(elem).attr("name");
            name = name.charAt(0).toUpperCase() + name.slice(1);
            name = name.replace("[]","");
            fields.push(name);
        }
    });
    if(fields.length) alert("Seems like you forgot following fields: "+fields.join(", "));
});

tinymce.init({
    selector: '#projectDescriptionEditor', //needs to be changed
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
</script>

<?php require 'footer.php'; ?>
