            <!-- booking modal -->
            <div class="modal fade" id="bookDynamicProject<?php echo stripSymbols($modal_id); ?>" tabindex="-1"
                    role="dialog" aria-labelledby="bookDynamicProjectLabel<?php echo stripSymbols($modal_id); ?>">
                    <div class="modal-dialog" role="form">
                        <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="bookDynamicProjectLabel<?php echo stripSymbols($modal_id); ?>">
                                        <?php echo $lang["DYNAMIC_PROJECTS_BOOKING_PROMPT"]; ?>
                                    </h4>
                                </div>
                                <br>
                                <div class="modal-body">
                                    <!-- modal body -->
                                        <textarea name="description" required class="form-control" style="max-width:100%; min-width:100%"></textarea>
                                        <!-- client selection -->
                                        <?php if ($conn->query("SELECT count(*) count FROM dynamicprojectsclients WHERE projectid = '$modal_id'")->fetch_assoc()["count"] > 1): ?>
                                        <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_CLIENT"]; ?>*:</label>
                                            <select class="form-control js-example-basic-single" name="client"
                                                id="bookDynamicProjectClient<?php echo stripSymbols($modal_id); ?>"  required>
                                                <?php
$modal_clientsResult = $conn->query("SELECT * FROM dynamicprojectsclients LEFT JOIN $clientTable ON $clientTable.id = dynamicprojectsclients.clientid WHERE projectid = '$modal_id'");
        while ($modal_clientRow = $modal_clientsResult->fetch_assoc()) {
            $modal_client_id = $modal_clientRow["id"];
            $modal_client = $modal_clientRow["name"];
            echo "<option value='$modal_client_id'>$modal_client</option>";
        }
        ?>
                                            </select>
                                        <?php else: ?> <!-- no selection if only one client -->
                                        <input id="bookDynamicProjectClient<?php echo stripSymbols($modal_id); ?>"
                                        type="hidden" name="client"
                                        value="<?php echo $conn->query("SELECT * FROM dynamicprojectsclients WHERE projectid = '$modal_id'")->fetch_assoc()["clientid"] ?>" />
                                        <?php endif;?>
                                        <!-- /client selection -->
                                        <label><?php echo $lang["DYNAMIC_PROJECTS_PERCENTAGE_FINISHED"]; ?>*:</label>
                                        <input type="number" class="form-control" name="completed" min="0" max="100" id="bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>" />
                                        <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>">
                                            Abgeschlossen
                                        </label>
                                        </div>
                                        <script>
                                            $("#bookDynamicProjectClient<?php echo stripSymbols($modal_id); ?>").change(function(event){
                                                    $.ajax({
                                                        url: 'ajaxQuery/AJAX_getDynamicProjectClientsCompleted.php',
                                                        dataType: 'json',
                                                        data: {id:"<?php echo $modal_id; ?>",client:$(event.target).val()},
                                                        cache: false,
                                                        type: 'POST',
                                                        success: function (response) {
                                                            $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val(response.completed)
                                                            $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").keyup()
                                                        },
                                                        error: function(response){
                                                            console.error(response.error);
                                                        }
                                                    })
                                                }).change()
                                            $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").keyup(function(event){
                                                console.log(event,$("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val() == 100)
                                                if($("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val() == 100){
                                                    $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked', true);
                                                }else{
                                                    $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked', false);
                                                }
                                            }).keyup()
                                            $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").change(function(event){
                                                console.log(event,$("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val() == 100)
                                                if($("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val() == 100){
                                                    $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked', true);
                                                }else{
                                                    $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked', false);
                                                }
                                            }).change()
                                            $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").change(function(event){
                                                console.log(event)
                                                if($("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked')){
                                                    $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val(100)
                                                }else{
                                                    $("#bookDynamicProjectClient<?php echo stripSymbols($modal_id); ?>").change()
                                                }
                                                $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").keyup()
                                            }).change()
                                        </script>
                                    <!-- /modal body -->
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary" name="pause" value="true"><?php echo $lang['SAVE']; ?></button>
                                </div>
                        </div>
                    </div>
                </div>
            <!-- /booking modal -->