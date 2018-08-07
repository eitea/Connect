<?php include dirname(dirname(__DIR__)) . '/header.php';  //5b34fa15e7a23 ?>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	if(!empty($_POST['delete_tag'])){
		$val = intval($_POST['delete_tag']);
		$conn->query("DELETE FROM tags WHERE id = $val");
		if($conn->error){
			showError($conn->error);
		} else {
			showSuccess($lang['OK_DELETE']);
		}
	}
	if(isset($_POST['saveTag'])){
		$name = test_input($_POST['edit_tag_value']);
		if(!empty($_POST['saveTag'])){
			$val = intval($_POST['saveTag']);
			$conn->query("UPDATE tags SET value = '$name' WHERE id = $val");
			if($conn->error){
				showError($conn->error);
			} else {
				showSuccess($lang['OK_SAVE']);
			}
		} else {
			$conn->query("INSERT INTO tags (value) VALUES('$name')");
			if($conn->error){
				showError($conn->error);
			} else {
				showSuccess($lang['OK_CREATE']);
			}
		}
	}
}
?>
<form method="POST">
	<div class="page-header">
		<h3>E-mail <?php echo $lang['OPTIONS']; ?>
			<div class="page-header-button-group"><a class="btn btn-default" data-toggle="modal" href="#edit-tag-modal"><i class="fa fa-plus"></i></a></div>
		</h3>
	</div>
	<div class="col-md-4"><h4>Tags</h4></div>
	<table class="table table-striped">
		<thead>
			<tr>
				<th>Tag</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$result = $conn->query("SELECT id, value FROM tags");
			while($row = $result->fetch_assoc()){
				echo '<tr><td>',$row['value'],'</td><td>';
				echo '<button type="submit" name="delete_tag" value="', $row['id'] ,'" class="btn btn-default"><i class="fa fa-trash-o"></i></button>';
				echo '<a href="#edit-tag-modal"  data-toggle="modal" data-value="', $row['value'] ,'" data-valid="', $row['id'] ,'" class="btn btn-default"><i class="fa fa-pencil"></i></button>';
				echo '</td><tr>';
			}
			?>
		</tbody>
	</table>
</form>

<form method="post">
	<div id="edit-tag-modal" class="modal fade">
  <div class="modal-dialog modal-content modal-sm">
	<div class="modal-header h4">Tag</div>
	<div class="modal-body">
		<label for="edit_tag_value">Name</label>
		<input type="text" id="edit_tag_value" name="edit_tag_value" value="" class="form-control">
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-warning" value="" name="saveTag" id="edit_tag_id"><?php echo $lang['SAVE']; ?></button>
	</div>
  </div>
</form>

<script type="text/javascript">
$('#edit-tag-modal').on('show.bs.modal', function (event) {
var button = $(event.relatedTarget);
$("#edit_tag_value").val(button.data('value'));
$("#edit_tag_id").val(button.data('valid'));
});
</script>
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
