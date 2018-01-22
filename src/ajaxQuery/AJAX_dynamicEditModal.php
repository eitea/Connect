<?php
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

$x = $_GET['projectid'];

$userID = $_GET['userid'];

$result = $conn->query("SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while ($result && ($row = $result->fetch_assoc())) {
    $available_companies[] = $row['companyID'];
}

if($x){
    $result = $conn->query("SELECT * from dynamicprojects WHERE projectid = '$x'"); echo $conn->error;
    $dynrow = $result->fetch_assoc();

    $result = $conn->query("SELECT teamid FROM dynamicprojectsteams WHERE projectid = '$x'"); echo $conn->error;
    $dynrow_teams = array_column($result->fetch_all(MYSQLI_ASSOC), 'teamid');

    $result = $conn->query("SELECT userid, position FROM dynamicprojectsemployees WHERE projectid = '$x'"); echo $conn->error;
    $dynrow_emps = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='$dbName' AND `TABLE_NAME`='dynamicprojects'");
    $dynrow = array_fill_keys(array_column($result->fetch_all(MYSQLI_ASSOC), 'COLUMN_NAME'), '');
    $dynrow['projectcolor'] = '#efefef';
    $dynrow['projectstart'] = date('Y-m-d');
    $dynrow['projectpriority'] = 3;
    $dynrow['projectstatus'] = 'ACTIVE';
    $dynrow_teams = array('teamid' => '');
    $dynrow_emps = array('userid' => '', 'position' => '');
}
?>

