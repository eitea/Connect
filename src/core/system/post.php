<!-- TODO: remove static strings -->
<?php include dirname(dirname(__DIR__)) .'/header.php';?>
<!-- +-Button -->
<div class="page-header-fixed">
    <div class="page-header">
        <button class="btn btn-default" data-toggle="modal" data-target="#postMessages" type="button"><?php echo "+"; ?></button>
    </div>
</div>

<!-- Post Popup -->
<form method="post" enctype="multipart/form-data">
      <div class="modal fade" id="postMessages" tabindex="-1" role="dialog" aria-labelledby="postLabel">
          <div class="modal-dialog" role="form">
              <div class="modal-content">

              <div class="modal-header">
                    <!-- x Button -->
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="postLabel"><?php echo $lang['POST']; ?></h4>
                </div>
                <br>

                <div class="modal-body">
                    <!-- modal body -->
                    <label for="betreff"> <?php echo $lang['SUBJECT']; ?> </label>
                    <input type="text" class="form-control" name="betreff">

                    <label for="betreff"> <?php echo $lang['POST_TO']; ?> </label>
                    <input type="text" class="form-control" name="betreff">
                    <!-- /modal body -->
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning" name="send"><?php echo $lang['SEND']; ?></button>
                </div>

              </div>
          </div>
      </div>
  </form>

<?php include dirname(dirname(__DIR__)).'/footer.php'; ?>
