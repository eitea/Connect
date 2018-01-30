<?php
    require_once __DIR__ . "/sourcefinder.php";
    echo '<button type="button" data-toggle="modal" data-target="#help" onclick="showHelp('.$this_page.')" style="float:right;margin-top:10px;background-color: Transparent;background-repeat:no-repeat;border: none;cursor:pointer;overflow: hidden;outline:none;"><i title="'.$lang['HELP'].'" class="fa fa-2x fa-question-circle"></i></button>'; //invisible button
    /*TODO: get map witch contains pages as keys and id's as values to associate the right help for the right page.
        While the Pages change, this file should not have to be changed at all. If everything is in variables then there should not be any problems while updating connect itself.
        This should, when completed, send an Intend to somewhere else and get back the content for the requested help. This provides flexability while being comepletely debuggable.
    */
?>
  <div class="modal fade" id="help" tabindex="-1" role="dialog" aria-labelledby="Help">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo $lang['HELP'] ?></h4>
        </div>
        <div class="modal-body">
          <!-- <video controls preload="none" width="100%">
            <source src="<?php echo $helpsource["$this_page"] ?? null ?>" type="video/mp4" >
          </video> -->
          <label>Hilfe befindet sich derzeit im Aufbau.</label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
        </div>
      </div>
    </div>
  </div>