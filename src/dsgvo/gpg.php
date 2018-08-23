<?php

class GPG
{
    private $gpg;
    private $home_dir;

    function __construct($gpg_dir = "")
    {
        if(!$gpg_dir) $gpg_dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "gpg";
        $this->set_home_dir($gpg_dir . DIRECTORY_SEPARATOR . uniqid("", true) . str_replace("/", "", base64_encode(random_bytes(24))));
        $this->gpg = new gnupg();
    }

    function __destruct()
    {
        if (!empty($this->home_dir)) {
            self::delete_files_recursive($this->home_dir);
        }
    }

    public static function delete_files_recursive($path, $depth = 0)
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException("$path must be a directory");
        }
        if (substr($path, strlen($path) - 1, 1) != '/') {
            $path .= '/';
        }
        $files = glob($path . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::delete_files_recursive($file, $depth + 1);
            } else {
                if (strpos($file, '.gitignore') === false) {
                    @unlink($file);
                }
            }
        }
        // if ($depth > 0) rmdir($path);
        rmdir($path);
    }

    function set_home_dir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        putenv("GNUPGHOME=$dir");
        $this->home_dir = $dir;
    }

    function create_key_pair($options, &$exec_output = "")
    {
        $name_real = $options["name"];
        $name_comment = $options["comment"];
        $name_email = $options["email"];
        $key_length = $options["key_length"];

        $name_rows = [];
        if ($name_real) {
            $name_rows[] = "Name-Real: $name_real";
        }
        if ($name_comment) {
            $name_rows[] = "Name-Comment: $name_comment";
        }
        if ($name_email) {
            $name_rows[] = " Name-Email: $name_email";
        }
        if (!count($name_rows)) {
            showError("Either name, comment or email has to be present");
            return ["public_key" => null, "private_key" => null, "fingerprint" => null];
        }
        $name_rows = implode("\n", $name_rows);

        $file_name = "$this->home_dir/gpg";
        $file = fopen("$file_name", "w");
        fwrite($file, "%echo Generating a basic OpenPGP key
                       %no-protection
                       Key-Type: RSA
                       Key-Length: $key_length
                       $name_rows
                       Expire-Date: 0
                       %commit
                       %echo done");
        fclose($file);
        $exec_output = shell_exec("gpg --generate-key --batch --homedir '$this->home_dir' --no-tty '$file_name' 2>&1");
        unlink("$file_name");

        // the list should only contain one fingerprint
        $fingerprint = self::parse_fingerprint(`gpg --list-secret-keys --with-colons --fingerprint`);
        $result = ["fingerprint" => $fingerprint];

        $private_key = `gpg --homedir '$this->home_dir' --armor --export-secret-keys $fingerprint`;
        $result["private_key"] = $private_key;

        $public_key = `gpg --homedir '$this->home_dir' --armor --export $fingerprint`;
        $result["public_key"] = $public_key;

        $this->show_key_info($fingerprint);

        return $result;
    }

    function get_key_pair($key)
    {
        $this->set_home_dir($this->home_dir);
        $info = $this->gpg->import($key);
        $fingerprint = $info["fingerprint"];
        $result = ["fingerprint" => $fingerprint];

        $private_key = `gpg --homedir '$this->home_dir' --armor --export-secret-keys $fingerprint`;
        $result["private_key"] = $private_key ? $private_key : null;

        $public_key = `gpg --homedir '$this->home_dir' --armor --export $fingerprint`;
        $result["public_key"] = $public_key;

        $this->show_key_info($fingerprint);

        return $result;
    }

    function get_fingerprint(string $public_key){
        $this->set_home_dir($this->home_dir);
        $info = $this->gpg->import($public_key);
        $fingerprint = $info["fingerprint"];
        return "$fingerprint";
    }

    private function show_key_info($fingerprint)
    {
        $key_info = $this->gpg->keyinfo($fingerprint);
        $output = [];
        if ($key_info) {
            foreach ($key_info[0]["uids"] as $info) {
                $output[] = $info["name"] . " (" . $info["comment"] . ") " . $info["email"];
            }
            showInfo("Key info: " . htmlspecialchars(implode(", ", $output)));
        } else {
            showWarning("Can't get key info");
        }
    }

    function encrypt($message, $encrypt_key, $sign_key = false)
    {
        $this->set_home_dir($this->home_dir);
        $this->gpg->import($encrypt_key["public_key"]);
        $success = $this->gpg->addencryptkey($encrypt_key["fingerprint"]);
        if (!$success) return $this->gpg->geterror();
        if (!$sign_key) {
            $encrypted = $this->gpg->encrypt($message);
            if (!$encrypted) return $this->gpg->geterror();
            return $encrypted;
        }
        $this->gpg->import($sign_key["private_key"]);
        $success = $this->gpg->addsignkey($sign_key["fingerprint"]);
        if (!$success) return $this->gpg->geterror();
        return $this->gpg->encryptsign($message);
    }

    function decrypt($message, $decrypt_key, $verify = false, &$fingerprint = false)
    {
        $this->set_home_dir($this->home_dir);
        $this->gpg->import($decrypt_key["private_key"]);
        $success = $this->gpg->addencryptkey($decrypt_key["fingerprint"]);
        if (!$success) return $this->gpg->geterror();
        if (!$verify) {
            $decrypted = $this->gpg->decrypt($message);
            if (!$decrypted) return $this->gpg->geterror();
            return $decrypted;
        }
        $info = $this->gpg->decryptverify($message, $decrypted);
        if (!$info) return $this->gpg->geterror();
        $fingerprint = $info[0]["fingerprint"];
        return $decrypted;
    }

    function sign($message, $sign_key)
    {
        $this->set_home_dir($this->home_dir);
        $this->gpg->import($sign_key["private_key"]);
        $success = $this->gpg->addsignkey($sign_key["fingerprint"]);
        if (!$success) return $this->gpg->geterror();
        $this->gpg->setsignmode(gnupg::SIG_MODE_CLEAR); // SIG_MODE_NORMAL, SIG_MODE_DETACH, SIG_MODE_CLEAR
        $signed = $this->gpg->sign($message);
        if (!$signed) return $this->gpg->geterror();
        return $signed;
    }

    function verify($message)
    {
        $this->set_home_dir($this->home_dir);
        $result = $this->gpg->verify($message, false);
        if ($result && isset($result[0]["fingerprint"])) return $result[0]["fingerprint"];
        return $this->gpg->geterror();
    }

    // function import_key($key, &$exec_output = "")
    // {
    //     $descriptorspec = array(
    //         0 => array("pipe", "r"),
    //         1 => array("pipe", "w"),
    //     );
    //     $env = ["GNUPGHOME" => $this->home_dir];
    //     $success = false;
    //     $process = proc_open("gpg --homedir '$this->home_dir' --import 2>&1", $descriptorspec, $pipes, $this->home_dir, $env);
    //     if (is_resource($process)) {
    //         fwrite($pipes[0], $key); 
    //         fclose($pipes[0]);

    //         $exec_output = stream_get_contents($pipes[1]);
    //         fclose($pipes[1]);

    //         $return_value = proc_close($process);
    //         $success = $return_value == 0;
    //     }
    //     return $success;
    // }

    protected static function parse_fingerprint($gpg_output)
    {
        preg_match('/fpr:+([A-Za-z0-9]+):/', $gpg_output, $match);
        if (isset($match[1]))
            return $match[1];
        showError("Can't parse fingerprint");
        return "";
    }
}

