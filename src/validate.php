<?php


/**
 * Usage:
 * Permissions::has("CORE"); // any core permission
 * Permissions::has("WRITE:CORE"); // any write core permission
 * Permissions::has("WRITE:CORE.SETTINGS"); // CORE.SETTINGS permission
 */
class Permissions
{
  /**
   * Those are all permissions. If setup_permissions is called, missing permissions will be written to the database.
   * IMPORTANT: Only leaves and their direct parents are saved (eg GENERAL>PROFILE>READ => PROFILE>READ)
   * @var array
   */
  public static $permission_groups = [
    'GENERAL' => [
      'STAMP',
      'BOOK',
      'REQUEST',
      'SOCIAL' => [
        'PROFILE',
      ],
      'POST' => [
        'READ',
        'WRITE',
        'EXTERN_PERSONAL',
        'EXTERN_TEAM',
        'EXTERN_COMPANY'
      ],
    ],
    'CORE' => [
      'SECURITY',
      'USERS',
      'COMPANIES',
      'TEAMS',
      'SETTINGS',
      'TEMPLATES',
	  'CLIENTS' => [
		  'READ',
		  'WRITE'
	  ],
	  'SUPPLIERS' => [
		  'READ',
		  'WRITE'
	  ]
    ],
    'TIMES' => [
      'READ',
      'WRITE'
    ],
    'PROJECTS' => [
      'USE',
      'ADMIN',
      'LOGS',
      'TASKS' => [
        'ADMIN',
        'WRITE',
        'READ'
      ],
      'WORKFLOW' => [
        'READ',
        'WRITE'
      ],
    ],
    'ERP' => [
      'PROCESS',
      'ARTICLE',
      'RECEIPT_BOOK',
      'VACANT_POSITIONS',
      'SETTINGS'
    ],
    'FINANCES' => [
      'ACCOUNTING_PLAN',
      'ACCOUNTING_JOURNAL',
      'TAX_RATES'
    ],
    'DSGVO' => [
      'EMAIL_TEMPLATES',
      'LOGS',
      'AGREEMENTS' => [
        'READ',
        'WRITE'
      ],
      'PROCEDURE_DIRECTORY' => [
        'READ',
        'WRITE'
      ],
      'TRAINING' => [
        'READ',
        'WRITE'
      ],
      'GPG' => [
        'USE',
        'ADMIN'
      ]
    ],
    'ARCHIVE' => [
      'SHARE',
      'PRIVATE'
    ],
  ];

  protected static $permission_cache = null;
  protected static $permission_cache_team = null;
  protected static $permission_cache_default = null;

  protected static $relationship_user_team = null;
  protected static $inherit_team_permissions = null;

  /**
   * Tests if a user has a permission (either directly or inherited from team)
   *
   * @param string $permission
   * @param int|false $userID User ID; uses $_SESSION if false
   * @return boolean
   */
  public static function has($permission, $userID = false) : bool
  {
    if (!$userID) {
      if (isset($_SESSION['userid'])) {
        $userID = $_SESSION['userid'];
      } else {
        return false;
      }
    }
    if (intval($userID) == 1) {
      return true; // admin
    }
    $has = self::has_user($permission, $userID);
    if (self::$relationship_user_team == null || self::$inherit_team_permissions == null) {
      self::update_cache_relationship_user_team();
    }
    if (isset(self::$relationship_user_team[$userID])) {
      if (self::user_inherits_team_permissions($userID)) {
        foreach (self::$relationship_user_team[$userID] as $teamID => $value) {
          if (self::has_team($permission, $teamID)) {
            $has = true;
          }
        }
      }
    }
    return $has;
  }

  /**
   * Tests if a user has permission inheritance enabled
   *
   * @param int $userID
   * @return boolean
   */
  public static function user_inherits_team_permissions($userID) : bool
  {
    if (intval($userID) === 1) return true;
    if (self::$inherit_team_permissions == null) {
      self::update_cache_relationship_user_team();
    }
    if (isset(self::$inherit_team_permissions[$userID])) {
      return self::$inherit_team_permissions[$userID];
    }
    return false;
  }

