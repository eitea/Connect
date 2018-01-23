<?php
//This template is a modal for editing new and existing dynamic projects
if (!function_exists('stripSymbols')) {
    function stripSymbols($s)
    {
        $result = "";
        foreach (str_split($s) as $char) {
            if (ctype_alnum($char)) {
                $result = $result . $char;
            }
        }
        return $result;
    }
}
$disabled = $modal_isAdmin ? "" : "disabled";

?>
    <button class="btn btn-default" data-toggle="modal" data-target="#dynamicProject<?php echo stripSymbols($modal_id) ?>" type="button">
        <i class="<?php echo $modal_symbol; ?>"></i>
    </button>


    <!-- new dynamic project modal -->
    <form method="post" autocomplete="off" id="projectForm<?php echo stripSymbols($modal_id) ?>">
        <input type="hidden" name="id" value="<?php echo $modal_id ?>">
        <div class="modal fade" id="dynamicProject<?php echo stripSymbols($modal_id) ?>" tabindex="-1" role="dialog" aria-labelledby="dynamicProjectLabel<?php echo stripSymbols($modal_id) ?>">
            <div class="modal-dialog modal-lg" role="form">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="dynamicProjectLabel<?php echo stripSymbols($modal_id) ?>">
                            <?php echo $modal_title; ?>
                        </h4>
                    </div>
                    <!-- modal body -->
                    <!-- tab buttons -->
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a data-toggle="tab" href="#projectBasics<?php echo stripSymbols($modal_id) ?>"><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_BASICS"]; ?>*</a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#projectDescription<?php echo stripSymbols($modal_id) ?>"><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_DESCRIPTION"]; ?>*</a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#projectAdvanced<?php echo stripSymbols($modal_id) ?>"><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_ADVANCED"]; ?></a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#projectSeries<?php echo stripSymbols($modal_id) ?>"><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_SERIES"]; ?></a>
                        </li>
                    </ul>
                    <!-- /tab buttons -->
                    <div class="tab-content">
                        <div id="projectBasics<?php echo stripSymbols($modal_id) ?>" class="tab-pane fade in active">
                            <div class="modal-body">
                                <!-- basics -->
                                <div class="well">
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_NAME"]; ?>*:</label>
                                    <input class="form-control" type="text" name="name" <?php echo $disabled ?> required value="<?php echo $modal_name; ?>">
                                    <?php if ($conn->query("SELECT count(*) count FROM $companyTable WHERE id IN (" . implode(', ', $available_companies) . ") ")->fetch_assoc()["count"] != 1) {?>
                                        <!-- more than one company -->
                                        <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_COMPANY"]; ?>*:</label>
                                        <select class="form-control js-example-basic-single" name="company"  <?php echo $disabled ?> required onchange="showClients(this.value, 0,'#dynamicProjectClients<?php echo stripSymbols($modal_id) ?>')">
                                            <option value="">...</option>
                                            <?php
$modal_result = $conn->query("SELECT * FROM $companyTable WHERE id IN (" . implode(', ', $available_companies) . ") ");
    while ($modal_row = $modal_result->fetch_assoc()) {
        $companyID = $modal_row["id"];
        $companyName = $modal_row["name"];
        $selected = $companyID == $modal_company && $modal_company != "" ? "selected" : "";
        echo "<option $selected value='$companyID'>$companyName</option>";
    }
    ?>
                                                                                </select>
                                        <?php } else {
    $modal_company = $conn->query("SELECT * FROM $companyTable WHERE id IN (" . implode(', ', $available_companies) . ") ")->fetch_assoc()["id"];
    ?>

	                                        <input type="hidden" name="company" value="<?php echo $modal_company; ?>"/>
	                                        <?php if (empty($modal_clients)): ?>
	                                        <script>
	                                        $(document).ready(function(){
	                                            showClients(<?php echo $modal_company; ?>, 0,'#dynamicProjectClients<?php echo stripSymbols($modal_id) ?>')
	                                        })
	                                        </script>
	                                        <?php endif;?>
                                        <?php }?>
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_CLIENTS"]; ?>*:</label>
                                    <select id="dynamicProjectClients<?php echo stripSymbols($modal_id) ?>"  <?php echo $disabled ?> class="form-control select2-team-icons" name="clients[]"
                                        multiple="multiple" required>
                                        <?php
