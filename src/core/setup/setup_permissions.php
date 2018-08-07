<?php

/**
 * Updates all permissions to match the tree above ($permission_groups).
 *
 * @param null|array $subtree Only needed for recursion
 * @return void
 */
function setup_permissions($subtree = null)
{
    global $conn;
    if($subtree == null){
        $subtree = Permissions::$permission_groups;
    }
    
    $stmt_insert_groups = $conn->prepare("INSERT INTO access_permission_groups (name) VALUES (?)");
    echo $conn->error;
    $stmt_insert_groups->bind_param("s", $group_name);

    $stmt_insert_permission = $conn->prepare("INSERT INTO access_permissions (groupID, name) VALUES (?, ?)");
    echo $conn->error;
    $stmt_insert_permission->bind_param("is", $group_id, $permission_name);
    
    $stmt_select_group = $conn->prepare("SELECT id from access_permission_groups where name = ?");
    echo $conn->error;
    $stmt_select_group->bind_param("s",$group_name);

    $stmt_select_permission = $conn->prepare("SELECT id FROM access_permissions WHERE groupID = ? AND name = ?");
    echo $conn->error;
    $stmt_select_permission->bind_param("is",$group_id,$permission_name);

    foreach ($subtree as $group_name => $permissions) {
        $stmt_select_group->execute();
        echo $stmt_select_group->error;
        $result_group = $stmt_select_group->get_result();
        if($result_group && ($row_group = $result_group->fetch_assoc())){
            $group_id = $row_group["id"];
            $result_group->free();
        }else{
            // don't create the group if it doesn't have any permissions
            // if a group doesn't have any permissions, it's just used for display
            $has_child_permissions = false;
            foreach ($permissions as $permission_key => $permission_name) {
                if(!is_string($permission_key)){
                    $has_child_permissions = true;
                }
            }
            if($has_child_permissions){
                $stmt_insert_groups->execute();
                echo $stmt_insert_groups->error;
                $group_id = $stmt_insert_groups->insert_id;
            }
        }
        foreach ($permissions as $permission_key => $permission_name) {
            if(is_string($permission_key)){
                setup_permissions([$permission_key => $permission_name]);
            }else{
                $stmt_select_permission->execute();
                echo $stmt_select_permission->error;
                $result_permission = $stmt_select_permission->get_result();
                if($result_permission && ($row_permission = $result_permission->fetch_assoc())){
                    continue;
                }
                $stmt_insert_permission->execute();
                echo $stmt_insert_permission->error;
            }
        }
    }
    $stmt_insert_groups->close();
    $stmt_insert_permission->close();
}