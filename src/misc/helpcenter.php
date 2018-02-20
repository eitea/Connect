<?php
/* TODO: get map witch contains pages as keys and id's as values to associate the right help for the right page.
  While the Pages change, this file should not have to be changed at all. If everything is in variables then there should not be any problems while updating connect itself.
  This should, when completed, send an Intend to somewhere else and get back the content for the requested help. This provides flexability while being comepletely debuggable.
 */
?>

<button type="button" class="btn help-button btn-empty" data-toggle="modal" data-target="#help"><i class="fa fa-2x fa-question-circle-o"></i></button>
<div class="modal fade" id="help" tabindex="-1" role="dialog" aria-labelledby="Help">
    <div class="modal-dialog modal-content modal-md" role="document">
        <div class="modal-header">
            <h4 class="modal-title"><?php echo $lang['HELP'] ?></h4>
        </div>
        <div class="modal-body">
            <?php
            if (file_exists(dirname(__DIR__) . '/helpcenter/h_' . $this_page)) {
                include dirname(__DIR__) . '/helpcenter/h_' . $this_page;
            } else {
                require_once __DIR__ . '/sourcefinder.php';
                echo "ID = " . $sourceid["$this_page"] ?? null;
                include dirname(__DIR__) . '/helpcenter/default.php';
            }
            ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
        </div>
    </div>
</div>
