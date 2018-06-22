<div id="taskTemplateEditor-modal" class="modal fade">
	<div class="modal-dialog modal-content modal-md">
		<div class="modal-header h4">Task Templates</div>
		<div class="modal-body">
			<div id="taskTemplateEditor-overview" class="tab-pane fade in active">
				<table class="table">
					<thead>
						<tr>
							<th>Name</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$result = $conn->query("SELECT projectname,projectid,v2 FROM dynamicprojects WHERE isTemplate = 'TRUE'");
						while($row = $result->fetch_assoc()){
							echo '<tr>';
							echo '<td>'.asymmetric_encryption('TASK', $row['projectname'], $userID, $privateKey, $row['v2']).'</td>';
							echo '<td><button type="submit" name="deleteProject" value="'.$row['projectid'].'" class="btn btn-default"><i class="fa fa-trash-o"></i></button></td>';
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