class GPGMixins
{
    /**
     * Returns the gpg keys (public and private) from the database. TODO: decrypt private key
     *
     * @param "user"|"team"|"company" $type
     * @param int $id
     * @return array
     */
    static function get_gpg_key($type, $id)
    {
        global $conn;
        if ($type == "user") {
            $result = $conn->query("SELECT public_key, private_key, fingerprint FROM gpg_keys WHERE userID = $id");
        } else if ($type == "team") {
            $result = $conn->query("SELECT public_key, private_key, fingerprint FROM gpg_keys WHERE teamID = $id");
        } else {
            $result = $conn->query("SELECT public_key, private_key, fingerprint FROM gpg_keys WHERE companyID = $id");
        }
        showError($conn->error);
        if ($result && $row = $result->fetch_assoc()) {
            return $row;
        }
        return [];
    }

    static function get_gpg_public_key($type, $id){
        global $conn;
        if ($type == "user") {
            $result = $conn->query("SELECT public_key, fingerprint FROM gpg_keys WHERE userID = $id");
        } else if ($type == "team") {
            $result = $conn->query("SELECT public_key, fingerprint FROM gpg_keys WHERE teamID = $id");
        } else {
            $result = $conn->query("SELECT public_key, fingerprint FROM gpg_keys WHERE companyID = $id");
        }
        showError($conn->error);
        if ($result && $row = $result->fetch_assoc()) {
            return $row;
        }
        return [];
    }

