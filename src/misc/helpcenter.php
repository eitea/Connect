<button type="button" class="btn help-button btn-empty" data-toggle="modal" data-target="#help"><i class="fa fa-2x fa-question-circle-o"></i></button>
<div class="modal fade" id="help" tabindex="-1" role="dialog" aria-labelledby="Help">
    <div class="modal-dialog modal-content modal-md" role="document">
        <div class="modal-header">
            <h4 class="modal-title"><?php echo $lang['HELP'] ?></h4>
        </div>
        <div class="modal-body">
            <?php
            if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'helpcenter'.DIRECTORY_SEPARATOR.'h_'.$this_page)){
                include __DIR__ .DIRECTORY_SEPARATOR.'helpcenter'.DIRECTORY_SEPARATOR.'h_'.$this_page;
            } else {
                include __DIR__.DIRECTORY_SEPARATOR.'helpcenter'.DIRECTORY_SEPARATOR.'default.php';
            }
             ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
        </div>
    </div>
</div>
