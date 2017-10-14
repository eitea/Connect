<?php
/*
This template is a modal for editing new and existing dynamic projects
*/
?>

<button class="btn btn-default" data-toggle="modal" data-target="#dynamicProject<?php echo $modal_id ?>" type="button"><i class="fa fa-plus"></i></button>


<!-- new dynamic project modal -->
<form method="post" autocomplete="off" id="projectForm<?php echo $modal_id ?>">
<input type="hidden" name="id" value="<?php echo $modal_id ?>">
    <div class="modal fade" id="dynamicProject<?php echo $modal_id ?>" tabindex="-1" role="dialog" aria-labelledby="dynamicProjectLabel<?php echo $modal_id ?>">
        <div class="modal-dialog" role="form">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="dynamicProjectLabel<?php echo $modal_id ?>">
                        <?php echo $modal_title; ?>
                    </h4>
                </div>
                    <!-- modal body -->
                    <!-- tab buttons -->
                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#projectBasics<?php echo $modal_id ?>">Grundlagen*</a></li>
                        <li><a data-toggle="tab" href="#projectDescription<?php echo $modal_id ?>">Projektbeschreibung*</a></li>
                        <li><a data-toggle="tab" href="#projectAdvanced<?php echo $modal_id ?>">Erweitert</a></li>
                        <li><a data-toggle="tab" href="#projectSeries<?php echo $modal_id ?>">Serie</a></li>
                    </ul>
                    <!-- /tab buttons -->
                    <div class="tab-content">
                        <div id="projectBasics<?php echo $modal_id ?>" class="tab-pane fade in active">
                            <div class="modal-body">
                            <!-- basics -->
                            <div class="well">
                                <label>Projektname*:</label>
                                <input class="form-control" type="text" name="name" required value="<?php echo $modal_name; ?>">
                                <label>Mandant*:</label>
                                <select class="form-control js-example-basic-single" name="company" required onchange="showClients(this.value, 0,'#dynamicProjectClients<?php echo $modal_id ?>')">
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
                                <select id="dynamicProjectClients<?php echo $modal_id ?>" class="form-control js-example-basic-single" name="client" multiple="multiple" required>
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
                        <div id="projectDescription<?php echo $modal_id ?>" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- description -->
                            <div class="well">
                                <label>Projektbeschreibung*:</label>
                                <textarea class="form-control" style="max-width: 100%" rows="10" name="description" required><?php echo $modal_description; ?></textarea>
                                <label>Bilder auswählen:</label><br>
                                <label class="btn btn-default" role="button">Durchsuchen...
                                <input type="file" name="images" multiple class="form-control" style="display:none;" id="projectImageUpload<?php echo $modal_id ?>" accept=".jpg,.jpeg,.png"></label>
                                <div id="projectPreview<?php echo $modal_id ?>">
                                <?php 
                                foreach ($modal_pictures as $picture) {
                                    echo "<span><img src='$picture' alt='Previously uploaded picture' class='img-thumbnail' style='width:49%;margin:0.5%'></span>";
                                }
                                ?>
                                </div>
                            </div>
                            <!-- /description -->
                            </div>
                        </div>
                        <div id="projectAdvanced<?php echo $modal_id ?>" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- advanced -->
                            <div class="well">
                                <label>Priorität*:</label>
                                <select class="form-control js-example-basic-single" name="priority" required>
                                    <option value="1">Sehr niedrig</option>
                                    <option value="2">Niedrig</option>
                                    <option value="3" selected>Normal</option>
                                    <option value="4">Hoch</option>
                                    <option value="5">Sehr hoch</option>
                                </select>
                                <label>Status*:</label>
                                <div class="input-group">
                                <select class="form-control" name="status" required>
                                        <option value="DEACTIVATED" <?php echo $modal_status == 'DEACTIVATED' ? "selected":"" ?> >Deaktiviert</option>
                                        <option value="DRAFT" <?php echo $modal_status == 'DRAFT' ? "selected":"" ?> >Entwurf</option>
                                        <option value="ACTIVE" <?php echo $modal_status == 'ACTIVE' ? "selected":"" ?> >Aktiv</option>
                                        <option value="COMPLETED" <?php echo $modal_status == 'COMPLETED' ? "selected":"" ?> >Abgeschlossen</option>
                                    </select>
                                    <span class="input-group-addon text-warning"> % abgeschlossen </span>
                                    <input type='number' class="form-control" name='completed' value="0" min="0" max="100" step="10" required/>
                                </div>
                                <label>Farbe:</label>
                                <input type="color" class="form-control" value="<?php echo $modal_color; ?>" name="color">
                                <label>Überprojekt:</label>
                                <select class="form-control js-example-basic-single" name="parent" required>
                                    <option value="none" selected>Keines</option>
                                </select>
                            </div>
                            <!-- /advanced -->
                            </div>
                        </div>
                        <div id="projectSeries<?php echo $modal_id ?>" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- series -->

                                <div class="well">
                                    <label>Start:</label>
                                    <input type='text' class="form-control datepicker" name='localPart' placeholder='Startdatum' value="<?php echo $modal_start; ?>" />
                                    <label>Ende:</label><br>
                                    <?php if($modal_end == ""): ?>
                                        <label><input type="radio" name="endradio" value="no" checked> Ohne</label><br>
                                    <?php else: ?>
                                        <label><input type="radio" name="endradio" value="no"> Ohne</label><br>
                                    <?php endif; ?>
                                    <?php if(preg_match("/\d{4}-\d{2}-\d{2}/",$modal_end)): ?>
                                        <input type="radio" name="endradio" value="date" checked><label><input type='text' class="form-control datepicker" name='enddate' placeholder="Enddatum" value="<?php echo $modal_end; ?>" /></label><br>
                                    <?php else: ?>
                                        <input type="radio" name="endradio" value="date"><label><input type='text' class="form-control datepicker" name='enddate' placeholder="Enddatum" value="<?php echo date('Y-m-d');?>" /></label><br>
                                    <?php endif; ?>
                                    <?php if(preg_match("/^\d+$/",$modal_end)): ?>
                                        <input type="radio" name="endradio" value="number" checked><label><input type='number' class="form-control" name='endnumber' placeholder="Wiederholungen" value="<?php echo $modal_end ?>"></label>
                                    <?php else: ?>
                                        <input type="radio" name="endradio" value="number"><label><input type='number' class="form-control" name='endnumber' placeholder="Wiederholungen"></label>
                                    <?php endif; ?>
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
                    <button type="submit" class="btn btn-warning" name="dynamicProject<?php echo $modal_id ?>"><?php echo $lang['SAVE']; ?></button>
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

$("#projectImageUpload<?php echo $modal_id ?>").change(function(event){
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
          $("#projectPreview<?php echo $modal_id ?>").append(span);
          $("#projectPreview<?php echo $modal_id ?> img").unbind("click").click(removeImg)
        };
      })(f);
      // Read in the image file as a data URL.
      reader.readAsDataURL(f);
    }
    
  });

$(function(){
    $("#projectPreview<?php echo $modal_id ?> img").click(removeImg)
})

function removeImg(event){
   $(event.target).remove()
}

$("#projectForm<?php echo $modal_id ?>").submit(function(event){
    $("#projectPreview<?php echo $modal_id ?>").find("input").remove()
    $("#projectPreview<?php echo $modal_id ?>").find("img").each(function(index,elem){
        $("#projectPreview<?php echo $modal_id ?>").append("<input type='hidden' value='" + getImageSrc(elem) + "' name='imagesbase64[]'>")
    })
})

function getImageSrc(img){
    var c = document.createElement("canvas");
    c.width = img.width;
    c.height = img.height;
    var ctx = c.getContext("2d");
    ctx.drawImage(img, 10, 10);
    return c.toDataURL();
}

</script>