    static function get_has_gpg_keys_list(){
        global $conn;
        $has_gpg_keys = [];
        $result = $conn->query("SELECT private_key, public_key, userID, teamID, companyID FROM gpg_keys");
        while($result && $row = $result->fetch_assoc()){
            if($row["userID"]){
                $has_gpg_keys["user"][$row["userID"]] = ["public" => !empty($row["public_key"]), "private" => !empty($row["private_key"])];
            }
            if($row["teamID"]){
                $has_gpg_keys["team"][$row["teamID"]] = ["public" => !empty($row["public_key"]), "private" => !empty($row["private_key"])];
            }
            if($row["companyID"]){
                $has_gpg_keys["company"][$row["companyID"]] = ["public" => !empty($row["public_key"]), "private" => !empty($row["private_key"])];
            }
        }
        return $has_gpg_keys;
    }

    static function get_select_options($user_query, $team_query, $company_query)
    {
        global $conn;
        $output = "";
        // var_dump($user_query);
        $result = $conn->query($user_query);
        while ($result && ($row = $result->fetch_assoc())) {
            $output .= '<option title="Benutzer" value="user;' . $row['id'] . '" data-icon="user">' . $row['firstname'] . ' ' . $row['lastname'] . '</option>';
        }
        $result = $conn->query($team_query);
        while ($result && ($row = $result->fetch_assoc())) {
            $icon = $row["isDepartment"] === 'TRUE' ? "share-alt" : "group";
            $type = $row["isDepartment"] === 'TRUE' ? "Abteilung" : "Team";
            $output .= '<option title="' . $type . '" value="team;' . $row['id'] . '" data-icon="' . $icon . '">' . $row['name'] . '</option>';
        }
        $result = $conn->query($company_query);
        while ($row = $result->fetch_assoc()) {
            $output .= '<option title="Mandant" value="company;' . $row['id'] . '" data-icon="building">' . $row['name'] . '</option>';
        }
        return $output;
    }

    static $output_modal = "";

