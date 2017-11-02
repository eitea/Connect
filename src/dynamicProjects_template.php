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

?>

    <button class="btn btn-default" data-toggle="modal" data-target="#dynamicProject<?php echo stripSymbols($modal_id) ?>" type="button">
        <i class="fa fa-cog"></i>
    </button>


    <!-- new dynamic project modal -->
    <form method="post" autocomplete="off" id="projectForm<?php echo stripSymbols($modal_id) ?>">
        <input type="hidden" name="id" value="<?php echo $modal_id ?>">
        <div class="modal fade" id="dynamicProject<?php echo stripSymbols($modal_id) ?>" tabindex="-1" role="dialog" aria-labelledby="dynamicProjectLabel<?php echo stripSymbols($modal_id) ?>">
            <div class="modal-dialog" role="form">
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
                            <a data-toggle="tab" href="#projectBasics<?php echo stripSymbols($modal_id) ?>">Grundlagen*</a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#projectDescription<?php echo stripSymbols($modal_id) ?>">Projektbeschreibung*</a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#projectAdvanced<?php echo stripSymbols($modal_id) ?>">Erweitert</a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#projectSeries<?php echo stripSymbols($modal_id) ?>">Serie</a>
                        </li>
                    </ul>
                    <!-- /tab buttons -->
                    <div class="tab-content">
                        <div id="projectBasics<?php echo stripSymbols($modal_id) ?>" class="tab-pane fade in active">
                            <div class="modal-body">
                                <!-- basics -->
                                <div class="well">
                                    <label>Projektname*:</label>
                                    <input class="form-control" type="text" name="name" required value="<?php echo $modal_name; ?>">
                                    <label>Mandant*:</label>
                                    <select class="form-control js-example-basic-single" name="company" required onchange="showClients(this.value, 0,'#dynamicProjectClients<?php echo stripSymbols($modal_id) ?>')">
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
                                    <label>Kunde*:</label>
                                    <select id="dynamicProjectClients<?php echo stripSymbols($modal_id) ?>" class="form-control js-example-basic-single" name="clients[]"
                                        multiple="multiple" required>
                                        <?php 
                                        if (!empty($modal_clients) && !empty($modal_company)) {
                                            $modal_clientsResult = $conn->query("SELECT * FROM $clientTable WHERE companyID = $modal_company");
                                            while ($modal_clientRow = $modal_clientsResult->fetch_assoc()) {
                                                $modal_client_id = $modal_clientRow["id"];
                                                $modal_client = $modal_clientRow["name"];
                                                $selected = in_array($modal_client_id, $modal_clients) ? "selected" : "";
                                                echo "<option $selected value='$modal_client_id'>$modal_client</option>";
                                            }
                                        }
                                        else {
                                            echo "<option>Zuerst Mandant auswählen</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="well">
                                    <label>Besitzer*:</label>
                                    <select class="form-control js-example-basic-single" name="owner" required>
                                        <?php
                                        $modal_result = $conn->query("SELECT * FROM UserData");
                                        while ($modal_row = $modal_result->fetch_assoc()) {
                                            $x = $modal_row['id'];
                                            $modal_user_name = "${modal_row['firstname']} ${modal_row['lastname']}";
                                            if (!empty($modal_owner)) {
                                                $selected = $modal_owner == $x ? "selected" : "";
                                            }
                                            else {
                                                $selected = $x == $userID ? "selected" : "";
                                            }
                                            echo "<option value='$x' $selected >$modal_user_name</option>";
                                        }
                                        ?>
                                    </select>
                                    <label>Mitarbeiter*:</label>
                                    <select class="form-control js-example-basic-single" name="employees[]" multiple="multiple" required>
                                        <?php
                                        $modal_result = $conn->query("SELECT * FROM UserData");
                                        while ($modal_row = $modal_result->fetch_assoc()) {
                                            $x = $modal_row['id'];
                                            $modal_user_name = "${modal_row['firstname']} ${modal_row['lastname']}";
                                            if (!empty($modal_employees)) {
                                                $selected = in_array($x, $modal_employees) ? "selected" : "";
                                            }
                                            else {
                                                $selected = $x == $userID ? "selected" : "";
                                            }
                                            echo "<option value='$x' $selected >$modal_user_name</option>";
                                        }
                                        ?>
                                    </select>
                                    <label>Optionale Mitarbeiter:</label>
                                    <select class="form-control js-example-basic-single" name="optionalemployees[]" multiple="multiple">
                                        <?php
                                        $modal_result = $conn->query("SELECT * FROM UserData");
                                        while ($modal_row = $modal_result->fetch_assoc()) {
                                            $x = $modal_row['id'];
                                            $modal_user_name = "${modal_row['firstname']} ${modal_row['lastname']}";
                                            if (!empty($modal_optional_employees)) {
                                                $selected = in_array($x, $modal_optional_employees) ? "selected" : "";
                                            }
                                            else {
                                                $selected = $x == $userID ? "selected" : "";
                                            }
                                            echo "<option value='$x' $selected >$modal_user_name</option>";
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
                                    <label>Projektbeschreibung*:</label>
                                    <textarea class="form-control" style="max-width: 100%" rows="10" name="description" required><?php echo $modal_description; ?></textarea>
                                    <label>Bilder auswählen:</label>
                                    <br>
                                    <label class="btn btn-default" role="button">Durchsuchen...
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
                                    <label>Priorität*:</label>
                                    <select class="form-control js-example-basic-single" name="priority" required>
                                        <?php
                                        $modal_priorities = array(1 => "Sehr niedrig", 2 => "Niedrig", 3 => "Normal", 4 => "Hoch", 5 => "Sehr hoch");
                                        for ($i = 1; $i <= 5; $i++) {
                                            $selected = $modal_priority == $i ? "selected" : "";
                                            echo "<option $selected value='$i'>${modal_priorities[$i]}</option>";
                                        }
                                        ?>
                                    </select>
                                    <label>Status*:</label>
                                    <div class="input-group">
                                        <select class="form-control" name="status" required>
                                            <option value="DEACTIVATED" <?php echo $modal_status=='DEACTIVATED' ? "selected" : "" ?> >Deaktiviert</option>
                                            <option value="DRAFT" <?php echo $modal_status=='DRAFT' ? "selected" : "" ?> >Entwurf</option>
                                            <option value="ACTIVE" <?php echo $modal_status=='ACTIVE' ? "selected" : "" ?> >Aktiv</option>
                                            <option value="COMPLETED" <?php echo $modal_status=='COMPLETED' ? "selected" : "" ?> >Abgeschlossen</option>
                                        </select>
                                        <span class="input-group-addon text-warning"> % abgeschlossen </span>
                                        <input type='number' class="form-control" name='completed' value="<?php echo $modal_completed; ?>" min="0" max="100" step="10"
                                            required/>
                                    </div>
                                    <label>Farbe:</label>
                                    <input type="color" class="form-control" value="<?php echo $modal_color; ?>" name="color">
                                    <label>Überprojekt:</label>
                                    <select class="form-control js-example-basic-single" name="parent" required>
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
                                    <label>Start:</label>
                                    <input type='text' class="form-control datepicker" name='localPart' placeholder='Startdatum' value="<?php echo $modal_start; ?>"
                                    />
                                    <label>Ende:</label>
                                    <br>
                                    <?php if ($modal_end == "") : ?>
                                    <label>
                                        <input type="radio" name="endradio" value="no" checked> Ohne</label>
                                    <br>
                                    <?php  else : ?>
                                    <label>
                                        <input type="radio" name="endradio" value="no"> Ohne</label>
                                    <br>
                                    <?php endif; ?>
                                    <?php if (preg_match("/\d{4}-\d{2}-\d{2}/", $modal_end)) : ?>
                                    <input type="radio" name="endradio" value="date" checked>
                                    <label>
                                        <input type='text' class="form-control datepicker" name='enddate' placeholder="Enddatum" value="<?php echo $modal_end; ?>"
                                        />
                                    </label>
                                    <br>
                                    <?php  else : ?>
                                    <input type="radio" name="endradio" value="date">
                                    <label>
                                        <input type='text' class="form-control datepicker" name='enddate' placeholder="Enddatum" value="<?php echo date('Y-m-d'); ?>"
                                        />
                                    </label>
                                    <br>
                                    <?php endif; ?>
                                    <?php if (preg_match("/^\d+$/", $modal_end)) : ?>
                                    <input type="radio" name="endradio" value="number" checked>
                                    <label>
                                        <input type='number' class="form-control" name='endnumber' placeholder="Wiederholungen" value="<?php echo $modal_end ?>">
                                    </label>
                                    <?php  else : ?>
                                    <input type="radio" name="endradio" value="number">
                                    <label>
                                        <input type='number' class="form-control" name='endnumber' placeholder="Wiederholungen">
                                    </label>
                                    <?php endif; ?>
                                </div>

                                <div class="well">
                                    <?php
                                    $modal_series = empty($modal_series) ? new ProjectSeries("", "", "") : $modal_series;
                                    ?>
                                        <label>Einmalig</label>
                                        <br>
                                        <input type="radio" <?php echo $modal_series->once ? "checked" : ""; ?> name="series" value="once">Keine Wiederholungen
                                        <br>

                                        <label>Täglich</label>
                                        <br>
                                        <input type="radio" <?php echo $modal_series->daily_every_nth ? "checked" : ""; ?> name="series" value="daily_every_nth">Alle
                                        <label>
                                            <input class="form-control" type="number" min="1" max="365" value="<?php echo $modal_series->daily_days; ?>" name="daily_days">
                                        </label> Tage
                                        <br>
                                        <input type="radio" <?php echo $modal_series->daily_every_weekday ? "checked" : ""; ?> name="series" value="daily_every_weekday">Montag
                                        bis Freitag
                                        <br>


                                        <label>Wöchentlich</label>
                                        <br>
                                        <input type="radio" <?php echo $modal_series->weekly ? "checked" : ""; ?> name="series" value="weekly">Alle
                                        <label>
                                            <input class="form-control" type="number" max="52" value="<?php echo $modal_series->weekly_weeks; ?>" name="weekly_weeks">
                                        </label> Wochen am
                                        <label>
                                            <select class="form-control" name="weekly_day" required>
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


                                        <label>Monatlich</label>
                                        <br>
                                        <input type="radio" <?php echo $modal_series->monthly_day_of_month ? "checked" : ""; ?> name="series" value="monthly_day_of_month">am
                                        <label>
                                            <input name="monthly_day_of_month_day" class="form-control" value="<?php echo $modal_series->monthly_day_of_month_day; ?>"
                                                type="number" min="1" max="31">
                                        </label> Tag jedes
                                        <label>
                                            <input name="monthly_day_of_month_month" value="<?php echo $modal_series->monthly_day_of_month_month; ?>" class="form-control"
                                                type="number" min="1" max="12">
                                        </label>. Monats
                                        <br>
                                        <input type="radio" <?php echo $modal_series->monthly_nth_day_of_week ? "checked" : ""; ?> name="series" value="monthly_nth_day_of_week">am
                                        <label>
                                            <input name="monthly_nth_day_of_week_nth" value="<?php echo $modal_series->monthly_nth_day_of_week_nth; ?>" class="form-control"
                                                type="number" min="1" max="5">
                                        </label>
                                        <label>
                                            <select class="form-control" name="monthly_nth_day_of_week_day" required>
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
                                            <input name="monthly_nth_day_of_week_month" value="<?php echo $modal_series->monthly_nth_day_of_week_month; ?>" class="form-control"
                                                type="number" min="1" max="12">
                                        </label> monats
                                        <br>


                                        <label>Jährlich</label>
                                        <br>
                                        <input type="radio" <?php echo $modal_series->yearly_nth_day_of_month ? "checked" : ""; ?> name="series" value="yearly_nth_day_of_month">jeden
                                        <label>
                                            <input name="yearly_nth_day_of_month_nth" value="<?php echo $modal_series->yearly_nth_day_of_month_nth; ?>" class="form-control"
                                                min="1" max="31" type="number">
                                        </label>.
                                        <label>
                                            <select class="form-control" name="yearly_nth_day_of_month_month" required>
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
                                        <input type="radio" <?php echo $modal_series->yearly_nth_day_of_week ? "checked" : ""; ?> name="series" value="yearly_nth_day_of_week">am
                                        <label>
                                            <input name="yearly_nth_day_of_week_nth" value="<?php echo $modal_series->yearly_nth_day_of_week_nth; ?>" class="form-control"
                                                min="1" max="5" type="number">
                                        </label>.
                                        <label>
                                            <select class="form-control" name="yearly_nth_day_of_week_day" required>
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
                                            <select name="yearly_nth_day_of_week_month" class="form-control" name="month" required>
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
                        <?php if ($modal_id):?>
                        <button type="submit" class="btn btn-danger ask-before-submit<?php echo stripSymbols($modal_id) ?>" name="deleteDynamicProject">
                            <?php echo $lang["DELETE"]; ?>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo $lang['CANCEL']; ?>
                        </button>
                        <button type="submit" class="btn btn-warning" name="dynamicProject<?php echo stripSymbols($modal_id) ?>">
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
            })
        })


    </script>