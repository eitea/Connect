<?php
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
    <button class="btn btn-default" data-toggle="modal" data-target="#dynamicComments<?php echo stripSymbols($modal_id) ?>" type="button">
        <i class="fa fa-comment"></i>
    </button>

    
    <!-- new dynamic project modal -->
    <form method="post" autocomplete="off" id="projectForm<?php echo stripSymbols($modal_id) ?>">
        <input type="hidden" name="id" value="<?php echo $modal_id ?>">
        <div class="modal fade" id="dynamicComments<?php echo stripSymbols($modal_id) ?>" tabindex="-1" role="dialog" aria-labelledby="dynamicCommentsLabel<?php echo stripSymbols($modal_id) ?>">
            <div class="modal-dialog" role="form">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="dynamicCommentsLabel<?php echo stripSymbols($modal_id) ?>">
                            <?php echo $modal_title; ?>
                        </h4>
                    </div>
                    <!-- modal body -->
                    <!-- tab buttons -->
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a data-toggle="tab" href="#dynamicCommentsSection1<?php echo stripSymbols($modal_id) ?>">Notizen</a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#dynamicCommentsSection2<?php echo stripSymbols($modal_id) ?>">Section 2</a>
                        </li>
                    </ul>
                    <!-- /tab buttons -->
                    <div class="tab-content">
                        <div id="dynamicCommentsSection1<?php echo stripSymbols($modal_id) ?>" class="tab-pane fade in active">
                            <div class="modal-body">
                                <!-- Notes -->
                                <table style="width:100%">
                                
                                <?php
                                $modal_result = $conn->query("SELECT * FROM dynamicprojectsnotes");
                                while($modal_row = $modal_result->fetch_assoc()){
                                   // var_dump($modal_row);
                                    echo "<tr>";
                                    echo "<td>{$modal_row['notetext']}</td>";
                                    echo "<td>{$modal_row['notedate']}</td>";
                                    echo "<td>{$modal_row['notecreator']}</td>";
                                    echo "</tr>";
                                }
                                ?>
                                </table>
                                <form>
                                <input type="hidden" name="id" value="<?php echo $modal_id ?>">
                                    <input type="text" name="notetext" /> <button type="submit" name="note" value="true">Submit</button>
                                    <button type="submit" name="deletenote" value="true">Delete all</button>
                                </form>
                                <!-- /Notes -->
                            </div>
                        </div>
                        <div id="dynamicCommentsSection2<?php echo stripSymbols($modal_id) ?>" class="tab-pane fade">
                            <div class="modal-body">
                                <!-- s2 -->
                              s2
                                <!-- /s2 -->
                            </div>
                        </div>
                    </div>
                    <!-- /modal body -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo $lang['CANCEL']; ?>
                        </button>
                                    <button type="submit" class="btn btn-warning show-required-fields<?php echo stripSymbols($modal_id) ?>" <?php if($modal_id): ?> name="editDynamicProject" <?php else: ?> name="dynamicProject" <?php endif; ?>  >
                            <?php echo $lang['SAVE']; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- /new dynamic project modal -->