if (!empty($modal_clients) && !empty($modal_company)) {
    $modal_clientsResult = $conn->query("SELECT * FROM $clientTable WHERE companyID = $modal_company");
    while ($modal_clientRow = $modal_clientsResult->fetch_assoc()) {
        $modal_client_id = $modal_clientRow["id"];
        $modal_client = $modal_clientRow["name"];
        $selected = in_array($modal_client_id, $modal_clients) ? "selected" : "";
        echo "<option $selected value='$modal_client_id' data-icon='address-card'>$modal_client</option>";
    }
} else {
    echo "<option>Zuerst Mandant auswählen</option>";
}
?>
                                    </select>
                                </div>
                                <div class="well">
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OWNER"]; ?>*:</label>
                                    <select class="form-control select2-team-icons"  <?php echo $disabled ?>  name="owner" required>
                                        <?php
$modal_result = $conn->query("SELECT * FROM UserData");
while ($modal_row = $modal_result->fetch_assoc()) {
    $x = $modal_row['id'];
    $modal_user_name = "${modal_row['firstname']} ${modal_row['lastname']}";
    if (!empty($modal_owner)) {
        $selected = $modal_owner == $x ? "selected" : "";
    } else {
        $selected = $x == $userID ? "selected" : "";
    }
    echo "<option value='$x' $selected data-icon='user'>$modal_user_name</option>";
}
?>
                                    </select>
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_EMPLOYEES"]; ?>*:</label>
                                    <select class="form-control select2-team-icons" <?php echo $disabled ?>  name="employees[]" multiple="multiple" required>
                                        <?php
$modal_result = $conn->query("SELECT * FROM UserData");
while ($modal_row = $modal_result->fetch_assoc()) {
    $x = $modal_row['id'];
    $modal_user_name = "${modal_row['firstname']} ${modal_row['lastname']}";
    if (!empty($modal_employees)) {
        $selected = in_array("user;" . $x, $modal_employees) ? "selected" : "";
    } else {
        $selected = "";
    }
    echo "<option value='user;$x' $selected data-icon='user'>$modal_user_name</option>";
}
//teams
$modal_result = $conn->query("SELECT * FROM $teamTable");
while ($modal_row = $modal_result->fetch_assoc()) {
    $x = $modal_row["id"];
    $selected = in_array("team;" . $x, $modal_employees) ? "selected" : "";
    $modal_team_name = $modal_row["name"];
    echo "<option value='team;$x' $selected data-icon='group'>$modal_team_name</option>";
}

?>
                                    </select>


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

            </script>





                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OPTIONAL_EMPLOYEES"]; ?>:</label>
                                    <select class="form-control select2-team-icons" <?php echo $disabled ?>  name="optionalemployees[]" multiple="multiple">
                                        <?php
$modal_result = $conn->query("SELECT * FROM UserData");
while ($modal_row = $modal_result->fetch_assoc()) {
    $x = $modal_row['id'];
    $selected = "";
    $modal_user_name = "${modal_row['firstname']} ${modal_row['lastname']}";
    if (!empty($modal_optional_employees)) {
        $selected = in_array($x, $modal_optional_employees) ? "selected" : "";
    }
    echo "<option value='$x' $selected data-icon='user'>$modal_user_name</option>";
}
?>
                                    </select>
                                </div>
                                <!-- /basics -->
                            </div>
                        </div>
                        <div id="projectDescription<?php echo stripSymbols($modal_id) ?>" class="tab-pane fade">
                            <div class="modal-body">
                                <!-- description -->
                                <div class="well">
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_DESCRIPTION"]; ?>*:</label>
                                    <textarea id="projectDescriptionEditor<?php echo stripSymbols($modal_id) ?>" class="form-control" style="max-width: 100%" rows="10"  <?php echo $disabled ?>  name="description" required><?php echo $modal_description; ?></textarea>
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PICTURES"]; ?>:</label>
                                    <br>
                                    <label class="btn btn-default" role="button"><?php echo $lang["DYNAMIC_PROJECTS_CHOOSE_PICTURES"]; ?>
                                        <input type="file" name="images" multiple class="form-control" style="display:none;" id="projectImageUpload<?php echo stripSymbols($modal_id) ?>"
                                            accept=".jpg,.jpeg,.png">
                                    </label>
                                    <div id="projectPreview<?php echo stripSymbols($modal_id) ?>">
                                        <?php
