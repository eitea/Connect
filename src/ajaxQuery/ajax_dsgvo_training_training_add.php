<?php 
if (!isset($_REQUEST["moduleID"])){
    echo "error";
    die();
}
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";

$moduleID = intval($_REQUEST["moduleID"]);
?>
<form method="post">
  <div class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><i class="fa fa-cube"></i> Neues Modul</h4>
        </div>
        <div class="modal-body">
        <label>Name*</label>
        <input type="text" class="form-control" name="name" placeholder="Name des Moduls" required/>
        <input type="hidden" name="module" value="<?php echo $moduleID; ?>"/>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-warning" name="createTraining" value="true"><?php echo $lang['ADD']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form>