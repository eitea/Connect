<?php

$company_id_to_name = [];
$result_company_id_to_name = $conn->query("SELECT id, name FROM $companyTable WHERE id IN (" . implode(', ', $available_companies) . ")");
while ($result_company_id_to_name && $row_company_id_to_name = $result_company_id_to_name->fetch_assoc()) {
    $company_id_to_name[$row_company_id_to_name["id"]] = $row_company_id_to_name["name"];
}

/**
 * It's easier to not have special cases for company children.
 * This function adds companies to the current menu item's children
 * which have company children as normal children.
 */
function transform_company_children_to_real_children(&$options)
{
    global $available_companies;
    global $company_id_to_name;
    if (isset($options["company_children"]) || isset($options["company_children_callback"])) {
        if (!isset($options["children"])) $options["children"] = []; // existing children can stay (e.g. for different companies with shared logs)
        foreach ($available_companies as $cmpID) {
            if (isset($company_id_to_name[$cmpID])) {
                $cmpName = $company_id_to_name[$cmpID];
                if (isset($options["company_children_callback"])) {
                    $options["company_children"] = $options["company_children_callback"](["id" => $cmpID, "name" => $cmpName]); // let the callback overwrite company_children for every company
                }
                if (count($options["company_children"]) == 1) {
                    // if there is only one child, generate
                    // parent
                    // |-company1
                    //  \company1
                    reset($options["company_children"]); // set pointer to first (=only) element
                    $company_child_name = key($options["company_children"]);
                    $child_with_query_parameters = $options["company_children"][$company_child_name];
                    if (!isset($child_with_query_parameters["url"]))
                        $child_with_query_parameters["url"] = $child_with_query_parameters["href"];
                    $href = ((strpos($child_with_query_parameters["href"], "?") === false) ? "?" : "&") . "cmp=$cmpID";
                    $child_with_query_parameters["href"] .= $href;
                    $child_with_query_parameters["get_params"]["cmp"] = $cmpID;
                    $options["children"][$cmpName] = $child_with_query_parameters;
                } else {
                    // if there are many children, generate
                    // parent
                    // |-company1
                    // |  |-company_child1
                    // |   \company_child2
                    //  \company2
                    //    |-company_child1
                    //     \company_child2
                    $children_with_query_parameters = [];
                    foreach ($options["company_children"] as $company_child_name => $company_child) {
                        $children_with_query_parameters[$company_child_name] = $company_child;
                        if (!isset($children_with_query_parameters[$company_child_name]["url"]))
                            $children_with_query_parameters[$company_child_name]["url"] = $children_with_query_parameters[$company_child_name]["href"]; // copy href before ?cmp=... is added
                        $href = ((strpos($children_with_query_parameters[$company_child_name]["href"], "?") === false) ? "?" : "&") . "cmp=$cmpID";
                        $children_with_query_parameters[$company_child_name]["href"] .= $href; // the href can change
                        $children_with_query_parameters[$company_child_name]["get_params"]["cmp"] = $cmpID; // for only setting one item active
                    }
                    $options["children"][$cmpName] = ["children" => $children_with_query_parameters];
                }
            }
        }
    }
}

/**
 * Tests if the current menu item should get the active-link class and be expanded
 */
function set_menu_item_active($options, &$is_active) : string
{
    global $this_url;
    global $this_page;
    $is_active = false;
    if ((isset($options["active_files"]) && in_array($this_page, $options["active_files"])) || (isset($options["url"]) && $this_url == $options["url"]) || (isset($options["active_routes"]) && in_array($this_url, $options["active_routes"]))) {
        if (isset($options["get_params"])) { // every get param has to match in order for the item to be active
            $is_active = true;
            foreach ($options["get_params"] as $param_name => $param_value) {
                if (isset($_GET[$param_name])) {
                    if ($_GET[$param_name] != $param_value) {
                        $is_active = false;
                    }
                } else {
                    $is_active = false;
                }
            }
        } else {
            $is_active = true;
        }
    }
    if ($is_active) {
        return 'class="active-link"';
    }
    return '';
}

/**
 * Returns href if the menu item should have one
 */
function set_menu_item_href($options) : string
{
    if (isset($options["href"]) && !(isset($options["disabled"]) && $options["disabled"]))
        return 'href="../' . $options["href"] . '"';
    return '';
}

/**
 * Returns an icon if the menu item should have one
 */
function set_menu_item_icon($options) : string
{
    if (isset($options["icon"]))
        return '<i class="' . $options["icon"] . ' pull-left"></i>';
    if (isset($options["icon_raw"]))
        return $options["icon_raw"];
    return '';
}

/**
 * returns a badge if the menu item should have one
 */
function set_menu_item_badge($options) : string
{
    if (isset($options["badge"])) {
        $count = $options["badge"]["count"];
        if ($count === 0 || $count === "0") $count = "";
        $id = "";
        if (isset($options["badge"]["id"])) {
            $id = 'id="' . $options["badge"]["id"] . '"';
        }
        return '<span class="pull-right"><small ' . $id . '>' . $count . '</small></span>';
    }
    return '';
}