    static function form_handling()
    {
        global $lang;
        global $conn;
        // var_dump ($_POST);
        $gpg_dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "gpg";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST["createKeypair"])) {
                $target = explode(";", $_POST["userOrTeamOrCompany"]);
                $gpg = new GPG($gpg_dir);

                $allowed = false;
                if ($target[0] == "user") {
                    $uid = intval($target[1]);
                    if($_SESSION["userid"] == $uid || Permissions::has("GPG.ADMIN") || Permissions::has("CORE.USERS"))
                        $allowed = true;
                } else if ($target[0] == "team") {
                    $tid = intval($target[1]);
                    if(Permissions::has("GPG.ADMIN") || Permissions::has("CORE.TEAMS"))
                        $allowed = true;
                } else {
                    $cid = intval($target[1]);
                    if(Permissions::has("GPG.ADMIN") || Permissions::has("CORE.COMPANIES"))
                        $allowed = true;
                }
                if($allowed){

                    if ($_POST["operation"] == "new") {
                        $name_real = test_input($_POST["nameReal"]);
                        $name_comment = test_input($_POST["nameComment"]);
                        $name_email = test_input($_POST["nameEmail"]);
                        $key_length = intval($_POST["keyLength"]);
    
                        $key_pair = $gpg->create_key_pair([
                            "name" => $name_real,
                            "comment" => $name_comment,
                            "email" => $name_email,
                            "key_length" => $key_length
                        ]);
                    } else { // import
                        $key_pair = $gpg->get_key_pair($_POST["keyImport"]);
                    }
                    $stmt_insert = $conn->prepare("INSERT INTO gpg_keys (public_key, private_key, fingerprint, userID, companyID, teamID) VALUES (?,?,?,?,?,?)");
                    showError($conn->error);
                    $stmt_insert->bind_param("sssiii", $public_key, $private_key, $fingerprint, $uid, $cid, $tid);
                    $public_key = $key_pair["public_key"];
                    $fingerprint = $key_pair["fingerprint"];
                    $uid = $cid = $tid = null;
                    $private_key = $key_pair["private_key"]; // TODO: REMOVE
                    if ($target[0] == "user") {
                        $uid = intval($target[1]);
                        if ($key_pair["private_key"]) {
                            $result = $conn->query("SELECT publicKey FROM security_users WHERE userID = $uid");
                            if ($result && $row = $result->fetch_assoc()) {
                        // $private_key = simple_encryption($key_pair["private_key"], $row["publicKey"]); // TODO: don't use symmetric encryption
                            }
                        }
                    } else if ($target[0] == "team") {
                        $tid = intval($target[1]);
                        if ($key_pair["private_key"]) {
    
                    // TODO: encrypt
                        }
                    } else {
                        $cid = intval($target[1]);
                        if ($key_pair["private_key"]) {
    
                            $result = $conn->query("SELECT publicKey FROM security_company WHERE companyID = $cid");
                            if ($result && $row = $result->fetch_assoc()) {
                        // $private_key = simple_encryption($key_pair["private_key"], $row["publicKey"]); // TODO: don't use symmetric encryption
                            }
                        }
                    }
                    if ($public_key && $fingerprint) {
                        if (!$private_key) {
                            showWarning("No valid private key found. The key can not be used to decrypt messages.");
                        }
                        if ($cid || $tid || $uid) {
                            $stmt_insert->execute();
                            showError($stmt_insert->error);
                        } else {
                            showError("Aborting. No target selected");
                        }
                    } else {
                        showError("Aborting. No valid public key or fingerprint found.");
                    }
                }else{
                    showError("You are not allowed to create keys for this target");
                }
            } elseif (isset($_POST["encryptOrDecrypt"]) && Permissions::has("GPG.USE")) {
                $operation = $_POST["operation"];
                $message = $_POST["message"];
                $sign = $verify = $sign_key = false;
                $gpg = new GPG($gpg_dir);
                self::$output_modal = "";
                if ($operation == "encrypt") {
                    $target = explode(";", $_POST["target"]);
                    $encrypt_key = self::get_gpg_key($target[0], intval($target[1]));
                    if (!(!isset($_POST["sign"]) || $_POST["sign"] == "false")) {
                        $sign = explode(";", $_POST["sign"]);
                        $sign_key = self::get_gpg_key($sign[0], intval($sign[1]));
                    }
                    self::$output_modal = $gpg->encrypt($message, $encrypt_key, $sign_key);
                } elseif ($operation == "decrypt" && Permissions::has("GPG.USE")) {
                    $target = explode(";", $_POST["decrypt"]);
                    $verify = isset($_POST["verify"]) && $_POST["verify"] == "true";
                    $decrypt_key = self::get_gpg_key($target[0], intval($target[1]));
                    self::$output_modal = $gpg->decrypt($message, $decrypt_key, $verify, $fingerprint);
                    if ($verify && $fingerprint) {
                        $result = $conn->query("SELECT gpg.userID, gpg.teamID, gpg.companyID, ud.firstname, ud.lastname, td.name team_name, cd.name company_name FROM gpg_keys gpg 
                                        LEFT JOIN UserData ud ON ud.id = gpg.userID 
                                        LEFT JOIN teamData td ON td.id = gpg.teamID 
                                        LEFT JOIN companyData cd ON cd.id = gpg.companyID 
                                        WHERE fingerprint LIKE '%$fingerprint'");
                        if ($result && $row = $result->fetch_assoc()) {
                            if ($row["userID"]) {
                                $firstname = $row["firstname"];
                                $lastname = $row["lastname"];
                                $from = "user $firstname $lastname";
                            } elseif ($row["teamID"]) {
                                $name = $row["team_name"];
                                $from = "team $name";
                            } else {
                                $name = $row["company_name"];
                                $from = "company $name";
                            }
                            showInfo("Message came from $from (fingerprint: $fingerprint)");
                        } else {
                            showWarning("Unknown fingerprint $fingerprint");
                        }
                    }
                } elseif ($operation == "sign" && Permissions::has("GPG.USE")) {
                    $target = explode(";", $_POST["signOnly"]);
                    $sign_key = self::get_gpg_key($target[0], intval($target[1]));
                    self::$output_modal = $gpg->sign($message, $sign_key);
                } elseif ($operation == "verify" && Permissions::has("GPG.USE")) {
                    $fingerprint = $gpg->verify($message);
                    $result = $conn->query("SELECT gpg.userID, gpg.teamID, gpg.companyID, ud.firstname, ud.lastname, td.name team_name, cd.name company_name FROM gpg_keys gpg 
                                    LEFT JOIN UserData ud ON ud.id = gpg.userID 
                                    LEFT JOIN teamData td ON td.id = gpg.teamID 
                                    LEFT JOIN companyData cd ON cd.id = gpg.companyID 
                                    WHERE fingerprint LIKE '%$fingerprint'");
                    if ($result && $row = $result->fetch_assoc()) {
                        if ($row["userID"]) {
                            $firstname = $row["firstname"];
                            $lastname = $row["lastname"];
                            $from = "user $firstname $lastname";
                        } elseif ($row["teamID"]) {
                            $name = $row["team_name"];
                            $from = "team $name";
                        } else {
                            $name = $row["company_name"];
                            $from = "company $name";
                        }
                        showInfo("Message came from $from (fingerprint: $fingerprint)");
                    } else {
                        showWarning("Unknown fingerprint $fingerprint");
                    }
                    self::$output_modal = "";
                }
            }
            if (isset($_POST["deleteGpgKeys"])) {
                $allowed = false;
                $target = explode(";", $_POST["deleteGpgKeys"]);
                if ($target[0] == "user") {
                    $uid = intval($target[1]);
                    if($_SESSION["userid"] == $uid || Permissions::has("GPG.ADMIN") || Permissions::has("CORE.USERS"))
                        $allowed = true;
                } else if ($target[0] == "team") {
                    $tid = intval($target[1]);
                    if(Permissions::has("GPG.ADMIN") || Permissions::has("CORE.TEAMS"))
                        $allowed = true;
                } else {
                    $cid = intval($target[1]);
                    if(Permissions::has("GPG.ADMIN") || Permissions::has("CORE.COMPANIES"))
                        $allowed = true;
                }
                if($allowed){
                    if ($target[0] == "user") {
                        $uid = intval($target[1]);
                        $result = $conn->query("DELETE FROM gpg_keys WHERE userID = $uid");
                    } else if ($target[0] == "team") {
                        $tid = intval($target[1]);
                        $result = $conn->query("DELETE FROM gpg_keys WHERE teamID = $tid");
                    } else {
                        $cid = intval($target[1]);
                        $result = $conn->query("DELETE FROM gpg_keys WHERE companyID = $cid");
                    }
                    if($conn->error) showError($conn->error);
                    else showSuccess($lang["OK_DELETE"]);
                }else{
                    showError("You are not allowed to delete this key pair");
                }
            }
        }
    }

    static function show_public_key_list($query, $userID = false, $teamID = false, $companyID = false, $container_class = "well", $button_only = false)
    {
        global $conn;
        global $userID_toName;
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            while ($result && $row = $result->fetch_assoc()) {
                if ($row["userID"]) {
                    $icon = "<i class='fa fa-fw fa-user'></i>";
                    $title = $userID_toName[$row["userID"]];
                    $delete_key_value = "user;" . $row["userID"];
                } else if ($row["teamID"]) {
                    $icon = "<i class='fa fa-fw fa-group'></i>";
                    $title = CommonVariables::$all_team_ids_to_name[$row["teamID"]];
                    $delete_key_value = "team;" . $row["teamID"];
                } else if ($row["companyID"]) {
                    $icon = "<i class='fa fa-fw fa-building'></i>";
                    $title = CommonVariables::$all_company_ids_to_name[$row["companyID"]];
                    $delete_key_value = "company;" . $row["companyID"];
                } else {
                    continue;
                }
                $private_key_info = $row["private_key"] ? "" : "<i data-toggle='tooltip' data-container='body' title='No private key' class='fa fa-info'></i>";
                $fingerprint = $row["fingerprint"];
                $public_key = $row["public_key"];
                $collapse_id = hash("md5", $fingerprint . rand());
                $public_key_rows = substr_count($public_key, "\n") + 1;
                echo "<div class='$container_class clearfix'>";
                echo "<div class=''>";
                echo "<a data-toggle='collapse' href='#gpg-collapse-$collapse_id'>$icon $title (Fingerprint $fingerprint)</a> $private_key_info";
                echo "<button type='button' data-container='body' class='btn btn-default pull-right copy-public-key' data-id='gpg-collapse-$collapse_id'>Öffentlichen GPG Schlüssel kopieren</button>";
                echo "<form method='POST' style='display: inline-block' class='pull-right'><button type='submit' class='btn btn-default pull-right' name='deleteGpgKeys' value='$delete_key_value' onclick='return gpgDeleteConfirmation()'>GPG Schlüssel löschen</button></form>";
                echo "</div>";
                echo "<div id='gpg-collapse-$collapse_id' role='tabpanel' class='col-xs-12 panel-collapse collapse'><div class='panel-body'>";
                echo "<textarea readonly style='resize: none' rows='$public_key_rows' class='form-control monospace-font'>$public_key</textarea>";
                echo "</div></div>";
                echo "</div>";
            }
            return;
        }
        echo "<div class='$container_class clearfix'>";
        echo "<div class=''>";
        self::show_new_keypair_button($userID, $teamID, $companyID);
        echo "</div></div>";
    }

    static function show_new_keypair_button($userID = false, $teamID = false, $companyID = false)
    {
        echo "<button type='button' name='showGpgNewKeysModal' data-gpg-user='$userID' data-gpg-team='$teamID' data-gpg-company='$companyID' class='btn btn-default pull-left'>
                Neues GPG Schlüsselpaar
            </button>";
    }

    private static $modal_id = 0;