foreach ($modal_pictures as $modal_picture) {
    echo "<span><img src='$modal_picture' alt='Previously uploaded' class='img-thumbnail' style='width:48%;margin:0.45%'></span>";
}
?>
                                    </div>
                                </div>
                                <!-- /description -->
                            </div>
                        </div>
                        <div id="projectAdvanced<?php echo stripSymbols($modal_id) ?>" class="tab-pane fade">
                            <div class="modal-body">
                                <!-- advanced -->
                                <div class="well">
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?>*:</label>
                                    <select class="form-control js-example-basic-single" <?php echo $disabled ?>  name="priority" required>
                                        <?php
$modal_priorities = array(1 => "Sehr niedrig", 2 => "Niedrig", 3 => "Normal", 4 => "Hoch", 5 => "Sehr hoch");
for ($i = 1; $i <= 5; $i++) {
    $selected = $modal_priority == $i ? "selected" : "";
    echo "<option $selected value='$i'>${modal_priorities[$i]}</option>";
}
?>
                                    </select>
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_STATUS"]; ?>*:</label>
                                    <div class="input-group">
                                        <select class="form-control" name="status" required <?php echo $disabled ?> >
                                            <option value="DEACTIVATED" <?php echo $modal_status == 'DEACTIVATED' ? "selected" : "" ?> >Deaktiviert</option>
                                            <option value="DRAFT" <?php echo $modal_status == 'DRAFT' ? "selected" : "" ?> >Entwurf</option>
                                            <option value="ACTIVE" <?php echo $modal_status == 'ACTIVE' ? "selected" : "" ?> >Aktiv</option>
                                            <option value="COMPLETED" <?php echo $modal_status == 'COMPLETED' ? "selected" : "" ?> >Abgeschlossen</option>
                                        </select>
                                        <span class="input-group-addon text-warning"><?php echo $lang["DYNAMIC_PROJECTS_PERCENTAGE_FINISHED"]; ?></span>
                                        <input type='number' class="form-control" name='completed' value="<?php echo $modal_completed; ?>" min="0" max="100" step="1"
                                            required/>
                                    </div>
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_COLOR"]; ?>:</label>
                                    <input <?php echo $disabled ?>  type="color" class="form-control" value="<?php echo $modal_color; ?>" name="color">
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PARENT"]; ?>:</label>
                                    <select class="form-control js-example-basic-single" name="parent" <?php echo $disabled ?>  required>
                                        <?php
$parentResult = $conn->query("SELECT * FROM dynamicprojects");
if ($modal_parent == "" || $modal_parent == "none") {
    echo "<option value='none' selected>Keines</option>";
}
while ($parentRow = $parentResult->fetch_assoc()) {
    $parentID = $parentRow["projectid"];
    $parentSelected = ($parentID == $modal_parent) ? "selected" : "";
    $parentName = $parentRow["projectname"];
    echo "<option $parentSelected value='$parentID' >$parentName</option>";
}
?>
                                    </select>
                                </div>
                                <!-- /advanced -->
                            </div>
                        </div>
                        <div id="projectSeries<?php echo stripSymbols($modal_id) ?>" class="tab-pane fade">
                            <div class="modal-body">
                                <!-- series -->

                                <div class="well">
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_SERIES_START"]; ?>:</label>
                                    <input type='text' class="form-control datepicker" name='localPart' placeholder='Startdatum' <?php echo $disabled ?>  value="<?php echo $modal_start; ?>"
                                    />
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_SERIES_END"]; ?>:</label>
                                    <br>
                                    <?php if ($modal_end == ""): ?>
                                    <label>
                                        <input type="radio" name="endradio" value="no" <?php echo $disabled ?>  checked><?php echo $lang["DYNAMIC_PROJECTS_SERIES_NO_END"]; ?></label>
                                    <br>
                                    <?php else: ?>
                                    <label>
                                        <input type="radio" name="endradio" value="no" <?php echo $disabled ?> ><?php echo $lang["DYNAMIC_PROJECTS_SERIES_NO_END"]; ?></label>
                                    <br>
                                    <?php endif;?>
                                    <?php if (preg_match("/\d{4}-\d{2}-\d{2}/", $modal_end)): ?>
                                    <input type="radio" name="endradio" value="date" checked <?php echo $disabled ?> >
                                    <label>
                                        <input type='text' class="form-control datepicker" <?php echo $disabled ?>  name='enddate' placeholder="Enddatum" value="<?php echo $modal_end; ?>"
                                        />
                                    </label>
                                    <br>
                                    <?php else: ?>
                                    <input type="radio" name="endradio" <?php echo $disabled ?>  value="date">
                                    <label>
                                        <input type='text' class="form-control datepicker" <?php echo $disabled ?>  name='enddate' placeholder="Enddatum" value="<?php echo date('Y-m-d'); ?>"
                                        />
                                    </label>
                                    <br>
                                    <?php endif;?>
                                    <?php if (preg_match("/^\d+$/", $modal_end)): ?>
                                    <input type="radio" name="endradio" value="number" checked <?php echo $disabled ?> >
                                    <label>
                                        <input type='number' class="form-control" name='endnumber' placeholder="<?php echo $lang["DYNAMIC_PROJECTS_SERIES_REPETITIONS"]; ?>" value="<?php echo $modal_end ?>" <?php echo $disabled ?> >
                                    </label>
                                    <?php else: ?>
                                    <input type="radio" name="endradio" value="number" <?php echo $disabled ?> >
                                    <label>
                                        <input type='number' class="form-control" name='endnumber' placeholder="<?php echo $lang["DYNAMIC_PROJECTS_SERIES_REPETITIONS"]; ?>" <?php echo $disabled ?> >
                                    </label>
                                    <?php endif;?>
                                </div>

                                <div class="well">
                                    <?php
