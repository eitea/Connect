<?php include 'header.php';
isDynamicProjectAdmin($userID); ?>
<!-- BODY -->
<?php
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["newDynamicProject"])){
    //Required
    $project_name = $_POST["name"];
    $project_description = $_POST["description"];
    

    
}
?>
<br>
<button class="btn btn-default" data-toggle="modal" data-target="#newDynamicProject" type="button"><i class="fa fa-plus"></i></button>
    


<!-- new dynamic project modal -->
<form method="post" autocomplete="off">
    <div class="modal fade" id="newDynamicProject" tabindex="-1" role="dialog" aria-labelledby="newDynamicProjectLabel">
        <div class="modal-dialog" role="form">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="newDynamicProjectLabel">
                        <?php echo $lang['DYNAMIC_PROJECTS_NEW']; ?>
                    </h4>
                </div>
                    <!-- modal body -->
                    <!-- tab buttons -->
                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#newProjectBasics">Grundlagen</a></li>
                        <li><a data-toggle="tab" href="#newProjectDescription">Projektbeschreibung</a></li>
                        <li><a data-toggle="tab" href="#newProjectAdvanced">Erweitert</a></li>
                        <li><a data-toggle="tab" href="#newProjectSeries">Serie</a></li>
                    </ul>
                    <!-- /tab buttons -->
                    <div class="tab-content">
                        <div id="newProjectBasics" class="tab-pane fade in active">
                            <div class="modal-body">
                            <!-- basics -->

                            <label>Projektname*:</label>
                            <input class="form-control" type="text" name="name" required>
                            <label>Mandant*:</label>
                            <select class="form-control js-example-basic-single" name="company" required>
                                <option value="" selected>...</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                            </select>
                            <label>Kunde*:</label>
                            <select class="form-control js-example-basic-single" name="client" multiple="multiple" required>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                            </select>
                            <hr>
                            <label>Besitzer*:</label>
                            <select class="form-control js-example-basic-single" name="owner" required>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                            </select>
                            <label>Mitarbeiter*:</label>
                            <select class="form-control js-example-basic-single" name="employees" multiple="multiple" required>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                            </select>
                            <label>Optionale Mitarbeiter:</label>
                            <select class="form-control js-example-basic-single" name="optionalemployees" multiple="multiple">
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                                <option value="example">Example</option>
                            </select>

                            <!-- /basics -->
                            </div>
                        </div>
                        <div id="newProjectDescription" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- description -->

                                <label>Projektbeschreibung*:</label>
                                <textarea class="form-control" style="max-width: 100%" rows="10" name="description" required></textarea>
                                <label>Bilder auswählen:</label>
                                <input type="file" name="images" multiple class="form-control">

                            <!-- /description -->
                            </div>
                        </div>
                        <div id="newProjectAdvanced" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- advanced -->
                                <label>Priorität*:</label>
                                <select class="form-control js-example-basic-single" name="location" required>
                                    <option value="example">Sehr niedrig</option>
                                    <option value="example">Niedrig</option>
                                    <option value="example" selected>Normal</option>
                                    <option value="example">Hoch</option>
                                    <option value="example">Sehr hoch</option>
                                </select>
                                <label>Status*:</label>
                                <div class="input-group">
                                <select class="form-control" name="location" required>
                                        <option value="example">Deaktiviert</option>
                                        <option value="example">Entwurf</option>
                                        <option value="example" selected>Aktiv</option>
                                        <option value="example">Abgeschlossen</option>
                                    </select>
                                    <span class="input-group-addon text-warning"> % abgeschlossen </span>
                                    <input type='number' class="form-control" name='domainPart' value="0" min="0" max="100" step="10" required/>
                                </div>
                                <label>Farbe:</label>
                                <input type="color" class="form-control" value="#f44242">

                            <!-- /advanced -->
                            </div>
                        </div>
                        <div id="newProjectSeries" class="tab-pane fade">
                            <div class="modal-body">
                            <!-- series -->
                                <div class="checkbox">
                                    <label><input type="radio" name="asdf" value="once" checked> Einmalig</label>
                                    <label><input type="radio" name="asdf" value="daily" > Täglich</label>
                                    <label><input type="radio" name="asdf" value="monthly" > Monatlich</label>
                                    <label><input type="radio" name="asdf" value="yearly" > Jährlich</label>
                                </div>
                                <hr>
                                <label>Start:</label>
                                <input type='text' class="form-control datepicker" name='localPart' placeholder='Startdatum' value="0000-00-00" />
                                <label>Ende:</label><br>
                                <label><input type="radio" name="asdfasdf" value="no" checked> Ohne</label><br>
                                <input type="radio" name="asdfasdf" value="no"><label><input type='text' class="form-control datepicker" name='domainPart' placeholder="Enddatum" value="0000-00-00" /></label><br>
                                <input type="radio" name="asdfasdf" value="no"><label><input type='number' class="form-control" name='localPart' placeholder="Nach _ wiederholungen"></label>
                                <hr>
                                Täglich<br>
                                <input type="radio">Alle <input> tage<br>
                                <input type="radio">jeden Arbeitstag<br>
                                Wöchentlich<br>
                                <input type="radio">alle <input> wochen am <input value="Montag"> <br>
                                Monatlich<br>
                                <input type="radio">am <input> tag jedes <input> monats<br>
                                <input type="radio">am <input> <input value="Montag"> jedes <input> monats<br>
                                Jährlich<br>
                                <input type="radio">jeden <input value="1">. <input value="Jänner"><br>
                                <input type="radio">am <input value="1">. <input value="Montag"> im september<br>

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







<!-- /BODY -->
<?php include 'footer.php'; ?>