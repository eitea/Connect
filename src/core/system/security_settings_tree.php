<?php

if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['saveRoles']) && has_permission("WRITE","CORE","SECURITY")) {
    $x = intval($_POST['saveRoles']);
    if($x != 1){
        $stmt_insert_permission_relationship = $conn->prepare("INSERT INTO relationship_access_permissions (userID, permissionID, type) VALUES (?, ?, ?)");
        echo $conn->error;
        $stmt_insert_permission_relationship->bind_param("iis", $x, $permissionID, $type);
        $conn->query("DELETE FROM relationship_access_permissions WHERE userID = $x");
        foreach ($_POST as $key => $type) {
            if(str_starts_with("PERMISSION", $key)){
                $arr = explode(";", $key);
                // $groupID = intval($arr[1]);
                $permissionID = intval($arr[2]);
                $stmt_insert_permission_relationship->execute();
                echo $stmt_insert_permission_relationship->error;
            }        
        }
        $stmt_insert_permission_relationship->close();    
    }
}

$result = $conn->query("SELECT id,name FROM access_permission_groups ORDER BY id");
$permission_groups = $result->fetch_all(MYSQLI_ASSOC);

?>
<h4>Benutzer Verwaltung</h4><br>


<div class="container-fluid panel-group" id="accordion">
    <?php
    $result = $conn->query("SELECT * FROM roles");
    while ($result && ($row = $result->fetch_assoc())):
        $x = $row['userID'];
		$res_access = $conn->query("SELECT module FROM security_access WHERE userID = $x AND outDated = 'FALSE'")->fetch_all();
		$hasAccessTo = array_column($res_access, 0);
        ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="heading<?php echo $x; ?>">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $x; ?>"><?php echo $userID_toName[$x]; ?></a>
                </h4>
            </div>
            <div id="collapse<?php echo $x; ?>" class="panel-collapse collapse <?php if($x == $activeTab) echo 'in'; ?>">
                <div class="panel-body">
                    <form method="POST">
						<h4><?php echo $lang['COMPANIES']; ?>:</h4>
						<div class="row checkbox">
							<?php
							$selection_company_checked = $selection_company;
							$stmt_company_relationship->execute();
							$result_relation = $stmt_company_relationship->get_result();
							while($row_relation = $result_relation->fetch_assoc()){
								$needle = 'value="'.$row_relation['companyID'].'"';
								if(strpos($selection_company_checked, $needle) !== false){
									$selection_company_checked = str_replace($needle, $needle.' checked ', $selection_company_checked);
								}
							}
							echo $selection_company_checked;
							?>
							<div class="col-md-12"><small>*<?php echo $lang['INFO_COMPANYLESS_USERS']; ?></small></div>
                        </div>
                        <h4>Berechtigungen <small>Momentan funktioniert nur CORE.SECURITY und DSGVO (<b>DSGVO Admin Modul muss momentan noch aktiv sein (wegen Verschlüsselung)</b>)</small></h4>
                        <?php 
                            foreach($permission_groups as $permission_group){
                                $groupID = $permission_group["id"];
                                $group_name = $permission_group["name"];
                                $permission_result = $conn->query("SELECT access_permissions.id,access_permissions.name,type FROM access_permissions LEFT JOIN relationship_access_permissions ON relationship_access_permissions.permissionID = access_permissions.id AND userID = $x WHERE groupID = $groupID");
                                ?>
                            <div class="row">
                                <div class="col-xs-12"><label> <?php echo $group_name ?></label></div>
                            </div>
                            <div class="row col-xs-offset-1 col-xs-11">
                               
                                <?php
                                while($permission_result && $permission_row = $permission_result->fetch_assoc()){
                                    $permission_name = $permission_row["name"];
                                    $permissionID = $permission_row["id"];
                                    $read_checked = ($permission_row["type"] === 'READ'||$permission_row["type"] === 'WRITE')?"checked":"";
                                    $write_checked = ($permission_row["type"] === 'WRITE')?"checked":"";
                                    if ($x == 1){
                                        $read_checked = "disabled checked";
                                        $write_checked = "disabled checked";
                                    }
                            ?>
                           
                            
                             <div class="col-md-6">
                                 <!-- data attributes are used for (un)checking permissions -->
                            <input type="checkbox" data-write="#<?php echo "${x}_WRITE_${groupID}_${permissionID}"; ?>" id="<?php echo "${x}_READ_${groupID}_${permissionID}"; ?>" name="<?php echo "PERMISSION;".$groupID.";".$permissionID ?>" value="READ" <?php echo $read_checked ?>>
                            <label>
                                    READ <?php echo $permission_name ?>
                                </label>
                                </div>
                                <div class="col-md-6">
                                <input type="checkbox" data-read="#<?php echo "${x}_READ_${groupID}_${permissionID}"; ?>" id="<?php echo "${x}_WRITE_${groupID}_${permissionID}"; ?>" name="<?php echo "PERMISSION;".$groupID.";".$permissionID ?>" value="WRITE" <?php echo $write_checked ?>>
                            <label>
                                     WRITE <?php echo $permission_name ?>
                                </label>
                                </div>
                              
                               
                            <?php   
                                }?>
                                
                                </div><?php
                            } 
                            ?>
                        <h4><?php echo $lang['ADMIN_MODULES']; ?></h4>
                        <div class="row checkbox">
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isCoreAdmin" <?php if($row['isCoreAdmin'] == 'TRUE'){echo 'checked';} ?>><?php echo $lang['ADMIN_CORE_OPTIONS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isTimeAdmin" <?php if($row['isTimeAdmin'] == 'TRUE'){echo 'checked';} ?>><?php echo $lang['ADMIN_TIME_OPTIONS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isProjectAdmin" <?php if($row['isProjectAdmin'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['PROJECTS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isReportAdmin" <?php if($row['isReportAdmin'] == 'TRUE'){echo 'checked';} ?>  /><?php echo $lang['REPORTS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isERPAdmin" <?php if(!array_key_exists('ERP', $grantable_modules) && $row['isERPAdmin'] == 'FALSE'){ echo 'disabled';} elseif($row['isERPAdmin'] == 'TRUE'){echo 'checked';} ?> />ERP
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isDynamicProjectsAdmin" <?php if($row['isDynamicProjectsAdmin'] == 'TRUE'){echo 'checked';} ?>><?php echo $lang['DYNAMIC_PROJECTS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isFinanceAdmin" <?php if($row['isFinanceAdmin'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['FINANCES']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isDSGVOAdmin" <?php if(!array_key_exists('DSGVO', $grantable_modules) && $row['isDSGVOAdmin'] == 'FALSE'){ echo 'disabled';} elseif($row['isDSGVOAdmin'] == 'TRUE'){echo 'checked';} ?> />DSGVO
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canEditClients" <?php if($row['canEditClients'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_EDIT_CLIENTS']; ?>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canEditSuppliers" <?php if($row['canEditSuppliers'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_EDIT_SUPPLIERS']; ?>
                                </label>
                            </div>
                        </div>
                        <br><h4><?php echo $lang['USER_MODULES']; ?></h4>
                        <div class="row checkbox">
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canStamp" <?php if($row['canStamp'] == 'TRUE'){echo 'checked';} ?>><?php echo $lang['CAN_CHECKIN']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canBook" <?php if($row['canBook'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_BOOK']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canEditTemplates" <?php if($row['canEditTemplates'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_EDIT_TEMPLATES']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseSocialMedia" <?php if($row['canUseSocialMedia'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_USE_SOCIAL_MEDIA']; ?>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canCreateTasks" <?php if($row['canCreateTasks'] == 'TRUE'){echo 'checked';} ?>/><?php echo $lang['CAN_CREATE_TASKS']; ?>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseArchive" <?php if($row['canUseArchive'] == 'TRUE'){echo 'checked';} ?>/><?php echo $lang['CAN_USE_ARCHIVE']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseClients" <?php if($row['canUseClients'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_USE_CLIENTS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseSuppliers" <?php if($row['canUseSuppliers'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_USE_SUPPLIERS']; ?>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseWorkflow" <?php if($row['canUseWorkflow'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_USE_WORKFLOW']; ?>
                                </label>
                            </div>
							<div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canSendToExtern" <?php if($row['canSendToExtern'] == 'TRUE'){echo 'checked';} ?> />Kann persönliche Nachrichten nach Extern senden
                                </label>
                            </div>
                        </div>
						<br><h4>Keys</h4>
                        <div class="row checkbox">
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="hasTaskAccess" <?php if(in_array('TASK', $hasAccessTo)){echo 'checked';} //TODO: optimize performance ?>>Tasks
                                </label><br>
                            </div>
							<div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="hasChatAccess" <?php if(in_array('CHAT', $hasAccessTo)){echo 'checked';} ?>>Messenger
                                </label><br>
                            </div>
						</div>
                        <br>
                        <?php if(has_permission("WRITE","CORE","SECURITY")): ?>
                        <div class="row">
                            <div class="col-sm-2 col-sm-offset-10 text-right">
                                <button type="submit" name="saveRoles" value="<?php echo $x; ?>" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div><br>
    <?php endwhile; ?>
</div>
<script>
$("[data-read]").change(function(){
    $($(this).data("read")).prop("checked", true);
})
$("[data-write]").change(function(){
    $($(this).data("write")).prop("checked", false);
})
</script>