$modal_series = empty($modal_series) ? new ProjectSeries("", "", "") : $modal_series;
?>
                                        <label><?php echo $lang["DYNAMIC_PROJECTS_SERIES_ONCE"]; ?></label>
                                        <br>
                                        <input type="radio" <?php echo $modal_series->once ? "checked" : ""; ?> name="series" value="once" <?php echo $disabled ?> >Keine Wiederholungen
                                        <br>

                                        <label><?php echo $lang["DYNAMIC_PROJECTS_SERIES_DAILY"]; ?></label>
                                        <br>
                                        <input type="radio" <?php echo $modal_series->daily_every_nth ? "checked" : ""; ?> name="series" value="daily_every_nth" <?php echo $disabled ?> >Alle
                                        <label>
                                            <input class="form-control" type="number" min="1" max="365" value="<?php echo $modal_series->daily_days; ?>" name="daily_days" <?php echo $disabled ?> >
                                        </label> Tage
                                        <br>
                                        <input type="radio" <?php echo $modal_series->daily_every_weekday ? "checked" : ""; ?> name="series" value="daily_every_weekday" <?php echo $disabled ?> >Montag
                                        bis Freitag
                                        <br>


                                        <label><?php echo $lang["DYNAMIC_PROJECTS_SERIES_WEEKLY"]; ?></label>
                                        <br>
                                        <input type="radio" <?php echo $modal_series->weekly ? "checked" : ""; ?> name="series" value="weekly" <?php echo $disabled ?> >Alle
                                        <label>
                                            <input class="form-control" type="number" max="52" value="<?php echo $modal_series->weekly_weeks; ?>" name="weekly_weeks" <?php echo $disabled ?> >
                                        </label> Wochen am
                                        <label>
                                            <select class="form-control" name="weekly_day" required <?php echo $disabled ?> >
                                                <?php
