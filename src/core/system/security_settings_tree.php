<?php

$result = $conn->query("SELECT groups.name group_name, perm.name permission_name, groups.id group_id, perm.id permission_id FROM access_permission_groups groups INNER JOIN access_permissions perm ON groups.id = perm.groupID");
$permission_name_to_ids = [];
while($result && $row = $result->fetch_assoc()){
    $permission_name_to_ids[$row["group_name"]][$row["permission_name"]] = ["group_id"=>$row["group_id"], "permission_id" => $row["permission_id"]];
}

$collapse_counter = 0;

function create_collapse_tree($permission_groups, $name, $x, $children_disabled = false, $mode = "USER"){
    global $collapse_counter;
    global $permission_name_to_ids;
    global $grantable_modules;
    $collapse_counter ++;
    $id = "permissionCollapseListGroup$collapse_counter";
    $child_groups = "";
    $children = "";
    $toolbar_expand = $toolbar = "";
    if($name == "DSGVO" && /*array_key_exists('DSGVO', $encrypted_modules)&&*/ !array_key_exists('DSGVO', $grantable_modules)){ 
        $children_disabled = true;
    } else if($name == "ERP" && /*array_key_exists('ERP', $encrypted_modules)&&*/ !array_key_exists('ERP', $grantable_modules)){
        $children_disabled = true;
    } 
    foreach ($permission_groups as $key => $value) {
        if(is_array($value)){
            $child_groups .= create_collapse_tree($value, $key, $x, $children_disabled, $mode);
            $toolbar_expand = "<a title='Alles ausklappen' data-toggle='tooltip' data-expand-all='#$id' role='button' style='float:right'> <i class='fa fa-fw fa-expand'></i> </a>"; // only show expand if item has children that can be expanded
        }else{
            if($mode == "USER"?Permissions::has_user("$name.$value",$x):Permissions::has_team("$name.$value",$x)){
                $checked = "checked";
            }else{
                $checked = "";
            }
            $note = "";
            if($mode == "USER" && !Permissions::has_user("$name.$value",$x) && Permissions::has("$name.$value",$x)){
                $note = "<i class='fa fa-check-square-o'></i> (Von Team geerbt)";
            }
            $group_id = $permission_name_to_ids[$name][$value]["group_id"];
            $permission_id = $permission_name_to_ids[$name][$value]["permission_id"];
            $checkbox_name="PERMISSION;$group_id;$permission_id";
            if(($x == 1 && $mode == "USER") || $children_disabled){
                $disabled = "disabled";
            }else{
                $disabled = "";
            }
            $children .= "<li class='list-group-item'><input data-permission-name='$value' $checked $disabled name='$checkbox_name' value='TRUE' type='checkbox'>$value $note</li>";
        }
    }
    $toolbar .= " <a title='Alles aktivieren' data-toggle='tooltip' data-check-all='#$id' role='button' style='float:right'> <i class='fa fa-fw fa-check-square-o'></i> </a>";             
    $toolbar .= " <a title='Alles deaktivieren' data-toggle='tooltip' data-uncheck-all='#$id' role='button' style='float:right'> <i class='fa fa-fw fa-square-o'></i> </a>";             
   return "
    <div class='panel-group' role='tablist' style='margin:0'> 
        <div class='panel panel-default'> 
            <div class='panel-heading' role='tab'> 
                <h4 class='panel-title'> <a href='#$id' class='' role='button' data-toggle='collapse'> $name </a> $toolbar $toolbar_expand </h4> 
                
            </div> 
            <div class='panel-collapse collapse' role='tabpanel' id='$id' style='margin-left: 20px'> 
                <ul class='list-group'> 
                    $children
                </ul>
                <div >
                    $child_groups 
                </div>
            </div> 
        </div> 
    </div>";
}

?>
<h4>Benutzer Verwaltung</h4><br>


