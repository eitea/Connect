<?php
/* DOES NOT INCLUDE BACKEND
$upload_viewer = [
'accessKey' => 'mc_status'
'category' => ''
'categoryID' => ''
]
*/
if(!function_exists('drawFolder')){
	function drawFolder($parent_structure, $visibility = true){
		global $conn;
		global $upload_viewer;
		$cat = $upload_viewer['category'];
		$catID = $upload_viewer['categoryID'];
		$html = '<div id="folder-'.$catID.'-'.$parent_structure.'" >';
		if(!$visibility) $html = substr_replace($html, 'style="display:none"', -1, 0);

		if($parent_structure != 'ROOT') $html .= '<div class="row"><div class="col-xs-1"><i class="fa fa-arrow-left"></i></div>
		<div class="col-xs-3"><button type="button" class="btn btn-link tree-node-back-'.$catID.'" data-parent="'.$parent_structure.'">Zurück</button></div></div>';
		$subfolder = '';
		$result = $conn->query("SELECT id, name, uploadDate, type, uniqID, uploadUser FROM archive WHERE category = '$cat' AND categoryID = '$catID' AND parent_directory = '$parent_structure' ORDER BY type <> 'folder', type ASC ");
		echo $conn->error;
		while($result && ($row = $result->fetch_assoc())){
			$html .= '<div class="row">';
			if($row['type'] == 'folder'){
				$html .= '<div class="col-xs-1"><i class="fa fa-folder-open-o"></i></div>
						<div class="col-xs-3"><a class="folder-structure-'.$catID.'" data-child="'.$row['id'].'" data-parent="'.$parent_structure.'" >' .$row['name'].'</a></div>
						<div class="col-xs-3">'.$row['uploadDate'].'</div><div class="col-xs-2"></div><div class="col-xs-3 text-right">';
				$folder_res = $conn->query("SELECT id FROM archive WHERE category = '$cat' AND categoryID = $catID AND parent_directory = '".$row['id']."' ");
				if($folder_res->num_rows < 1){
					$html .= '<form method="POST"><button type="submit" name="delete-folder" value="'.$row['id'].'" class="btn btn-default"><i class="fa fa-trash-o"></i></button>';
				}
				$html .= '</div>';
				$subfolder .= drawFolder($row['id'], false);
			} else {
				$html .= '<div class="col-xs-1"><i class="fa fa-file-o"></i></div><div class="col-xs-3">'.$row['name'].'</div><div class="col-xs-3">'.$row['uploadDate'].'</div>';
				$html .= '<div class="col-xs-2">';
				if($row['uploadUser']){
					$res_u = $conn->query("SELECT firstname, lastname FROM UserData WHERE id = ".$row['uploadUser']);
					if($res_u && ($row_u = $res_u->fetch_assoc())) $html .= $row_u['firstname'].' '.$row_u['lastname'];
				}
				$html .= '</div>';
				$html .='<div class="col-xs-3 text-right"><form method="POST" style="display:inline">
				<button type="submit" class="btn btn-default" name="delete-file" value="'.$row['uniqID'].'"><i class="fa fa-trash-o"></i></button></form>
				<form method="POST" style="display:inline" action="../project/detailDownload" target="_blank"><input type="hidden" name="keyReference" value="'.$cat.'_'.$catID.'" />
				<button type="submit" class="btn btn-default" name="download-file" value="'.$row['uniqID'].'"><i class="fa fa-download"></i></button>
				</form></div>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';
		$html .= $subfolder;
		return $html;
	}
}
?>

<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading"><?php echo mc_status($upload_viewer['accessKey']); ?>Datei Upload (Archiv)
				<div class="page-header-button-group">
					<div class="btn-group"><a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" title="Hochladen..."><i class="fa fa-upload"></i></a>
						<ul class="dropdown-menu">
							<li><a data-toggle="modal" data-target="#modal-new-folder-<?php echo $upload_viewer['categoryID']; ?>">Neuer Ordner</a></li>
							<li><a data-toggle="modal" data-target="#modal-new-file-<?php echo $upload_viewer['categoryID']; ?>">File</a></li>
						</ul>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-1 bold">Type</div>
				<div class="col-xs-3 bold">Name</div>
				<div class="col-xs-3 bold">Upload Datum</div>
				<div class="col-xs-2 bold">Benutzer</div>
				<!-- div class="col-xs-3">Operations</div-->
			</div>
			<?php echo drawFolder('ROOT', $upload_viewer['category'], $upload_viewer['categoryID']); ?>
		</div>
	</div>
</div>

<div id="modal-new-folder-<?php echo $upload_viewer['categoryID']; ?>" class="modal fade">
	<div class="modal-dialog modal-content modal-sm">
		<form method="POST">
			<input type="hidden" name="saveThisProject" value="<?php echo $upload_viewer['categoryID']; ?>" />
			<div class="modal-header h4">Neuer Ordner</div>
			<div class="modal-body">
				<label>Name</label>
				<input type="text" name="new-folder-name" class="form-control" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning modal-new-<?php echo $upload_viewer['categoryID']; ?>" name="add-new-folder" value="ROOT"><?php echo $lang['ADD']; ?></button>
			</div>
		</form>
	</div>
</div>
<div id="modal-new-file-<?php echo $upload_viewer['categoryID']; ?>" class="modal fade">
	<div class="modal-dialog modal-content modal-sm">
		<form method="POST" enctype="multipart/form-data">
			<input type="hidden" name="saveThisProject" value="<?php echo $upload_viewer['categoryID']; ?>" />
			<div class="modal-header h4">File Hochladen</div>
			<div class="modal-body">
				<label class="btn btn-default">
					Datei Auswählen
					<input type="file" name="new-file-upload"  accept="application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint, text/plain, application/pdf,.doc, .docx" style="display:none" >
				</label>
				<small>Max. 15MB<br>Text, PDF, .Zip und Office</small>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning modal-new-<?php echo $upload_viewer['categoryID']; ?>" name="add-new-file" value="ROOT"><?php echo $lang['ADD']; ?></button>
			</div>
		</form>
	</div>
</div>

<script>
var grandParent = (typeof grandParent === 'undefined') ? [] : grandParent;
var index = <?php echo $upload_viewer['categoryID']; ?>;
grandParent[index] = ['ROOT'];
$('.tree-node-back-'+index).click(function(){
	var grandPa = grandParent[index].pop();
	//alert(grandPa);
	$('#folder-'+index+'-'+ $(this).data('parent')).hide();
	$('#folder-'+index+'-'+ grandPa).fadeIn();
	$('.modal-new-'+index).val(grandPa);
});
$('.folder-structure-'+index).click(function(event){
	$('#folder-'+index+'-'+ $(this).data('parent')).hide();
	$('#folder-'+index+'-'+ $(this).data('child')).fadeIn();
	//alert('#folder-'+index+'-'+ $(this).data('child'));
	grandParent[index].push($(this).data('parent'));
	$('.modal-new-'+index).val($(this).data('child'));
});
</script>