$days_of_the_week = array("monday" => "Montag", "tuesday" => "Dienstag", "wednesday" => "Mittwoch", "thursday" => "Donnerstag", "friday" => "Freitag", "saturday" => "Samstag", "sunday" => "Sonntag");
foreach (array_keys($days_of_the_week) as $day) {
    $selected = $modal_series->weekly_day == $day ? "selected" : "";
    echo "<option $selected value='$day'>${days_of_the_week[$day]}</option>";
}
?>
                                            </select>
                                        </label>
                                        <br>


                                        <label><?php echo $lang["DYNAMIC_PROJECTS_SERIES_MONTHLY"]; ?></label>
                                        <br>
                                        <input type="radio" <?php echo $modal_series->monthly_day_of_month ? "checked" : ""; ?> name="series" value="monthly_day_of_month" <?php echo $disabled ?> >am
                                        <label>
                                            <input name="monthly_day_of_month_day" class="form-control" value="<?php echo $modal_series->monthly_day_of_month_day; ?>"
                                                type="number" min="1" max="31" <?php echo $disabled ?> >
                                        </label> Tag jedes
                                        <label>
                                            <input <?php echo $disabled ?>  name="monthly_day_of_month_month" value="<?php echo $modal_series->monthly_day_of_month_month; ?>" class="form-control"
                                                type="number" min="1" max="12">
                                        </label>. Monats
                                        <br>
                                        <input <?php echo $disabled ?>  type="radio" <?php echo $modal_series->monthly_nth_day_of_week ? "checked" : ""; ?> name="series" value="monthly_nth_day_of_week">am
                                        <label>
                                            <input <?php echo $disabled ?>  name="monthly_nth_day_of_week_nth" value="<?php echo $modal_series->monthly_nth_day_of_week_nth; ?>" class="form-control"
                                                type="number" min="1" max="5">
                                        </label>
                                        <label>
                                            <select class="form-control" name="monthly_nth_day_of_week_day" required <?php echo $disabled ?> >
                                                <?php
$days_of_the_week = array("monday" => "Montag", "tuesday" => "Dienstag", "wednesday" => "Mittwoch", "thursday" => "Donnerstag", "friday" => "Freitag", "saturday" => "Samstag", "sunday" => "Sonntag");
foreach (array_keys($days_of_the_week) as $day) {
    $selected = $modal_series->monthly_nth_day_of_week_day == $day ? "selected" : "";
    echo "<option $selected value='$day'>${days_of_the_week[$day]}</option>";
}
?>
                                            </select>
                                        </label> jedes
                                        <label>
                                            <input <?php echo $disabled ?>  name="monthly_nth_day_of_week_month" value="<?php echo $modal_series->monthly_nth_day_of_week_month; ?>" class="form-control"
                                                type="number" min="1" max="12">
                                        </label> monats
                                        <br>


                                        <label><?php echo $lang["DYNAMIC_PROJECTS_SERIES_YEARLY"]; ?></label>
                                        <br>
                                        <input  <?php echo $disabled ?> type="radio" <?php echo $modal_series->yearly_nth_day_of_month ? "checked" : ""; ?> name="series" value="yearly_nth_day_of_month">jeden
                                        <label>
                                            <input  <?php echo $disabled ?> name="yearly_nth_day_of_month_nth" value="<?php echo $modal_series->yearly_nth_day_of_month_nth; ?>" class="form-control"
                                                min="1" max="31" type="number">
                                        </label>.
                                        <label>
                                            <select class="form-control" name="yearly_nth_day_of_month_month" required <?php echo $disabled ?> >
                                                <?php
$months_of_the_year = array("JAN" => "Jänner", "FEB" => "Februar", "MAR" => "März", "APR" => "April", "MAY" => "Mai", "JUN" => "Juni", "JUL" => "Juli", "AUG" => "August", "SEPT" => "September", "OCT" => "Oktober", "NOV" => "November", "DEC" => "Dezember");
foreach (array_keys($months_of_the_year) as $month) {
    $selected = $modal_series->yearly_nth_day_of_month_month == $month ? "selected" : "";
    echo "<option $selected value='$month'>${months_of_the_year[$month]}</option>";
}
?>
                                            </select>
                                        </label>
                                        <br>
                                        <input <?php echo $disabled ?>  type="radio" <?php echo $modal_series->yearly_nth_day_of_week ? "checked" : ""; ?> name="series" value="yearly_nth_day_of_week">am
                                        <label>
                                            <input <?php echo $disabled ?>  name="yearly_nth_day_of_week_nth" value="<?php echo $modal_series->yearly_nth_day_of_week_nth; ?>" class="form-control"
                                                min="1" max="5" type="number">
                                        </label>.
                                        <label>
                                            <select class="form-control" name="yearly_nth_day_of_week_day" required <?php echo $disabled ?> >
                                                <?php