/**
 * adds data-toggle if the menu item is a parent
 */
function set_menu_item_collapse($options, $hash, $parent_hash) : string
{
    if (isset($options["children"]))
        // return 'data-toggle="collapse" data-parent="#sidebar-accordion"  href="#header-collapse-' . $hash . '"';
    return 'data-toggle="collapse" data-parent="#header-collapse-parent-' . $parent_hash . '"  href="#header-collapse-' . $hash . '"';
    return '';
}

function set_menu_item_style($options)
{
    if (isset($options["disabled"]) && $options["disabled"])
        return 'style="user-select:none;cursor:not-allowed;"';
    return 'style="user-select:none;cursor:pointer;"';
}

/**
 * Creates a menu item
 */
function create_menu_item($options, $title, $depth, &$is_active, $parent_hash, &$is_visible) : string
{
    global $routes;
    global $lang;
    $output = "";
    // eg. url: dsgvo/logs, href: dsgvo/logs?cmp=1
    if (isset($options["href"]) && !isset($options["url"])) {
        $options["url"] = $options["href"] . ""; // companies have additional parameters for href (doing this for every item is easier)
    }
    $is_visible = true;
    if (isset($options["show"]) && !$options["show"]) {
        $is_visible = false;
        return "";
    }
    if (isset($options["url"]) && isset($routes[$options["url"]]) && !has_permission_for_route($routes[$options["url"]])) {
        // tell the parent that you have permission (no child has permission => hide parent)
        $is_visible = false;
        return "";
    }
    transform_company_children_to_real_children($options);
    $hash_input = $title . $parent_hash; // make sure the hash is unique and always the same for a given item
    if (isset($options["get_params"])) $hash_input .= http_build_query($options["get_params"]); // same items with different get parameters likely need a different hash (especially companies)
    $menu_item_hash = hash("md5", $hash_input); // the hash for data-toggle
    $output .= '<li ' . set_menu_item_collapse($options, $menu_item_hash, $parent_hash) . '>';
    $output .= '<a ' . set_menu_item_active($options, $is_active) . ' ' . set_menu_item_href($options) . ' ' . set_menu_item_style($options) . '>';
    $output .= set_menu_item_badge($options);
    $output .= set_menu_item_icon($options);
    if (isset($options["children"])) {
        $output .= '<i class="fa fa-caret-down pull-right"></i>';
    }
    if (isset($lang[$title])) {
        $output .= '<span>' . $lang[$title] . '</span>';
    } else {
        $output .= '<span>' . $title . '</span>';
    }
    $output .= '</a>';
    $output .= '</li>';
    if (isset($options["children"])) {
        $output .= create_menu($options, $depth, $menu_item_hash, $any_sub_menu_item_active, $any_sub_menu_item_visible);
        if ($any_sub_menu_item_active) {
            $is_active = true;
        }
        $is_visible = $any_sub_menu_item_visible; // the parent is only visible if it has children
    }
    if(isset($options["disabled"]) && $options["disabled"] && $is_visible){
        // the item is visible, but the parent should not render if every child is disabled
        $is_visible = false;
        return $output;
    }
    if ($is_visible) return $output;
    return "";
}

/**
 * Creates a list of menu items
 * @param array $options only requires a "children" key, which is
 * an array (key = name of menu item (will be taken from $lang if available))
 * of children. Each child may have "href", "icon", "icon_raw", "children",
 * "company_children", "show", "get_params" and "badge".
 */
function create_menu($options = [], $depth = 0, $parent_hash = "nohash", &$any_item_active = false, &$any_item_visible = false)
{
    $any_item_active = false;
    $any_item_visible = false;
    $children_html = '';
    foreach ($options["children"] as $title => $child) {
        $children_html .= create_menu_item($child, $title, $depth + 1, $current_item_active, $parent_hash, $current_item_visible);
        if ($current_item_active) {
            $any_item_active = true;
        }
        if ($current_item_visible) {
            $any_item_visible = true;
        }
    }
    if ($depth == 0) {
        return $children_html; // the root doesn't need to be wrapped in collapse
    }
    // wrap children in collapse and add 'in' if any child is active
    $output = '';
    $collapse_in = $any_item_active ? "in" : ""; // instead of calling ("#parent id").click()
    // $output .= '<div id="header-collapse-parent-' . $parent_hash . '" class="panel-group" >';
    $output .= '<div id="header-collapse-' . $parent_hash . '" role="tabpanel" class="panel-collapse collapse ' . $collapse_in . '"><div class="panel-body" style="padding-top:0;padding-right:0;"><ul style="margin-right:0" class="nav navbar-nav">';
    $output .= $children_html;
    $output .= '</div></div>';
    // $output .= "</div>";
    return $output;
}