  /**
   * Tests if the user has the permission
   * @param string $name A permission string eg "WRITE:CORE.SETTINGS" or "CORE" (=any "READ:CORE")
   * @param int|false $userID User ID; uses $_SESSION if false
   */
  public static function has_user($permission, $userID = false) : bool
  {
    if (!$userID) {
      if (isset($_SESSION['userid'])) {
        $userID = $_SESSION['userid'];
      } else {
        return false;
      }
    }
    if (intval($userID) == 1) {
      return true; // admin
    }
    $parsed = self::parse($permission);
    if (isset(self::$permission_cache[$parsed["group"]][$parsed["permission"]][$userID])) {
      return self::$permission_cache[$parsed["group"]][$parsed["permission"]][$userID];
    }
    self::update_cache_user($userID);
    if (isset(self::$permission_cache[$parsed["group"]][$parsed["permission"]][$userID])) {
      return self::$permission_cache[$parsed["group"]][$parsed["permission"]][$userID];
    }
    echo "$permission doesn't exist";
    return false;
  }

  /**
   * Tests if the team has the permission
   * @param string $permission A permission string eg "CORE.SETTINGS"
   * @param int $teamID
   */
  public static function has_team($permission, $teamID) : bool
  {
    $parsed = self::parse($permission);
    if (isset(self::$permission_cache_team[$parsed["group"]][$parsed["permission"]][$teamID])) {
      return self::$permission_cache_team[$parsed["group"]][$parsed["permission"]][$teamID];
    }
    self::update_cache_team($teamID);
    if (isset(self::$permission_cache_team[$parsed["group"]][$parsed["permission"]][$teamID])) {
      return self::$permission_cache_team[$parsed["group"]][$parsed["permission"]][$teamID];
    }
    echo "$permission doesn't exist";
    return false;
  }

  /**
   * Tests if a permission should be applied to newly created users
   *
   * @param string $permission
   * @return boolean
   */
  public static function has_default($permission) : bool
  {
    $parsed = self::parse($permission);
    if (isset(self::$permission_cache_default[$parsed["group"]][$parsed["permission"]])) {
      return self::$permission_cache_default[$parsed["group"]][$parsed["permission"]];
    }
    self::update_cache_default();
    if (isset(self::$permission_cache_default[$parsed["group"]][$parsed["permission"]])) {
      return self::$permission_cache_default[$parsed["group"]][$parsed["permission"]];
    }
    echo "$permission doesn't exist";
    return false;
  }

  /**
   * Does nothing when user has permission. When the user doesn't have permission,
   * a message and the footer are displayed and the script execution stops
   * @param string $permission A permission string eg "CORE.SETTINGS"
   * @param int|false $userID User ID (uses $_SESSION if false)
   */
  public static function require($permission, $userID = false)
  {
    if (!self::has($permission, $userID)) {
      echo 'Access denied. <a href="../user/logout"> logout</a>';
      if (function_exists("showError")) showError("You don't have the permission \"$permission\" (<a href='#' onclick='window.history.back()'>go back</a> or <a href='../user/logout'>logout</a>)");
      if ($include_footer) include 'footer.php';
      die();
    }
  }

  /**
   * Called when Permission::has() is called and the permission isn't in the cache
   * Needs to be called after a permission change (in security settings)
   *
   * Caches every permission since create_menu tests permissions for every leaf.
   * 
   * @return void
   */
  public static function update_cache_user($userID = false)
  {
    // echo "updating cache";
    if (!$userID) {
      if (isset($_SESSION['userid'])) {
        $userID = $_SESSION['userid'];
      } else {
        return;
      }
    }
    if (intval($userID) == 1) {
      return;
    }
    self::update_cache($userID, "USER");
  }

  /**
   * Called when Permission::has() is called and the permission isn't in the cache
   * Needs to be called after a permission change (in security settings)
   *
   * Caches every permission since create_menu tests permissions for every leaf.
   * 
   * @return void
   */
  public static function update_cache_team($teamID)
  {
    self::update_cache($teamID, "TEAM");
  }

  /**
   * Updates the cache for default permissions. This will only be used in system/security
   * or when creating new users.
   *
   * @return void
   */
  public static function update_cache_default()
  {
    self::update_cache(false, "DEFAULT");
  }

  /**
   * Updates user-team relationship and inherited team permissions
   * 
   * @return void
   */
  public static function update_cache_relationship_user_team()
  {
    global $conn;
    if (!$conn) require 'connection.php';
    $result = $conn->query("SELECT teamID, userID FROM relationship_team_user");
    echo $conn->error;
    while ($result && $row = $result->fetch_assoc()) {
      self::$relationship_user_team[$row["userID"]][$row["teamID"]] = true;
    }
    $result = $conn->query("SELECT id, inherit_team_permissions FROM UserData");
    while ($result && $row = $result->fetch_assoc()) {
      self::$inherit_team_permissions[$row["id"]] = $row["inherit_team_permissions"] == 'TRUE';
    }
  }