$days_of_the_week = array("monday" => "Montag", "tuesday" => "Dienstag", "wednesday" => "Mittwoch", "thursday" => "Donnerstag", "friday" => "Freitag", "saturday" => "Samstag", "sunday" => "Sonntag");
foreach (array_keys($days_of_the_week) as $day) {
    $selected = $modal_series->yearly_nth_day_of_week_day == $day ? "selected" : "";
    echo "<option $selected value='$day'>${days_of_the_week[$day]}</option>";
}
?>
                                            </select>
                                        </label> im
                                        <label>
                                            <select name="yearly_nth_day_of_week_month" class="form-control" name="month" required <?php echo $disabled ?> >
                                                <?php
$months_of_the_year = array("JAN" => "Jänner", "FEB" => "Februar", "MAR" => "März", "APR" => "April", "MAY" => "Mai", "JUN" => "Juni", "JUL" => "Juli", "AUG" => "August", "SEPT" => "September", "OCT" => "Oktober", "NOV" => "November", "DEC" => "Dezember");
foreach (array_keys($months_of_the_year) as $month) {
    $selected = $modal_series->yearly_nth_day_of_week_month == $month ? "selected" : "";
    echo "<option $selected value='$month'>${months_of_the_year[$month]}</option>";
}
?>
                                            </select>
                                        </label>
                                        <br>


                                </div>


                                <!-- /series -->
                            </div>
                        </div>
                    </div>
                    <!-- /modal body -->
                    <div class="modal-footer">
                        <?php if ($modal_id && $modal_isAdmin): ?>
                        <button type="submit" class="btn btn-danger ask-before-submit<?php echo stripSymbols($modal_id) ?> disable-required-fields<?php echo stripSymbols($modal_id) ?>" name="deleteDynamicProject">
                            <?php echo $lang["DELETE"]; ?>
                        </button>
                        <?php endif;?>
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo $lang['CANCEL']; ?>
                        </button>
                                    <button type="submit" class="btn btn-warning show-required-fields<?php echo stripSymbols($modal_id) ?>" <?php if ($modal_id): ?> name="editDynamicProject" <?php else: ?> name="dynamicProject" <?php endif;?>  >
                            <?php echo $lang['SAVE']; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- /new dynamic project modal -->
    <script>
        function showClients(company, client, targetSelector) {
            $.ajax({
                url: 'ajaxQuery/AJAX_getClient.php',
                data: { companyID: company, clientID: client },
                type: 'get',
                success: function (resp) {
                    $(targetSelector).html(resp);
                },
                error: function (resp) { },
            });
        }

        $("#projectImageUpload<?php echo stripSymbols($modal_id) ?>").change(function (event) {
            var files = event.target.files;
            //$("#newProjectPreview").html(""); //delete old pictures
            // Loop through the FileList and render image files as thumbnails.
            for (var i = 0, f; f = files[i]; i++) {
                // Only process image files.
                if (!f.type.match('image.*')) {
                    continue;
                }
                var reader = new FileReader();
                // Closure to capture the file information.
                reader.onload = (function (theFile) {
                    return function (e) {
                        // Render thumbnail.
                        var span = document.createElement('span');
                        span.innerHTML =
                            [
                                '<img class="img-thumbnail" style="width:49%;margin:0.5%" src="',
                                e.target.result,
                                '" title="', escape(theFile.name),
                                '"/>'
                            ].join('');
                        $("#projectPreview<?php echo stripSymbols($modal_id) ?>").append(span);
                        $("#projectPreview<?php echo stripSymbols($modal_id) ?> img").unbind("click").click(removeImg)
                    };
                })(f);
                // Read in the image file as a data URL.
                reader.readAsDataURL(f);
            }

        });

        $(function () {
            $("#projectPreview<?php echo stripSymbols($modal_id) ?> img").click(removeImg)
        })

        function removeImg(event) {
            $(event.target).remove()
        }

        $("#projectForm<?php echo stripSymbols($modal_id) ?>").submit(function (event) {
            $("#projectPreview<?php echo stripSymbols($modal_id) ?>").find("input").remove()
            $("#projectPreview<?php echo stripSymbols($modal_id) ?>").find("img").each(function (index, elem) {
                console.log(getImageSrc(elem).length)
                $("#projectPreview<?php echo stripSymbols($modal_id) ?>").append("<input type='hidden' value='" + getImageSrc(elem) + "' name='imagesbase64[]'>")
            })
        })

        function getImageSrc(img) {
            var c = document.createElement("canvas");
            c.width = img.naturalWidth;
            c.height = img.naturalHeight;
            var ctx = c.getContext("2d");
            ctx.drawImage(img, 0, 0);
            return c.toDataURL();
        }
        $(function () {
            $(".ask-before-submit<?php echo stripSymbols($modal_id) ?>").click(function askUser(event) {
                if(confirm("Are you sure?") && confirm("This can not be reverted")){
                    return true;
                }
                event.preventDefault();
                return false;
            });

            $(".disable-required-fields<?php echo stripSymbols($modal_id) ?>").click(function (event){
                 $("#projectForm<?php echo stripSymbols($modal_id) ?> input, #projectForm<?php echo stripSymbols($modal_id) ?> textarea, #projectForm<?php echo stripSymbols($modal_id) ?> select").filter("[required]").each(function(index,elem){
                     $(elem).attr("required",false);
                 })
                 $("#projectForm<?php echo stripSymbols($modal_id) ?> select").each(function(index,elem){
                     $(elem).attr("required",false);
                 })
                 $("#projectForm<?php echo stripSymbols($modal_id) ?> input").each(function(index,elem){
                     if($(elem).attr("min") || $(elem).attr("max"))
                     $(elem).attr("min", false);
                     $(elem).attr("max", false);
                 })
            })

            $(".show-required-fields<?php echo stripSymbols($modal_id) ?>").click(function (event){
                 var fields = [];
                 $("#projectForm<?php echo stripSymbols($modal_id) ?> input, #projectForm<?php echo stripSymbols($modal_id) ?> textarea, #projectForm<?php echo stripSymbols($modal_id) ?> select").filter("[required]").each(function(index,elem){
                    if($(elem).val() == ""){
                        var name = $(elem).attr("name");
                        name = name.charAt(0).toUpperCase() + name.slice(1);
                        name = name.replace("[]","");
                        fields.push(name);
                    }
                 })
                 if(fields.length) alert("Seems like you forgot following fields: "+fields.join(", "));
            })
            $("#dynamicProject<?php echo stripSymbols($modal_id) ?>").on('hidden.bs.modal', function () {
                window.location.reload()
            });
        })


    </script>





