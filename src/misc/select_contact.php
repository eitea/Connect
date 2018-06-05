<div class="row">
	<?php
	$val = uniqid(); //namespace
	$filterCompany = empty($filterings['company']) ? 0 : $filterings['company'];
	$filterClient = empty($filterings['client']) ? 0 : $filterings['client'];
	$filterPerson = empty($filterings['contact']) ? 0 : $filterings['contact'];

	if(!$cmpID){
		$result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
		if($result_fc && $result_fc->num_rows > 1){
			echo '<div class="col-sm-6"><label>'.$lang['COMPANY'].'</label><select class="js-example-basic-single" name="filterCompany"
			onchange="select_client[\''.$val.'\'].showClients(this.value, '.$filterClient.');" >';
			echo '<option value="0">...</option>';
			while($result && ($row_fc = $result_fc->fetch_assoc())){
				$checked = '';
				if($filterCompany == $row_fc['id']) {
					$checked = 'selected';
				}
				echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
			}
			echo '</select></div>';
		} else {
			$filterCompany = $available_companies[1];
		}
	} else {
		$filterCompany = $cmpID;
	}
	?>
	<div class="col-md-6">
		<label><?php echo $lang['CLIENT']; ?></label>
		<select id="clientHint-<?php echo $val; ?>" class="js-example-basic-single" onchange="select_client['<?php echo $val; ?>'].showContacts(this.value, <?php echo $filterClient; ?>);">
			<?php
			if($filterCompany){
				$res = $conn->query("SELECT id, name FROM clientData WHERE companyID = $filterCompany");
				if ($res && $res->num_rows > 1) {echo '<option value="0">...</option>';}
				while ($res && ($row_fc = $res->fetch_assoc())) {
					$selected = $filterClient == $row_fc['id'] ? 'selected' : '';
					echo "<option $selected value='" . $row_fc['id'] . "' >" . $row_fc['name'] . '</option>';
				}
			}
			?>
		</select>
	</div>
	<div class="col-md-6">
		<label>Kontaktperson</label>
		<select id="contactHint-<?php echo $val; ?>" class="js-example-basic-single" name="filterContact">
			<?php
			if($filterClient){
				$res = $conn->query("SELECT id, firstname, lastname FROM contactPersons WHERE clientID = $filterClient ");
				while ($row = $result->fetch_assoc()) {
				    $id = $row['id'];
				    $name = $row['firstname'].' '.$row['lastname'];
				    echo "<option value='$id'>$name</option>";
				}
			}
			?>
		</select>
	</div>
</div>
<script>
if(select_client == null) var select_client = {};
select_client['<?php echo $val; ?>'] = {
	showContacts: function(client, person){
		$.ajax({
			url:'ajaxQuery/AJAX_getContacts.php',
			data:{clientID:client},
			type: 'get',
			success : function(resp){
				$('#contactHint-<?php echo $val; ?>').html(resp);
			},
			error : function(resp){}
		});
	},
	showClients: function(company, client){
		if(company != ""){
			$.ajax({
				url:'ajaxQuery/AJAX_getClient.php',
				data:{companyID:company, clientID:client},
				type: 'get',
				success : function(resp){
					$("#clientHint-<?php echo $val; ?>").html(resp);
				},
				error : function(resp){}
			});
		}
	}
};
</script>
