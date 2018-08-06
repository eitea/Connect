<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID); //5b34fa15e7a23 ?>
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
		$type = 'text';
		$extra = '';
		if($_POST['edit_tag_unit'] && $_POST['edit_tag_number']) { //5b6800f1881fb
			$type = test_input($_POST['edit_tag_unit']);
			$extra = intval($_POST['edit_tag_number']);
		}
		if(!empty($_POST['saveTag'])){
			$val = intval($_POST['saveTag']);
			$conn->query("UPDATE tags SET value = '$name', type = '$type', extra = '$extra' WHERE id = $val");
			if($conn->error){
				showError($conn->error);
			} else {
				showSuccess($lang['OK_SAVE']);
			}
		} else {
			$conn->query("INSERT INTO tags (value, type, extra) VALUES('$name', '$type', '$extra')");
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
  <div class="modal-dialog modal-content modal-md">
	<div class="modal-header h4">Tag</div>
	<div class="modal-body">
		<div class="col-md-12">
			<label for="edit_tag_value">Name</label>
			<input type="text" id="edit_tag_value" name="edit_tag_value" value="" class="form-control"><br>
		</div>
		<div class="col-md-6">
			<label for="edit_tag_unit"><?php echo $lang['DELETION_PERIOD']; ?></label>
			<select class="form-control duration-unit-select" name="edit_tag_unit" id="edit_tag_unit">
				<option value="">...</option>
				<option value="date_days"> <?php echo $lang['TIME_UNIT_TOSTRING'][1]; ?></option>
				<option value="date_weeks"> <?php echo $lang['TIME_UNIT_TOSTRING'][2]; ?></option>
				<option value="date_months"> <?php echo $lang['TIME_UNIT_TOSTRING'][3]; ?></option>
				<option value="date_years"> <?php echo $lang['TIME_UNIT_TOSTRING'][4]; ?></option>
			</select><br>
		</div>
		<div class="col-md-6">
			<label for="edit_tag_unit"><?php echo $lang['UNIT']; ?></label>
			<select class="form-control duration-number-select" name="edit_tag_number" id="edit_tag_number" >
				<option value="">...</option>
				<?php
				for ($k = 1;$k<=30;++$k) {
					echo "<option value='$k'>$k</option>";
				}
				?>
			</select><br>
		</div>
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