<!-- text editor test -->
<script>
tinymce.init({
  selector: '#projectDescriptionEditor<?php echo stripSymbols($modal_id) ?>', //needs to be changed
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

    // Note: In modern browsers input[type="file"] is functional without
    // even adding it to the DOM, but that might not be the case in some older
    // or quirky browsers like IE, so you might want to add it to the DOM
    // just in case, and visually hide it. And do not forget do remove it
    // once you do not need it anymore.

    input.onchange = function() {
      var file = this.files[0];

      var reader = new FileReader();
      reader.onload = function () {
        // Note: Now we need to register the blob in TinyMCEs image blob
        // registry. In the next release this part hopefully won't be
        // necessary, as we are looking to handle it internally.
        var id = 'blobid' + (new Date()).getTime();
        var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
        mimeType = (reader.result.split(";")[0].split(":")[1]) //mime type
        var base64 = reader.result.split(',')[1];
        var blobInfo = blobCache.create(id, file, base64);
        blobCache.add(blobInfo);
        // call the callback and populate the Title field with the file name
        cb(blobInfo.blobUri(), { title: file.name, text:file.name,alt:file.name,source:"images/Question_Circle.jpg",poster:"images/Question_Circle.jpg" });
      };
      reader.readAsDataURL(file);
    };

    input.click();
  },
  relative_urls: true,
  images_upload_handler: function (blobInfo, success, failure) {
      var info = (blobInfo.blob())
      var mimeType = info.type
      success("=data:"+mimeType+";base64,"+blobInfo.base64()) // '=' is not valid bases64 (prevents not displaying non image files)
    
      failure()
  },
  relative_urls: true,
  init_instance_callback: function (editor) {
    editor.on('click', function (event) {
        if(event.target.nodeName.toLowerCase() != "img")
            return
      console.log('Element clicked:', event.target.nodeName);
      src = event.target.src.split("=")[1]
      downloadBase64File(src, event.target.alt) // src contains data:mime;base64...
    });
  }


});

function downloadBase64File(src, filename){
    var element = document.createElement('a');
  element.setAttribute('href', src);
  element.setAttribute('download', filename);
  element.style.display = 'none';
  document.body.appendChild(element);
  element.click();
  document.body.removeChild(element);
} 
</script>