<div class="container-fluid panel-group" id="accordion">
    <?php
    $result = $conn->query("SELECT id FROM UserData");
    while ($result && ($row = $result->fetch_assoc())):
        $x = $row['id'];
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
                        <h4>Berechtigungen </h4>
                            <div class="col-xs-12">
                                <label>
                                    <input type="checkbox" name="inherit_team_permissions" <?php if($x == 1){echo 'disabled';} ?> <?php if(Permissions::user_inherits_team_permissions($x)){echo 'checked';} ?>>Berechtigungen von Team erben
                                </label><br>
                            </div>
                            <br />
                            <br />
                            <?php 
                                echo create_collapse_tree(Permissions::$permission_groups, "PERMISSIONS", $x);
                            ?>
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
                        <div class="row">
                            <div class="col-sm-2 col-sm-offset-10 text-right">
                                <button type="submit" name="saveRoles" value="<?php echo $x; ?>" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div><br>
    <?php endwhile; ?>
</div>

<h4>Team Verwaltung</h4><br>


<div class="container-fluid panel-group" id="accordion">
    <?php

    $result = $conn->query("SELECT id teamID, name, isDepartment FROM teamData");
    while ($result && ($row = $result->fetch_assoc())):
        $x = $row['teamID'];
        $name = $row["name"];
        $isDepartment = $row["isDepartment"] == "TRUE";
        ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="teamHeading<?php echo $x; ?>">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#teamCollapse<?php echo $x; ?>"><?php echo $name ?> </a> <?php if($isDepartment) echo '<small style="padding-left:35px;color:green;">Abteilung</small>'; ?>
                </h4>
            </div>
            <div id="teamCollapse<?php echo $x; ?>" class="panel-collapse collapse <?php if($x == $activeTab) echo 'in'; ?>">
                <div class="panel-body">
                    <form method="POST">
                        <div class="row">
                        <?php 
                            foreach ($relationship_team_user[$x] as $uid) {
                                echo "<div class='col-md-6 col-xs-12' >";
                                echo $userID_toName[$uid];
                                echo "</div>";
                            }
                            ?>
                        </div>
                        <?php
                        echo create_collapse_tree(Permissions::$permission_groups, "PERMISSIONS", $x, false, "TEAM");
                        ?>
                        <div class="row">
                            <div class="col-sm-2 col-sm-offset-10 text-right">
                                <button type="submit" name="saveTeamRoles" value="<?php echo $x; ?>" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div><br>
    <?php endwhile; ?>
</div>

<script>
$("[data-permission-name]").change(function(){
    var currentPermissionName = $(this).data("permission-name");
    var checked = $(this).prop("checked");
    $(this).closest(".list-group").find("[data-permission-name]").each(function(idx,elem){
        var otherPermissionName = $(elem).data("permission-name");
        if(checked){
            if(currentPermissionName == "ADMIN"){
                $(this).prop("checked",true);
            }
            if(currentPermissionName == "WRITE" && otherPermissionName == "READ"){
                $(this).prop("checked",true);
            }
            if(currentPermissionName == "BOOK" && otherPermissionName == "STAMP"){
                $(this).prop("checked",true);                
            }
        }else{
            if(currentPermissionName == "READ" && (otherPermissionName == "ADMIN" || otherPermissionName == 'WRITE')){
                $(this).prop("checked",false);
            }
            if(currentPermissionName == "WRITE" && otherPermissionName == "ADMIN"){
                $(this).prop("checked",false);
            }
            if(currentPermissionName == "USE" && otherPermissionName == "ADMIN"){
                $(this).prop("checked", false);
            }
            if(currentPermissionName == "STAMP" && otherPermissionName == "BOOK"){
                $(this).prop("checked", false);                
            }
        }
    });
    // $($(this).data("write")).prop("checked", false);
})

$('[href*="permissionCollapseListGroup"]').click(function(){
    $(this).closest(".panel-group").parent().find('[id*="permissionCollapseListGroup"]').not(this).collapse("hide") // closes all sibling collapses 
})
$('[data-expand-all*="permissionCollapseListGroup"]').click(function(){
    $(this).closest(".panel-group").find('[id*="permissionCollapseListGroup"]').not(this).collapse("show") // closes all sibling collapses 
})
$('[data-check-all*="permissionCollapseListGroup"]').click(function(){
    $(this).closest(".panel-group").find("[data-permission-name]").prop("checked", true);
})
$('[data-uncheck-all*="permissionCollapseListGroup"]').click(function(){
    $(this).closest(".panel-group").find("[data-permission-name]").prop("checked", false);
})
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>