<div class="modal fade" id="editingModal-<?php echo $x; ?>">
    <div class="modal-dialog modal-lg" role="form">
        <div class="modal-content">
            <form method="POST" id="projectForm<?php echo $x; ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Task editieren</h4>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#projectBasics<?php echo $x; ?>">Grundliegendes*</a></li>
                        <li><a data-toggle="tab" href="#projectDescription<?php echo $x; ?>"><?php echo $lang["DESCRIPTION"]; ?>*</a></li>
                        <li><a data-toggle="tab" href="#projectAdvanced<?php echo $x; ?>">Erweiterte Optionen</a></li>
                        <li><a data-toggle="tab" href="#projectSeries<?php echo $x; ?>">Routine Aufgabe</a></li>
                    </ul>
                    <div class="tab-content">
                        <div id="projectBasics<?php echo $x; ?>" class="tab-pane fade in active"><br>
                            <div class="row">
                                <?php
                                if(count($available_companies ) > 2){
                                    $result_fc = mysqli_query($conn, "SELECT id, name FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
                                    echo '<div class="col-sm-4"><label>'.$lang['COMPANY'].'</label><select class="js-example-basic-single" name="filterCompany" onchange="showClients(this.value, \''.$dynrow['clientid'].'\', \'clientHint'.$x.'\');" >';
                                    echo '<option value="0">...</option>';
                                    while($result && ($row_fc = $result_fc->fetch_assoc())){
                                        $checked = $dynrow['companyid'] == $row_fc['id'] ? 'selected' : '';
                                        echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
                                    }
                                    echo '</select></div>';
                                }
                                ?>
                                <div class="col-sm-4">
                                    <label><?php echo $lang['CLIENT']; ?></label>
                                    <select id="clientHint<?php echo $x; ?>" class="js-example-basic-single" name="filterClient" onchange="showProjects(this.value, '<?php echo $dynrow['clientprojectid']; ?>', 'projectHint<?php echo $x; ?>');">
                                        <?php
                                        if(count($available_companies) <= 2 ){
                                            $result_fc = $conn->query("SELECT id, name FROM clientData WHERE companyID IN (".implode(', ', $available_companies).");");
                                        } elseif($dynrow['companyid']){
                                            $result_fc = $conn->query("SELECT id, name FROM clientData WHERE companyID = '".$dynrow['companyid']."';");
                                        }
                                        echo '<option value="0">...</option>';
                                        while($result && ($row_fc = $result_fc->fetch_assoc())){
                                            $checked = $dynrow['clientid'] == $row_fc['id'] ? 'selected' : '';
                                            echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label><?php echo $lang['PROJECT']; ?></label>
                                    <select id="projectHint<?php echo $x; ?>" class="js-example-basic-single" name="filterProject">
                                        <?php
                                            if($dynrow['clientid']){
                                                $result_fc = $conn->query("SELECT id, name FROM projectData WHERE clientID = ". $dynrow['clientid']);
                                                while($result && ($row_fc = $result_fc->fetch_assoc())){
                                                    $checked = $dynrow['clientprojectid'] == $row_fc['id'] ? 'selected' : '';
                                                    echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12"><small>*Auswahl ist Optional. Falls kein Projekt angegeben, entscheidet der Benutzer.</small><br><br></div>
                            <div class="col-md-12"><label>Task Name*</label><input class="form-control required-field" type="text" name="name" placeholder="Bezeichnung" value="<?php echo $dynrow['projectname']; ?>" /><br></div>
                            <?php
                            $modal_options = '';
                            $result = $conn->query("SELECT id, firstname, lastname FROM UserData");
                            while ($row = $result->fetch_assoc()){ $modal_options .= '<option value="'.$row['id'].'" data-icon="user">'.$row['firstname'] .' '. $row['lastname'].'</option>'; }
                            ?>
                            <div class="col-md-4">
                                <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OWNER"]; ?>*</label>
                                <select class="select2-team-icons required-field" name="owner">
                                <?php echo str_replace('<option value="'.$dynrow['projectowner'].'" ', '<option selected value="'.$dynrow['projectowner'].'" ', $modal_options); ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label><?php echo $lang["EMPLOYEE"]; ?>*</label>
                                <select class="select2-team-icons required-field" name="employees[]" multiple="multiple">
                                    <?php
                                    $result = str_replace('<option value="', '<option value="user;', $modal_options); //append 'user;' before every value
                                    for($i = 0; $i < count($dynrow_emps); $i++){
                                        if($dynrow_emps[$i]['position'] == 'normal'){
                                            $result = str_replace('<option value="user;'.$dynrow_emps[$i]['userid'].'" ', '<option selected value="user;'.$dynrow_emps[$i]['userid'].'" ', $result);
                                        }
                                    }
                                    echo $result;
                                    $result = $conn->query("SELECT id, name FROM $teamTable");
                                    while ($row = $result->fetch_assoc()) {
                                        $selected = '';
                                        if(in_array($row['id'], $dynrow_teams)){
                                            $selected = 'selected';
                                        }
                                        echo '<option value="team;'.$row['id'].'" data-icon="group" '.$selected.' >'.$row['name'].'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OPTIONAL_EMPLOYEES"]; ?></label>
                                <select class="select2-team-icons" name="optionalemployees[]" multiple="multiple">
                                    <?php
                                    $result = $modal_options;
                                    for($i = 0; $i < count($dynrow_emps); $i++){
                                        if($dynrow_emps[$i]['position'] == 'optional')
                                        $result = str_replace('<option value="'.$dynrow_emps[$i]['userid'].'" ', '<option selected value="'.$dynrow_emps[$i]['userid'].'" ', $result);
                                    }
                                    echo $result;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div id="projectDescription<?php echo $x; ?>" class="tab-pane fade"><br>
                            <label><?php echo $lang["DESCRIPTION"]; ?>*</label>
                            <textarea class="form-control projectDescriptionEditor" rows="10" name="description" ><?php echo $dynrow['projectdescription']; ?></textarea>
                            <br>
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PICTURES"]; ?></label>
                            <br>
                            <label class="btn btn-default" role="button"><?php echo $lang["DYNAMIC_PROJECTS_CHOOSE_PICTURES"]; ?>
                                <input type="file" name="images" multiple class="form-control" style="display:none;" id="projectImageUpload" accept=".jpg,.jpeg,.png">
                            </label>
                            <div id="projectPreview<?php echo $x; ?>">
                                <?php
                                if(isset($_POST['imagesbase64'])){
                                    foreach ($_POST['imagesbase64'] as $modal_picture) {
                                        echo "<span><img src='$modal_picture' alt='Previously uploaded' class='img-thumbnail' style='width:48%;margin:0.45%'></span>";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div id="projectAdvanced<?php echo $x; ?>" class="tab-pane fade"><br>
                            <div class="col-md-6">
                                <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?>*</label>
                                <select class="form-control js-example-basic-single" name="priority">
                                    <option value='1' <?php if($dynrow['projectpriority'] == 1) echo 'selected'; ?> >Sehr niedrig</option>
                                    <option value='2' <?php if($dynrow['projectpriority'] == 2) echo 'selected'; ?> >Niedrig</option>
                                    <option value='3' <?php if($dynrow['projectpriority'] == 3) echo 'selected'; ?> >Normal</option>
                                    <option value='4' <?php if($dynrow['projectpriority'] == 4) echo 'selected'; ?> >Hoch</option>
                                    <option value='5' <?php if($dynrow['projectpriority'] == 5) echo 'selected'; ?> >Sehr hoch</option>
                                </select><br>
                            </div>
                            <div class="col-md-6">
                                <label>Status*</label>
                                <div class="input-group">
                                    <select class="form-control" name="status" >
                                        <option value="DEACTIVATED" <?php if($dynrow['projectstatus'] == "DEACTIVATED") echo 'selected'; ?>>Deaktiviert</option>
                                        <option value="ACTIVE" <?php if($dynrow['projectstatus'] == 'ACTIVE') echo 'selected'; ?>>Aktiv</option>
                                        <option value="DRAFT" <?php if($dynrow['projectstatus'] == 'DRAFT') echo 'selected'; ?>>Entwurf</option>
                                        <option value="COMPLETED" <?php if($dynrow['projectstatus'] == 'COMPLETED') echo 'selected'; ?>>Abgeschlossen</option>
                                    </select>
                                    <span class="input-group-addon text-warning"><?php echo $lang["DYNAMIC_PROJECTS_PERCENTAGE_FINISHED"]; ?></span>
                                    <input type='number' class="form-control" name='completed' value="<?php echo $dynrow['projectpercentage']; ?>" min="0" max="100" step="1"/>
                                </div><br>
                            </div>
                            <div class="col-md-6">
                                <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_COLOR"]; ?></label>
                                <input type="color" class="form-control" value="<?php echo $dynrow['projectcolor']; ?>" name="color"><br>
                            </div>
                            <div class="col-md-6">
                                <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PARENT"]; ?>:</label>
                                <select class="form-control js-example-basic-single" name="parent">
                                    <option value=''>Keines</option>
                                    <?php
                                    $result = $conn->query("SELECT projectid, projectname FROM dynamicprojects");
                                    while ($row = $result->fetch_assoc()) {
                                        $selected = ($row['projectid'] == $dynrow['projectowner']) ? 'selected' : '';
                                        echo '<option '.$selected.' value="'.$row["projectid"].'" >'.$row["projectname"].'</option>';
                                    }
                                    ?>
                                </select><br>
                            </div>
                        </div>
                        <div id="projectSeries<?php echo $x; ?>" class="tab-pane fade"><br>
                            <div class="well">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label><?php echo $lang["BEGIN"]; ?></label>
                                        <input type='text' class="form-control datepicker" name='start' placeholder='Anfangsdatum' value="<?php echo $dynrow['projectstart']; ?>" /><br>
                                    </div>
                                    <div class="col-md-6">
                                        <label><?php echo $lang["END"]; ?></label><br>
                                        <label><input type="radio" name="endradio" value="" checked ><?php echo $lang["DYNAMIC_PROJECTS_SERIES_NO_END"]; ?></label><br>
                                        <input type="radio" name="endradio" value="date">
                                        <label><input type='text' class="form-control datepicker" name='enddate' placeholder="Enddatum" value="<?php echo $dynrow['projectend']; ?>"/></label><br>
                                        <input type="radio" name="endradio" value="number" >
                                        <label><input type='number' class="form-control" name='endnumber' placeholder="<?php echo $lang["DYNAMIC_PROJECTS_SERIES_REPETITIONS"]; ?>" ></label><br>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12"><br> <!-- Once -->
                                <label><?php echo $lang["SCHEDULE_TOSTRING"][0]; ?></label><br>
                                <label><input type="radio"checked name="series" value="once" >Keine Wiederholungen</label><br>
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
                                <label><input name="yearly_nth_day_of_month_nth" class="form-control" min="1" max="31" type="number" value="1"></label> -ten
                                <label>
                                    <select class="form-control" name="yearly_nth_day_of_month_month">
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
                                    <select name="yearly_nth_day_of_week_month" class="form-control" name="month">
                                        <?php echo $modal_months; ?>
                                    </select>
                                </label>
                                <br>
                            </div>
                        </div>
                    </div><!-- /tab-content -->
                </div><!-- /modal-body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning" name="editDynamicProject" value="<?php echo $x; ?>" ><?php echo $lang['SAVE']; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
