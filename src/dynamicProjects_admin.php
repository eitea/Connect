<?php include 'header.php';
isDynamicProjectAdmin($userID); ?>
<!-- BODY -->
<?php
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["newDynamicProject"])){
    var_dump($_FILES);
    

    
}
?>
<br>
<button class="btn btn-default" data-toggle="modal" data-target="#newDynamicProject" type="button"><i class="fa fa-plus"></i></button>
    

<?php
// variables for easy reuse for editing existing dynamic projects
$modal_title = $lang['DYNAMIC_PROJECTS_NEW'];
$modal_name = "";
$modal_company = "";
//not jet implemented:
$modal_clients = array();
$modal_owner = "";
$modal_employees = array();
$modal_optional_employees = array();
$modal_description = "";
$modal_pictures = array();
$modal_priority = 3;
$modal_status = "";
$modal_color = "#ffffff";
$modal_parent = "";
$modal_start = "";
$modal_end = "";
$modal_series = "";
?>
<!-- new dynamic project modal -->
<form method="post" autocomplete="off">
    <div class="modal fade" id="newDynamicProject" tabindex="-1" role="dialog" aria-labelledby="newDynamicProjectLabel">
        <div class="modal-dialog" role="form">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="newDynamicProjectLabel">
                        <?php echo $modal_title; ?>
                    </h4>
                </div>
                    <!-- modal body -->
                    <!-- tab buttons -->
                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#newProjectBasics">Grundlagen*</a></li>
                        <li><a data-toggle="tab" href="#newProjectDescription">Projektbeschreibung*</a></li>
                        <li><a data-toggle="tab" href="#newProjectAdvanced">Erweitert</a></li>
                        <li><a data-toggle="tab" href="#newProjectSeries">Serie</a></li>
                    </ul>
                    <!-- /tab buttons -->
                    <div class="tab-content">
                        <div id="newProjectBasics" class="tab-pane fade in active">
                            <div class="modal-body">
                            <!-- basics -->
                            <div class="well">
                                <label>Projektname*:</label>
                                <input class="form-control" type="text" name="name" required value="<?php echo $modal_name; ?>">
                                <label>Mandant*:</label>
                                <select class="form-control js-example-basic-single" name="company" required onchange="showClients(this.value, 0,'#newDynamicProjectClients')">
                                    <option value="">...</option>
                                    <?php 
                                    $result = $conn->query("SELECT * FROM $companyTable WHERE id IN (".implode(', ', $available_companies).") ");
                                    while ($row = $result->fetch_assoc()) {
                                        $companyID = $row["id"];
                                        $companyName = $row["name"];
                                        $selected = $companyID == $modal_company && $modal_company != "" ? "selected":"";
                                        echo "<option $selected value='$companyID'>$companyName</option>";
                                    }
                                    ?>
                                </select>
                                <label>Kunde*:</label>
                                <select id="newDynamicProjectClients" class="form-control js-example-basic-single" name="client" multiple="multiple" required>
                                    <option>Zuerst Mandant auswählen</option>
                                    <?php 
                                        foreach ($modal_clients as $client) {
                                            echo "<option>$client</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="well">
                                <label>Besitzer*:</label>
                                <select class="form-control js-example-basic-single" name="owner" required>
                                    <?php
                                    $result = $conn->query("SELECT * FROM UserData");
                                    while ($row = $result->fetch_assoc()) {
                                        $x = $row['id'];
                                        $selected = $x == $userID ? "selected":"";
                                        $name = "${row['firstname']} ${row['lastname']}";
                                        echo "<option value='$x' $selected >$name</option>";
                                    }
                                    ?>
                                </select>
                                <label>Mitarbeiter*:</label>
                                <select class="form-control js-example-basic-single" name="employees" multiple="multiple" required>
                                    <?php
                                    $result = $conn->query("SELECT * FROM UserData");
                                    while ($row = $result->fetch_assoc()) {
                                        $x = $row['id'];
                                        $selected = $x == $userID ? "selected":"";
                                        $name = "${row['firstname']} ${row['lastname']}";
                                        echo "<option value='$x' $selected >$name</option>";
                                    }
                                    ?>
                                </select>
                                <label>Optionale Mitarbeiter:</label>
                                <select class="form-control js-example-basic-single" name="optionalemployees" multiple="multiple">
                                    <?php
                                    $result = $conn->query("SELECT * FROM UserData");
                                    while ($row = $result->fetch_assoc()) {
                                        $x = $row['id'];
                                        $name = "${row['firstname']} ${row['lastname']}";
                                        echo "<option value='$x' $selected >$name</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- /basics -->
                            </div>
                        </div>
                        <div id="newProjectDescription" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- description -->
                            <div class="well">
                                <label>Projektbeschreibung*:</label>
                                <textarea class="form-control" style="max-width: 100%" rows="10" name="description" required></textarea>
                                <label>Bilder auswählen:</label><br>
                                <label class="btn btn-default" role="button">Durchsuchen...
                                <input type="file" name="images" multiple class="form-control" style="display:none;" id="newProjectImageUpload" accept=".jpg,.jpeg,.png"></label>
                                <output id="newProjectPreview"></output>
                            </div>
                            <!-- /description -->
                            </div>
                        </div>
                        <div id="newProjectAdvanced" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- advanced -->
                            <div class="well">
                                <label>Priorität*:</label>
                                <select class="form-control js-example-basic-single" name="priority" required>
                                    <option value="example">Sehr niedrig</option>
                                    <option value="example">Niedrig</option>
                                    <option value="example" selected>Normal</option>
                                    <option value="example">Hoch</option>
                                    <option value="example">Sehr hoch</option>
                                </select>
                                <label>Status*:</label>
                                <div class="input-group">
                                <select class="form-control" name="status" required>
                                        <option value="example">Deaktiviert</option>
                                        <option value="example">Entwurf</option>
                                        <option value="example" selected>Aktiv</option>
                                        <option value="example">Abgeschlossen</option>
                                    </select>
                                    <span class="input-group-addon text-warning"> % abgeschlossen </span>
                                    <input type='number' class="form-control" name='completed' value="0" min="0" max="100" step="10" required/>
                                </div>
                                <label>Farbe:</label>
                                <input type="color" class="form-control" value="#f44242" name="color">
                                <label>Überprojekt:</label>
                                <select class="form-control js-example-basic-single" name="parent" required>
                                    <option value="">Keines</option>
                                </select>
                            </div>
                            <!-- /advanced -->
                            </div>
                        </div>
                        <div id="newProjectSeries" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- series -->

                                <div class="well">
                                    <label>Start:</label>
                                    <input type='text' class="form-control datepicker" name='localPart' placeholder='Startdatum' value="0000-00-00" />
                                    <label>Ende:</label><br>
                                    <label><input type="radio" name="asdfasdf" value="no" checked> Ohne</label><br>
                                    <input type="radio" name="asdfasdf" value="no"><label><input type='text' class="form-control datepicker" name='domainPart' placeholder="Enddatum" value="0000-00-00" /></label><br>
                                    <input type="radio" name="asdfasdf" value="no"><label><input type='number' class="form-control" name='localPart' placeholder="Nach _ wiederholungen"></label>
                                </div>

                                <div class="well">
                                    
                                        <label>Einmalig</label><br>
                                        <input type="radio" checked>Keine Wiederholungen<br>

                                        <label>Täglich</label><br>
                                        <input type="radio">Alle <label><input class="form-control" type="number"></label> Tage<br>
                                        <input type="radio">Montag bis Freitag<br>


                                        <label>Wöchentlich</label><br>
                                        <input type="radio">Alle <label><input class="form-control" type="number"></label> Wochen am <label><select class="form-control" name="day" required>
                                        <option value="monday">Montag</option>
                                        <option value="tuesday">Dienstag</option>
                                        <option value="wednesday">Mittwoch</option>
                                        <option value="thursday">Donnerstag</option>
                                        <option value="Friday">Freitag</option>
                                        <option value="Saturday">Samstag</option>
                                        <option value="Sunday">Sonntag</option>
                                </select></label> <br>


                                        <label>Monatlich</label><br>
                                        <input type="radio">am <label><input class="form-control" type="number"></label> Tag jedes <label><input class="form-control" type="number"></label>. Monats<br>
                                        <input type="radio">am <label><input class="form-control" type="number"></label> <label><select class="form-control" name="day" required>
                                        <option value="monday">Montag</option>
                                        <option value="tuesday">Dienstag</option>
                                        <option value="wednesday">Mittwoch</option>
                                        <option value="thursday">Donnerstag</option>
                                        <option value="Friday">Freitag</option>
                                        <option value="Saturday">Samstag</option>
                                        <option value="Sunday">Sonntag</option>
                                </select></label> jedes <label><input class="form-control" type="number"></label> monats<br>

                                        
                                        <label>Jährlich</label><br>
                                        <input type="radio">jeden <input value="1">. <input value="Jänner"><br>
                                        <input type="radio">am <input value="1">. <input value="Montag"> im september<br>

                                    
                                </div>


                            <!-- /series -->
                            </div>
                        </div>
                    </div>
                    <!-- /modal body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning" name="newDynamicProject"><?php echo $lang['SAVE']; ?></button>
                </div>
            </div>
        </div>
    </div>
</form>
<!-- /new dynamic project modal -->
<script>
function showClients(company, client, targetSelector){
    $.ajax({
        url:'ajaxQuery/AJAX_getClient.php',
        data:{companyID:company, clientID:client},
        type: 'get',
        success : function(resp){
        $(targetSelector).html(resp);
        },
        error : function(resp){},
    });
}

$("#newProjectImageUpload").change(function(event){
    var files = event.target.files;
    $("#newProjectPreview").html("");
    // Loop through the FileList and render image files as thumbnails.
    for (var i = 0, f; f = files[i]; i++) {
      // Only process image files.
      if (!f.type.match('image.*')) {
        continue;
      }
      var reader = new FileReader();
      // Closure to capture the file information.
      reader.onload = (function(theFile) {
        return function(e) {
          // Render thumbnail.
          var span = document.createElement('span');
          span.innerHTML = 
          [
            '<img class="img-thumbnail" style="width:49%;margin:0.5%" src="', 
            e.target.result,
            '" title="', escape(theFile.name), 
            '"/>'
          ].join('');
          $("#newProjectPreview").append(span);
        };
      })(f);
      // Read in the image file as a data URL.
      reader.readAsDataURL(f);
    }
  });


</script>






<!-- /BODY -->
<?php include 'footer.php'; ?>