  /**
   * Adds all default permissions to a user and optionally deletes other permissions.
   * Also updates the user cache automatically.
   *
   * @param int $userID
   * @param boolean $also_delete_previous_permissions
   * @return void
   */
  public static function apply_defaults($userID, $also_delete_previous_permissions = false)
  {
    if (!$userID) {
      echo "no user id";
      return;
    }
    if ($also_delete_previous_permissions) {
      $conn->query("DELETE FROM relationship_access_permissions WHERE userID = $userID");
      echo $conn->error;
    }
    global $conn;
    if (!$conn) require 'connection.php';
    
    // copy all default permissions without ignoring errors
    $conn->query("INSERT INTO relationship_access_permissions (userID, permissionID)
                  SELECT $userID, permissionID
                  FROM default_access_permissions
                  ON DUPLICATE KEY UPDATE relationship_access_permissions.permissionID = relationship_access_permissions.permissionID");
    echo $conn->error;
    self::update_cache_user($userID);
  }

  /**
   * Updates user or team cache
   *
   * @param int|false $id userID, teamID or neither
   * @param "USER"|"TEAM"|"DEFAULT" $mode
   * @return void
   */
  protected static function update_cache($id, $mode)
  {
    global $conn;
    if (!$conn) require 'connection.php';
    $table = $mode == "USER" ? "relationship_access_permissions" : ($mode == "TEAM" ? "relationship_team_access_permissions" : "default_access_permissions");
    $additional_query = $mode == "USER" ? "AND rel.userID = $id" : ($mode == "TEAM" ? "AND rel.teamID = $id" : "");
    $result = $conn->query("SELECT groups.name g_name, perm.name p_name, rel.permissionID perm_id
                            FROM access_permission_groups groups
                            LEFT JOIN access_permissions perm ON perm.groupID = groups.id
                            LEFT JOIN $table rel ON rel.permissionID = perm.id
                            $additional_query");
    echo $conn->error;
    while ($result && $row = $result->fetch_assoc()) {
      if ($row["perm_id"]) {
        if ($mode == "USER") {
          self::$permission_cache[$row["g_name"]][$row["p_name"]][$id] = true;
        } else if ($mode == "TEAM") {
          self::$permission_cache_team[$row["g_name"]][$row["p_name"]][$id] = true;
        } else {
          self::$permission_cache_default[$row["g_name"]][$row["p_name"]] = true;
        }
      } else {
        if ($mode == "USER") {
          self::$permission_cache[$row["g_name"]][$row["p_name"]][$id] = false;
        } else if ($mode == "TEAM") {
          self::$permission_cache_team[$row["g_name"]][$row["p_name"]][$id] = false;
        } else {
          self::$permission_cache_default[$row["g_name"]][$row["p_name"]] = false;
        }
      }
    }
  }

  /**
   * Tests if a user or a team the user is in has any permission in a group (eg DSGVO)
   */
  public static function has_any($group_name, $userID = false, $permission_groups = false, $parent_matches = false, $parent_name = "")
  {
    if (!$userID) {
      if (isset($_SESSION['userid'])) {
        $userID = $_SESSION['userid'];
      } else {
        return false;
      }
    }
    $has = false;
    if (!$permission_groups) {
      $permission_groups = self::$permission_groups;
    }
    foreach ($permission_groups as $name => $children) {
      if (is_array($children)) {
        if ($name == $group_name) {
          $parent_matches = true;
        }
        if (self::has_any($group_name, $userID, $children, $parent_matches, $name)) {
          $has = true;
        }
      } else {
        if ($parent_matches && self::has("$parent_name.$children", $userID)) {
          // echo "$parent_name.$children is true for user $userID. ";
          $has = true;
        }
      }
    }
    return $has;
  }

  /**
   * This function takes a permission string ("WRITE:CORE.SETTINGS" or "CORE.SETTINGS") and
   * returns the full permission
   */
  protected static function parse($str)
  {
    $parsed = ["group" => null, "permission" => null];

    $exploded = explode(".", $str);
    if (count($exploded) == 0) return $parsed;
    if (count($exploded) == 1) {
      $parsed["group"] = $exploded[0];
    } else {
      $parsed["group"] = $exploded[0];
      $parsed["permission"] = $exploded[1];
    }
    return $parsed;
  }
};

?>
