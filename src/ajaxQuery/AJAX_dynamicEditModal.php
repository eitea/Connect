<?php
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";
if(!$_SERVER['REQUEST_METHOD'] == 'POST'){
    die("0");
}
$x = preg_replace("/[^A-Za-z0-9]/", '', $_POST['projectid']);
$isDynamicProjectsAdmin = $_POST['isDPAdmin'];
session_start();
$userID = $_SESSION["userid"] or die("0");

$result = $conn->query("SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while ($result && ($row = $result->fetch_assoc())) {
    $available_companies[] = $row['companyID'];
}

$result = $conn->query("SELECT DISTINCT userID FROM $companyToUserRelationshipTable WHERE companyID IN(" . implode(', ', $available_companies) . ") OR $userID = 1");
$available_users = array('-1');
while ($result && ($row = $result->fetch_assoc())) {
    $available_users[] = $row['userID'];
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
    $dynrow['projectowner'] = $userID;
    $dynrow['projectleader'] = ($isDynamicProjectsAdmin == 'TRUE') ? 0 : $userID;
    $dynrow_teams = array('teamid' => '');
    $dynrow_emps = array('userid' => '', 'position' => '');
    $dynrow['companyid'] = $_SESSION['filterings']['company'] ?? 0; //isset, or 0
    $dynrow['clientid'] = $_SESSION['filterings']['client'] ?? 0;
    $dynrow['clientprojectid'] = $_SESSION['filterings']['project'] ?? 0;
    $dynrow['level'] = 0;
}
?>

