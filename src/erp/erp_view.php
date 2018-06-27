<?php include dirname(__DIR__) . '/header.php'; enableToERP($userID);
require dirname(__DIR__) . "/misc/helpcenter.php";
$transitions = array('ANG', 'AUB', 'RE', 'LFS', 'GUT', 'STN');
$filterings = array('savePage' => $this_page, 'procedures' => array(array(), 0, ''), 'company' => 0, 'client' => 0);

if(isset($_GET['t'])){
	$filterings['procedures'][0] = array(strtoupper($_GET['t']));
	$filterings['savePage'] = $this_page.'?val='.$_GET['t'];
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
	if(isset($_POST['delete_proposal'])) {
		$conn->query("DELETE FROM processHistory WHERE id = ".intval($_POST['delete_proposal']));
		if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';}
		$conn->query("DELETE p1 FROM proposals p1 WHERE p1.id NOT IN(SELECT processID FROM processHistory WHERE processID = p1.id)"); echo $conn->error;
	} elseif(isset($_POST['turnBalanceOff'])) {
		$conn->query("UPDATE UserData SET erpOption = 'FALSE' WHERE id = $userID");
	} elseif(isset($_POST['turnBalanceOn'])) {
		$conn->query("UPDATE UserData SET erpOption = 'TRUE' WHERE id = $userID");
	} elseif(!empty($_POST['copy_process'])) {
		$val = intval($_POST['copy_process']);
		$result = $conn->query("SELECT processID, id_number FROM processHistory WHERE id = $val"); echo $conn->error;
		$row = $result->fetch_assoc();
		$processID = $row['processID'];
		//copy process
		$conn->query("INSERT INTO proposals(clientID, deliveryDate, paymentMethod, shipmentType, representative, porto, portoRate, header, referenceNumrow)
		SELECT clientID, deliveryDate, paymentMethod, shipmentType, representative, porto, portoRate, header, referenceNumrow FROM proposals WHERE id = $processID"); echo $conn->error;
		//insert history
		$processID = $conn->insert_id;
		$conn->query("INSERT INTO processHistory(id_number, processID, status) VALUES('".$row['id_number']."', $processID, 0)"); echo $conn->error;
		//insert products
		$historyID = $conn->insert_id;
		$origin = randomPassword(16);
		$conn->query("INSERT INTO products(name, description, price, quantity, unit, taxID, cash, purchase, position historyID, origin)
		SELECT name, description, price, quantity, unit, taxID, cash, purchase, position, $historyID, '$origin' FROM products WHERE historyID = $val");
		if($conn->error){
			echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
		} else {
			echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
		}
	} elseif(!empty($_POST['setComplete'])) {
		$val = intval($_POST['setComplete']);
		$conn->query("UPDATE processHistory SET status = 2 WHERE id = $val");
		if($conn->error){
			echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
		} else {
			echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
		}
	} elseif(isset($_POST['add_new_process']) && !empty($_POST['filterClient']) && !empty($_POST['nERP'])){
		$val = intval($_POST['filterClient']);
		$result = $conn->query("SELECT representative, paymentMethod, shipmentType FROM clientInfoData WHERE clientID = $val");
		if($result && $row = $result->fetch_assoc()){
			$meta_paymentMethod = $row['paymentMethod'];
			$meta_shipmentType = $row['shipmentType'];
			$meta_representative = $row['representative'];
		} else {
			echo $conn->error;
			$meta_paymentMethod = $meta_shipmentType = $meta_representative = '';
		}
		$date = getCurrentTimestamp();
		if(isset($_POST['filterCompany'])){
			$num = getNextERP(test_input($_POST['nERP']), $_POST['filterCompany']);
		} else {
			$num = getNextERP(test_input($_POST['nERP']), $available_companies[1]);
		}
		$conn->query("INSERT INTO proposals (clientID, curDate, deliveryDate, paymentMethod, shipmentType, representative)
		VALUES ($val, '$date', '$date', '$meta_paymentMethod', '$meta_shipmentType', '$meta_representative')");
		$val = $conn->insert_id;
		$conn->query("INSERT INTO processHistory (id_number, processID, status) VALUES('$num', $val, 0)");
		$val = $conn->insert_id;
		if(!$conn->error){
			redirect("edit?val=$val");
		} else {
			echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
		}
	}
}
if(isset($_GET['err'])){
	echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>';
	$val = $_GET['err'];
	if($val == 1){
		echo $lang['ERROR_MISSING_SELECTION'];
	} elseif($val == 2){
		echo $lang['ERROR_UNEXPECTED'];
	} else {
		echo 'Unknown error';
	}
	echo '</div>';
}
$result = $conn->query("SELECT * FROM clientData WHERE companyID IN (".implode(', ', $available_companies).")");
if(!$result || $result->num_rows <= 0){
	echo '<div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
	echo '<a class="btn btn-warning" data-toggle="modal" data-target="#create_client">'.$lang['NEW_CLIENT_CREATE'].'</a>';
	echo '</div>';
	include dirname(__DIR__) . "/misc/new_client_buttonless.php";
}
$result = $conn->query("SELECT erpOption FROM UserData WHERE id = $userID");
if($result && ($row = $result->fetch_assoc())){ $showBalance = $row['erpOption'];} else { $showBalance = 'FALSE'; }
?>
<div class="page-header-fixed">
	<div class="page-header">
		<h3><?php echo $lang['PROCESSES']; ?>
			<div class="page-header-button-group">
				<?php include dirname(__DIR__) . '/misc/set_filter.php'; ?>
				<button type="button" class="btn btn-default" data-toggle="modal" data-target=".add_process" title="<?php echo $lang['NEW_PROCESS']; ?>"><i class="fa fa-plus"></i></button>
				<form method="post" style="display:inline-block">
					<?php
					if($showBalance == 'TRUE'){
						echo '<button type="submit" name="turnBalanceOff" class="btn btn-warning" title="Bilanz deaktivieren"><i class="fa fa-check"></i> Bilanz</button>';
					} else {
						echo '<button type="submit" name="turnBalanceOn" class="btn btn-default" title="Bilanz aktivieren"><i class="fa fa-times"></i> Bilanz</button>';
					}
					?>
				</form>
			</div>
		</h3>
	</div>
</div>
<div class="page-content-fixed-130">
	<?php
	$filtered_transitions = empty($filterings['procedures'][0]) ? $transitions : $filterings['procedures'][0];
	$filterCompany_query = $filterings['company'] ?  'AND clientData.companyID = '.$filterings['company'] : "";
	$filterClient_query = $filterings['client'] ?  'AND clientData.id = '.$filterings['client'] : "";
	$filterStatus_query = ($filterings['procedures'][1] >= 0) ? 'AND status = '.$filterings['procedures'][1] : "";

	//5af0481d917fe
	$result = $conn->query("SELECT id_number, status, p.id, p.clientID, p.curDate, companyID, processHistory.id AS historyID, clientData.name AS clientName, companyData.name AS companyName
		FROM processHistory INNER JOIN proposals p ON p.id = processHistory.processID
		INNER JOIN clientData ON p.clientID = clientData.id INNER JOIN companyData ON clientData.companyID = companyData.id
		WHERE companyID IN (".implode(', ', $available_companies).") $filterCompany_query $filterClient_query $filterStatus_query
		ORDER BY id_number");
		echo $conn->error;
		?>

		<table class="table table-hover">
			<thead>
				<?php if(count($available_companies) > 2){ echo '<th>'.$lang['COMPANY'].'</th>';} ?>
				<th><?php echo $lang['CLIENT']; ?></th>
				<th>ID</th>
				<th>Status</th>
				<?php if($showBalance == 'TRUE') echo '<th>Bilanz</th>'; ?>
				<th>Vorgang</th>
				<th>Datum</th>
				<th>Option</th>
			</thead>
			<tbody>
				<?php
				//5af0481d917fe
				$modals = '';
				$stmt_balance = $conn->prepare("SELECT quantity, price, purchase, origin FROM products WHERE historyID = ? AND origin IS NOT NULL");
				$stmt_balance->bind_param("i", $historyID);
				while($result && ($row = $result->fetch_assoc())){
					$historyID = $row['historyID'];
					$current_transition = preg_replace('/\d/', '', $row['id_number']);
					if(!in_array($current_transition, $filtered_transitions)) continue;
					echo '<tr>';
					if(count($available_companies) > 2){ echo '<td>'.$row['companyName'].'</td>'; }
					echo '<td>'.$row['clientName'].'</td>';
					echo '<td>'.$row['id_number'].'</td>';
					echo '<td>'.$lang['OFFERSTATUS_TOSTRING'][intval($row['status'])].'</td>';
					if($showBalance == 'TRUE'){
						$balance = 0;
						$stmt_balance->execute();
						$result_b = $stmt_balance->get_result();
						while($rowB = $result_b->fetch_assoc()){
							if(empty($product_placements[$current_transition][$rowB['origin']])) $product_placements[$current_transition][$rowB['origin']] = 0;
							$product_placements[$current_transition][$rowB['origin']] += $rowB['quantity'];
							$balance += $rowB['quantity'] * ($rowB['price'] - $rowB['purchase']);
						}
						$style = $balance > 0 ? "style='color:#6fcf2c;font-weight:bold;'" : "style='color:#facf1e;font-weight:bold;'";
						echo "<td $style>".number_format($balance, 2, ',', '.').' EUR</td>';
					}

					$transitable = false;
					if($current_transition == 'ANG') {$transitable = true; $available_transitions = array('AUB', 'RE', 'STN');}
					if($current_transition == 'AUB') {$transitable = true; $available_transitions = array('RE', 'LFS', 'STN');}
					if($current_transition == 'RE') {$transitable = true; $available_transitions = array('LFS', 'GUT');}
					echo '<td>'.substr(md5($row['id']),0,8).'<i style="color:#'.substr(md5($row['id']),0,6).'" class="fa fa-circle"></i></td>';
					echo '<td>'.date('d.m.Y', strtotime($row['curDate'])).'</td>';
					echo '<td>';
					if($current_transition == 'RE' && !$row['status']){
						echo '<a data-target=".ask-complete-'.$historyID.'" data-toggle="modal" class="btn btn-default btn-sm" title="Download"><i class="fa fa-download"></i></a> ';
						$modals .= '<form method="POST">
						<div class="modal fade ask-complete-'.$historyID.'">
						<div class="modal-dialog modal-sm modal-content">
						<div class="modal-header"><h4 class="modal-title">'.$row['id_number'].'</h4></div>
						<div class="modal-body">Wollen Sie diese Rechnung abschließen?</div>
						<div class="modal-footer">
						<a href="download?proc='.$historyID.'" class="btn btn-default" target="_blank" onclick="$(\'.ask-complete-'.$historyID.'\').modal(\'hide\');">'.$lang['CONFIRM_CANCEL'].'</a>
						<button type="submit" name="setComplete" class="btn btn-warning" value="'.$historyID.'">'.$lang['CONFIRM'].'</button>
						</div> </div> </div> </form>';
					} else {
						echo "<a href='download?proc=$historyID' class='btn btn-default btn-sm' target='_blank'><i class='fa fa-download'></i></a> ";
					}
					echo '<form method="POST" style="display:inline"><button type="submit" class="btn btn-default btn-sm" name="copy_process" title="'.$lang['COPY'].'" value="'.$historyID.'"><i class="fa fa-files-o"></i></button></form> ';
					echo '<a href="edit?val='.$historyID.'" title="'.$lang['EDIT'].'" class="btn btn-default btn-sm"><i class="fa fa-pencil"></i></a> ';

					if($transitable){ //if open positions
						if($current_transition != 'RE'){ echo '<button type="button" class="btn btn-default btn-sm" title="'.$lang['DELETE'].'" data-toggle="modal" data-target=".confirm-delete-'.$historyID.'"><i class="fa fa-trash-o"></i></button> '; }
						echo '<a style="margin-left: 20px" data-target=".choose-transition-'.$historyID.'" data-toggle="modal" class="btn btn-warning btn-sm" title="'.$lang['TRANSITION'].'"><i class="fa fa-arrow-right"></i></a>';

						$modal_transits = '';
						foreach($available_transitions as $t){
							$modal_transits .= '<div class="row"><div class="col-xs-6"><label><input type="radio" name="copy_transition" value="'.$t.'" />'.getNextERP($t, $row['companyID']).'</label></div><div class="col-xs-6">'.$lang['PROPOSAL_TOSTRING'][$t].'</div></div>';
						}
						$modals .= '<form method="POST" action="edit?val='.$historyID.'">
						<div class="modal fade choose-transition-'.$historyID.'">
						<div class="modal-dialog modal-sm modal-content">
						<div class="modal-header"><h3>'.$lang['TRANSITION'].'</h3></div>
						<div class="modal-body"><div class="radio">'.$modal_transits.'</div></div>
						<div class="modal-footer">
						<button type="button" data-dismiss="modal" class="btn btn-default">Cancel</button>
						<button type="submit" class="btn btn-warning" name="translate">OK</button>
						</div> </div> </div></form><form method="POST">
						<div class="modal fade confirm-delete-'.$historyID.'">
						<div class="modal-dialog modal-sm modal-content">
						<div class="modal-header"><h4 class="modal-title">'.sprintf($lang['ASK_DELETE'], $row['id_number']).'</h4></div>
						<div class="modal-body">'.$lang['WARNING_DELETE_TRANSITION'].'</div>
						<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">'.$lang['CONFIRM_CANCEL'].'</button>
						<button type="submit" name="delete_proposal" class="btn btn-warning" value="'.$historyID.'">'.$lang['CONFIRM'].'</button>
						</div> </div> </div> </form>';
					} //endif transitable
					echo '</td>';
					echo '</tr>';
				}
				echo $conn->error;
				?>

			</tbody>
		</table>
		<?php echo $modals; ?>
		<form method="POST">
			<div class="modal fade add_process">
				<div class="modal-dialog modal-md modal-content">
					<div class="modal-header"><h4><?php echo $lang['NEW_PROCESS']; ?></h4></div>
					<div class="modal-body">
						<div class="container-fluid">
							<div class="col-sm-12"><?php include dirname(__DIR__) . '/misc/select_client.php'; ?></div>
							<div class="col-sm-6"><br>
								<label><?php echo $lang['CHOOSE_PROCESS']; ?></label>
								<select class="js-example-basic-single" name="nERP">
									<option value="ANG"><?php echo $lang['PROPOSAL_TOSTRING']['ANG']; ?></option>
									<option <?php if($filterings['procedures'][0] == 'sub') echo "selected"; ?> value="AUB"><?php echo $lang['PROPOSAL_TOSTRING']['AUB']; ?></option>
									<option <?php if($filterings['procedures'][0] == 're') echo "selected"; ?> value="RE"><?php echo $lang['PROPOSAL_TOSTRING']['RE']; ?></option>
									<option <?php if($filterings['procedures'][0] == 'lfs') echo "selected"; ?> value="LFS"><?php echo $lang['PROPOSAL_TOSTRING']['LFS']; ?></option>
								</select>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" data-dismiss="modal" class="btn btn-default">Cancel</button>
						<button type="submit" class="btn btn-warning" name="add_new_process" value="<?php echo $historyID; ?>"><?php echo $lang['CONTINUE']; ?></button>
					</div>
				</div>
			</div>
		</form>

		<script>
		$(document).ready(function(){
			$('.table').DataTable({
				language: {
					<?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
				},
				responsive: true,
				autoWidth: false,
				paging: true
			});
		});
		<?php
		if(!empty($_POST['setComplete'])){
			echo 'window.location.replace("download?proc='.$_POST['setComplete'].'");';
		}
		?>
	</script>
</div>
<?php include dirname(__DIR__) . '/footer.php'; ?>