static function show_encrypt_decrypt_modal()
{
    if (!Permissions::has("GPG.USE")) return;
    global $lang;
    global $available_companies;
    self::$modal_id++;
    ?>

         <button type="button" data-toggle="modal" data-target="#encryptDecryptModal<?php echo self::$modal_id ?>" class="btn btn-default pull-left">
            GPG Operationen
        </button>

<form method="post">
        <div id="encryptDecryptModal<?php echo self::$modal_id ?>" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">GPG</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="operation" value="encrypt" id="gpg-operation-encrypt-decrypt" />
                        <ul class="nav nav-tabs nav-justified">
                            <li class="active">
                                <a href="#gpg-tab-encrypt" data-toggle="tab" onclick="$('#gpg-operation-encrypt-decrypt').val('encrypt')">Verschlüsseln</a>
                            </li>
                            <li>
                                <a href="#gpg-tab-decrypt" data-toggle="tab" onclick="$('#gpg-operation-encrypt-decrypt').val('decrypt')">Entschlüsseln</a>
                            </li>
                            <li>
                                <a href="#gpg-tab-sign" data-toggle="tab" onclick="$('#gpg-operation-encrypt-decrypt').val('sign')">Signieren</a>
                            </li>
                            <li>
                                <a href="#gpg-tab-verify" data-toggle="tab" onclick="$('#gpg-operation-encrypt-decrypt').val('verify')">Verifizieren</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade in active" id="gpg-tab-encrypt">
                                <label>Empfänger (nur dieser kann die Nachricht entschlüsseln)</label>
                                <select class="select2-team-icons form-control" name="target">
                                    <?php
                                    echo self::get_select_options(
                                        "SELECT ud.id, ud.firstname, ud.lastname FROM UserData ud WHERE EXISTS (SELECT userID FROM gpg_keys WHERE userID = ud.id)",
                                        "SELECT td.id, td.name, td.isDepartment FROM teamData td WHERE EXISTS (SELECT teamID FROM gpg_keys WHERE teamID = td.id)",
                                        "SELECT id, name FROM companyData cd WHERE id IN (" . implode(', ', $available_companies) . ") AND EXISTS (SELECT companyID FROM gpg_keys WHERE companyID = cd.id)"
                                    );
                                    ?>
                                </select>

                                <label>Signieren (optional)</label>
                                <select class="select2-team-icons form-control" name="sign">
                                    <option value="false" data-icon="times">Ohne</option>
                                    <?php
                                    echo self::get_select_options(
                                        "SELECT ud.id, ud.firstname, ud.lastname FROM UserData ud WHERE EXISTS (SELECT userID FROM gpg_keys WHERE userID = ud.id AND private_key IS NOT NULL)",
                                        "SELECT td.id, td.name, td.isDepartment FROM teamData td WHERE EXISTS (SELECT teamID FROM gpg_keys WHERE teamID = td.id AND private_key IS NOT NULL)",
                                        "SELECT id, name FROM companyData cd WHERE id IN (" . implode(', ', $available_companies) . ") AND EXISTS (SELECT companyID FROM gpg_keys WHERE companyID = cd.id AND private_key IS NOT NULL)"
                                    ); // TODO: only show options that the user can decrypt
                                    ?>
                                </select>
                                <br />
                                <br />

                            </div>
                            <div class="tab-pane fade" id="gpg-tab-decrypt">
                                <label>Empfänger</label>
                                <select class="select2-team-icons form-control" name="decrypt">
                                    <?php
                                    echo self::get_select_options(
                                        "SELECT ud.id, ud.firstname, ud.lastname FROM UserData ud WHERE EXISTS (SELECT userID FROM gpg_keys WHERE userID = ud.id AND private_key IS NOT NULL)",
                                        "SELECT td.id, td.name, td.isDepartment FROM teamData td WHERE EXISTS (SELECT teamID FROM gpg_keys WHERE teamID = td.id AND private_key IS NOT NULL)",
                                        "SELECT id, name FROM companyData cd WHERE id IN (" . implode(', ', $available_companies) . ") AND EXISTS (SELECT companyID FROM gpg_keys WHERE companyID = cd.id AND private_key IS NOT NULL)"
                                    ); // TODO: only show options that the user can decrypt
                                    ?>
                                </select>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="verify" value="true"> Signatur verifizieren
                                    </label>
                                </div>

                            </div>
                            <div class="tab-pane fade" id="gpg-tab-sign">
                                <label>Sender</label>
                                <select class="select2-team-icons form-control" name="signOnly">
                                    <?php
                                    echo self::get_select_options(
                                        "SELECT ud.id, ud.firstname, ud.lastname FROM UserData ud WHERE EXISTS (SELECT userID FROM gpg_keys WHERE userID = ud.id AND private_key IS NOT NULL)",
                                        "SELECT td.id, td.name, td.isDepartment FROM teamData td WHERE EXISTS (SELECT teamID FROM gpg_keys WHERE teamID = td.id AND private_key IS NOT NULL)",
                                        "SELECT id, name FROM companyData cd WHERE id IN (" . implode(', ', $available_companies) . ") AND EXISTS (SELECT companyID FROM gpg_keys WHERE companyID = cd.id AND private_key IS NOT NULL)"
                                    ); // TODO: only show options that the user can decrypt
                                    ?>
                                </select>
                                <br />
                                <br />
                            </div>
                            <div class="tab-pane fade" id="gpg-tab-verify">
                            </div>
                        </div>
                        <label>Nachricht</label>
                        <textarea style='resize: none' class="form-control monospace-font" name="message" rows="20" placeholder=""></textarea>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo $lang['CANCEL']; ?>
                        </button>
                        <button type="submit" class="btn btn-warning" name="encryptOrDecrypt" value="true">
                            <?php echo $lang["SAVE"] ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
        <?php

    }

    static function show_output_modal()
    {
        global $lang;
        if (self::$output_modal) { ?>
            <form method="post">
                <div id="outputModal" class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-md" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Ergebnis</h4>
                            </div>
                            <div class="modal-body">
                            <textarea id="gpg-modal-output" readonly style='resize: none' rows='<?php echo substr_count(self::$output_modal, "\n") + 1 ?>' class='form-control monospace-font'><?php echo self::$output_modal ?></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">
                                    <?php echo $lang['CLOSE']; ?>
                                </button>
                                <button class='btn btn-primary' type="button" id="copy-gpg-output">Kopieren</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <script>$('#outputModal').modal('show');</script>
        <?php 
    }
    ?>
    <div id="currentGpgModal"></div>
    <?php

}
static function show_scripts()
{
    ?>
        <script>
            function formatState(state) {
                if (!state.id) { return state.text; }
                var $state = $(
                    '<span><i class="fa fa-fw fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
                );
                return $state;
            };
            function onGpgModalLoad(){
                $(".select2-team-icons").select2({
                    templateResult: formatState,
                    templateSelection: formatState
                });
                $('[data-toggle="tooltip"]').tooltip();
            }
            $(document).ready(function(){
                $(".copy-public-key").click(function () {
                    var textArea = $("#" + $(this).data("id") + " textarea")[0];
                    $(textArea).parent().parent().collapse("show");

                    textArea.select();
                    document.execCommand("copy");
                    $(".copy-public-key").addClass("btn-default").removeClass("btn-success");
                    $(this).addClass("btn-success").removeClass("btn-default");
                    $this = $(this);
                    setTimeout(function () {
                        $this.addClass("btn-default").removeClass("btn-success");
                    }, 3000);
                })
                $("#copy-gpg-output").click(function () {
                    var textArea = $("textarea#gpg-modal-output")[0];
                    textArea.select();
                    document.execCommand("copy");
                    $this = $(this);
                    $this.addClass("btn-success").removeClass("btn-primary");
                    setTimeout(function () {
                        $this.addClass("btn-primary").removeClass("btn-success");
                    }, 3000);
                })
                onGpgModalLoad()
            })

        function gpgDeleteConfirmation(){
            if(prompt("Type 'delete gpg keys forever' if you are sure") === "delete gpg keys forever"){
                return true;
            }
            return false;
        }
        function setCurrentGpgModal(data, type, url, complete) {
            $.ajax({
                url: url,
                data: data,
                type: type,
                success: function (resp) {
                    $("#currentGpgModal").html(resp);
                },
                error: function (resp) { console.error(resp) },
                complete: function (resp) {
                    if (complete) complete(resp);
                    else $("#currentGpgModal .modal").modal('show');
                    onGpgModalLoad();
                }
            });
        }
        $("button[name=showGpgNewKeysModal]").click(function () {
            setCurrentGpgModal({
                user: $(this).data("gpg-user") || undefined,
                team: $(this).data("gpg-team") || undefined,
                company: $(this).data("gpg-company") || undefined,
            },
                'post',
                'ajaxQuery/ajax_dsgvo_gpg_new_keys_modal.php'
            )
        })
        </script><?php

            }

        }