<div class="modal fade" id="<?php if($dynrow['isTemplate']=='TRUE') echo "temp"; ?>editingModal-<?php echo $x; ?>">
    <div class="modal-dialog modal-lg" role="form">
        <div class="modal-content">
            <form method="POST" id="projectForm<?php echo $x; ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Task editieren</h4>
                </div>
                <div class="modal-body">
                    <div class="remember_state pull-right"></div>
                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#projectBasics<?php echo $x; ?>">Basic*</a></li>
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
                                            $result_fc = $conn->query("SELECT id, name FROM clientData WHERE isSupplier = 'FALSE' AND companyID IN (".implode(', ', $available_companies).");");
                                        } elseif($dynrow['companyid']){
                                            $result_fc = $conn->query("SELECT id, name FROM clientData WHERE isSupplier = 'FALSE' AND companyID = '".$dynrow['companyid']."';");
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

                            <div class="col-md-12"><small>*Auswahl ist Optional. Falls leer, entscheidet der Benutzer.</small><br><br></div>
                            <div class="col-md-12"><label>Task Name*</label><input spellchecking="true" class="form-control" type="text" name="name" placeholder="Bezeichnung" maxlength="55" value="<?php echo $dynrow['projectname']; ?>" /><br></div>
                            <?php
                            $modal_options = '';
                            if($isDynamicProjectsAdmin == 'TRUE'){
                                $result = $conn->query("SELECT id, firstname, lastname FROM UserData WHERE id IN (".implode(', ', $available_users).")");
                            }else{
                                $result = $conn->query("SELECT id, firstname, lastname FROM UserData WHERE id = $userID");
                            }
                            while ($row = $result->fetch_assoc()){ $modal_options .= '<option value="'.$row['id'].'" data-icon="user">'.$row['firstname'] .' '. $row['lastname'].'</option>'; }
                            ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <label><?php echo $lang["OWNER"]; ?>*</label>
                                    <select class="select2-team-icons" name="owner">
                                        <?php echo str_replace('<option value="'.$dynrow['projectowner'].'" ', '<option selected value="'.$dynrow['projectowner'].'" ', $modal_options); ?>
                                    </select><br>
                                </div>
                                <div class="col-md-6">
                                    <label><?php echo $lang["EMPLOYEE"]; ?>/ Team*</label>
                                    <select class="select2-team-icons" name="employees[]" multiple="multiple">
                                        <?php
                                        if($isDynamicProjectsAdmin != 'TRUE'){
                                            $result = str_replace('<option value="', '<option selected value="user;', $modal_options);
                                        } else {
                                            $result = str_replace('<option value="', '<option value="user;', $modal_options);
                                        }
                                        for($i = 0; $i < count($dynrow_emps); $i++){
                                            if($dynrow_emps[$i]['position'] == 'normal'){
                                                $result = str_replace('<option value="user;'.$dynrow_emps[$i]['userid'].'" ', '<option selected value="user;'.$dynrow_emps[$i]['userid'].'" ', $result);
                                            }
                                        }
                                        echo $result;
                                        if($isDynamicProjectsAdmin == 'TRUE'){
                                            $result = $conn->query("SELECT id, name FROM $teamTable");
                                            while ($row = $result->fetch_assoc()) {
                                                $selected = '';
                                                if(in_array($row['id'], $dynrow_teams)){
                                                    $selected = 'selected';
                                                }
                                            echo '<option value="team;'.$row['id'].'" data-icon="group" '.$selected.' >'.$row['name'].'</option>';
                                            }
                                        }
                                        ?>
                                    </select><br>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?>*</label>
                                <select class="form-control js-example-basic-single" name="priority">
                                    <?php
                                    for($i = 1; $i < 6; $i++){
                                        $selected = $dynrow['projectpriority'] == $i ? 'selected' : '';
                                        echo '<option value="'.$i.'" '.$selected.'>'.$lang['PRIORITY_TOSTRING'][$i].'</option>';
                                    }
                                    ?>
                                </select><br>
                            </div>
                                <div class="col-md-4">
                                    <label>Geschätzte Zeit <a data-toggle="collapse" href="#estimateCollapse-<?php echo $x; ?>"><i class="fa fa-question-circle-o"></i></a></label>
                                    <input type="text" class="form-control" value="<?php echo $dynrow['estimatedHours']; ?>" name="estimatedHours" /><br>
                                </div>
                                <div class="col-md-4">
                                    <label><?php echo $lang["BEGIN"]; ?>*</label>
                                    <input type='text' class="form-control datepicker" name='start' placeholder='Anfangsdatum' value="<?php echo $dynrow['projectstart']; ?>" /><br>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="collapse" id="estimateCollapse-<?php echo $x; ?>">
                                        <div class="well">
                                            Die <strong>Geschätzte Zeit</strong> wird per default in Stunden angegeben. D.h. 120 = 120 Stunden. <br>
                                            Mit "m", "t", "w" oder "M" können genauere Angaben gemacht werden: z.B. 2M für 2 Monate, 7m = 7 Minuten, 4t = 4 Tage und 6w = 6 Wochen.<br>
                                            Konkret: "2M 3w 50" würde also für 2 Monate, 3 Wochen und 50 Stunden stehen. (Alle anderen Angaben werden gespeichert, aber vom Programm ignoriert)
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label>Tags</label>
                                <select class="form-control js-example-tokenizer" name="projecttags[]" multiple="multiple">
                                    <option value="Bug">Bug</option>
                                    <option value="Erweiterung">Erweiterung</option>
                                    <option value="GUI">GUI</option>
                                    <option value="Verbesserung">Verbesserung</option>
                                    <?php
                                    foreach(explode(',', $dynrow['projecttags']) as $tag){
                                        if($tag) echo '<option value="'.$tag.'" selected>'.$tag.'</option>';
                                    }
                                    ?>
                                </select><small>Tags werden durch ',' oder ' ' automatisch getrennt.</small><br><br>
                            </div>
                            <div class="col-md-12">
                                <label><?php echo $lang["DESCRIPTION"]; ?>* <small>(Max. 15MB)</small></label>
                                <textarea class="form-control projectDescriptionEditor tinymce-remember" name="description"  ><?php echo $dynrow['projectdescription']; ?></textarea>
                                <br>
                            </div>
                        </div>
                        <div id="projectAdvanced<?php echo $x; ?>" class="tab-pane fade"><br>
                            <div class="row">
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
                                    <div class="col-md-6">
                                        <?php if($isDynamicProjectsAdmin == 'TRUE'): ?>
                                            <label>Skill Minimum</label>
                                            <input type="range" step="10" value="<?php echo $dynrow['level']; ?>" oninput="document.getElementById('projectskill-<?php echo $x; ?>').value = this.value;"><br>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php  if($isDynamicProjectsAdmin == 'TRUE'): ?>
                                            <label>Level</label>
                                            <input id="projectskill-<?php echo $x; ?>" type="number" class="form-control" name="projectskill" value="<?php echo $dynrow['level']; ?>"><br>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_COLOR"]; ?></label>
                                    <input type="color" class="form-control" value="<?php echo $dynrow['projectcolor']; ?>" name="color"><br>
                                </div>
                                <div class="col-md-4">
                                <?php  if($isDynamicProjectsAdmin == 'TRUE'): ?>
                                    <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PARENT"]; ?>:</label>
                                    <select class="form-control js-example-basic-single" name="parent">
                                        <option value=''>Keines</option>
                                        <?php
                                        $result = $conn->query("SELECT projectid, projectname FROM dynamicprojects");
                                        while ($row = $result->fetch_assoc()) {
                                            $selected = ($row['projectid'] == $dynrow['projectparent']) ? 'selected' : '';
                                            echo '<option '.$selected.' value="'.$row["projectid"].'" >'.$row["projectname"].'</option>';
                                        }
                                        ?>
                                    </select><br>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label><?php echo $lang["LEADER"]; ?></label>
                                    <select class="select2-team-icons" name="leader">
                                        <?php if($isDynamicProjectsAdmin == 'TRUE') echo '<option value="">...</option>'; ?>
                                        <?php echo str_replace('<option value="'.$dynrow['projectleader'].'" ', '<option selected value="'.$dynrow['projectleader'].'" ', $modal_options); ?>
                                    </select><br>
                                </div>
                                <div class="col-md-6">
                                <?php  if($isDynamicProjectsAdmin == 'TRUE'): ?>
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
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div id="projectSeries<?php echo $x; ?>" class="tab-pane fade"><br>
                            <div class="well">
                                <div class="row">
                                    <div class="col-sm-8">
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
                    <div class="pull-left"><?php echo $x; ?></div>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning" name="editDynamicProject" value="<?php if($dynrow['isTemplate'] == 'FALSE')echo $x; ?>" ><?php echo $lang['SAVE']; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
