<?php
session_start();
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "validate.php";
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "gpg.php";

$userID = $_SESSION["userid"] or die("not logged in");

$result = $conn->query("SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while ($result && ($row = $result->fetch_assoc())) {
    $available_companies[] = $row['companyID'];
}

$uid = $tid = $cid = false;
if (isset($_POST["user"])) $uid = intval($_POST["user"]);
if (isset($_POST["team"])) $tid = intval($_POST["team"]);
if (isset($_POST["company"])) $cid = intval($_POST["company"]);

$user_query = $team_query = $company_query = "AND 1 = 2";
$is_single_target = $name_email = $name_real = $name_comment = false;
if ($uid && ($uid == $userID || Permissions::has("GPG.ADMIN") || Permissions::has("CORE.USERS"))) {
    $user_query = "AND id = $uid";
    $is_single_target = true;
    $result = $conn->query("SELECT email, firstname, lastname FROM UserData where id = $uid");
    echo $conn->error;
    $row = $result->fetch_assoc();
    if ($row["firstname"] || $row["lastname"]) $name_real = $row["firstname"] . " " . $row["lastname"];
    if ($row["email"]) $name_email = $row["email"];
}
if ($tid && (Permissions::has("GPG.ADMIN") || Permissions::has("CORE.TEAMS"))) {
    $team_query = "AND id = $tid";
    $is_single_target = true;
    $result = $conn->query("SELECT email, emailName, name FROM teamData where id = $tid");
    echo $conn->error;
    $row = $result->fetch_assoc();
    if ($row["emailName"]) $name_real = $row["emailName"];
    elseif ($row["name"]) $name_real = $row["name"];
    if ($row["email"]) $name_email = $row["email"];
}
if ($cid && (Permissions::has("GPG.ADMIN") || Permissions::has("CORE.COMPANIES"))) {
    $company_query = "AND id = $cid";
    $is_single_target = true;
    $result = $conn->query("SELECT mail, name, cmpDescription FROM companyData where id = $cid");
    echo $conn->error;
    $row = $result->fetch_assoc();
    if ($row["cmpDescription"]) $name_real = $row["cmpDescription"];
    elseif ($row["name"]) $name_real = $row["name"];
    if ($row["mail"]) $name_email = $row["mail"];
}
if (!$uid && !$tid && !$cid && (Permissions::has("GPG.ADMIN"))) {
    $user_query = $team_query = $company_query = "";
}
$modal_id = str_replace(["/", "+"], "", base64_encode(random_bytes(24)));
?>
        <form method="post">
        <div class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">GPG</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="operation" value="new" id="gpg-operation<?php echo $modal_id ?>" />
                        <ul class="nav nav-tabs nav-justified">
                            <li class="active">
                                <a href="#gpg-tab-new<?php echo $modal_id ?>" data-toggle="tab" onclick="$('#gpg-operation<?php echo $modal_id ?>').val('new')">Neues Paar erstellen</a>
                            </li>
                            <li>
                                <a href="#gpg-tab-import<?php echo $modal_id ?>" data-toggle="tab" onclick="$('#gpg-operation<?php echo $modal_id ?>').val('import')">Importieren</a>
                            </li>
                        </ul>

                        <label>User/Team/Mandant</label>
                        <select class="select2-team-icons form-control" <?php echo $is_single_target ? 'readonly' : '' ?> name="userOrTeamOrCompany">
                            <?php
                            echo GPGMixins::get_select_options(
                                "SELECT ud.id, ud.firstname, ud.lastname FROM UserData ud WHERE NOT EXISTS (SELECT userID FROM gpg_keys WHERE userID = ud.id) $user_query",
                                "SELECT td.id, td.name, td.isDepartment FROM teamData td WHERE NOT EXISTS (SELECT teamID FROM gpg_keys WHERE teamID = td.id) $team_query",
                                "SELECT id, name FROM companyData cd WHERE id IN (" . implode(', ', $available_companies) . ") AND NOT EXISTS (SELECT companyID FROM gpg_keys WHERE companyID = cd.id) $company_query"
                            );
                            ?>
                        </select>
                        <br>
                        <br>

                        <div class="tab-content">
                            <div class="tab-pane fade in active" id="gpg-tab-new<?php echo $modal_id ?>">
                                <label>RSA Schlüssellänge</label>
                                <select class="form-control" name="keyLength">
                                    <option value="1024">1024 Bit (schnell, nicht empfohlen)</option>
                                    <option value="2048" selected>2048 Bit</option>
                                    <option value="4096">4096 Bit (empfohlen)</option>
                                </select>
                                <br />
                                <div class="well">
                                    <small>Alle, mit denen Sie den Schlüssel teilen, können diese Felder sehen. Mindestens eines dieser Felder muss ausgefüllt sein.</small>
                                    <br />
                                    <label>Name</label>
                                    <input class="form-control" type="text" name="nameReal" <?php echo $name_real ? "readonly" : "" ?> value="<?php echo $name_real ?>" />
                                    <label>Kommentar</label>
                                    <input class="form-control" type="text" name="nameComment" <?php echo $name_comment ? "readonly" : "" ?> value="<?php echo $name_comment ?>" />
                                    <label>Email</label>
                                    <input class="form-control" type="email" name="nameEmail" <?php echo $name_email ? "readonly" : "" ?> value="<?php echo $name_email ?>" />
                                </div>
                            </div>
                            <div class="tab-pane fade" id="gpg-tab-import<?php echo $modal_id ?>">
                                <label>Privater oder öffentlicher Schlüssel</label>
                                <textarea style='resize: none' class="form-control monospace-font" name="keyImport" rows="20" placeholder="-----BEGIN PGP PRIVATE KEY BLOCK-----

lQHYBFt2jCMBBACqNKBRI/lMszIrUl96HwvkG
ldnE5rsrwP2Cj5UwGRvCDIvzWgKea0Bhc25f5
LOz5HuYgZk8oF7Y5R1yeeiwjp4JNe/xww88eK
PvCec2I7Z2O51DtdcaMpvRdWgKea0Bhc24/ct
X3g1gnMCANIMO97R2j1kLTCG8TNaLZQw8mGgb
vxiWgKea0Bhc2mVxaSMLL81n24AMOLf4ImGJe
Dt40fuwdXRBPY1oDOqslIMB/0OSYFoOOdb1k/
H5WpZQ3ZqMvWWgKea0Bhc2NqUF/rkKxnJlPXj
hSeYJqlSk6cYZFW7ZoxutwZUxAlxeMkVcqgu+
DofTZPTWgKea0Bhc2egTrQrYXNskZmtsPojOz
a2RmamwgKGFzZGtsZmpsYXNramYpIDxhc2xma
mxhc8FoNWgKea0Bhc2pBBWgKea0Bhc2MBCAA4

-----END PGP PRIVATE KEY BLOCK-----"></textarea>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo $lang['CANCEL']; ?>
                        </button>
                        <button type="submit" class="btn btn-warning" name="createKeypair" value="true">
                            <?php echo $lang["SAVE"] ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>