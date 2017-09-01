<?php
require_once __DIR__ . "/utilities.php";

//test if password has changed
if(isset($_SESSION["userid"])){
    $masterpw = $conn->query("SELECT masterPassword FROM configurationdata")->fetch_array()[0];
    $shouldLogOut = false;
    if(strlen($masterpw) != 0){
        $sessionmasterpw = $_SESSION['masterpassword'] ?? false;
        if($sessionmasterpw){
            if(crypt($sessionmasterpw,$masterpw) != $masterpw){
                $shouldLogOut = true;
            }
        }else{
            $shouldLogOut = true;        
        }
    }else{
        if($_SESSION["masterpassword"] ?? false){
            $shouldLogOut = true;
        }
    }
    if($shouldLogOut){
        session_unset();
        session_destroy();
        redirect('../login/auth');
        die();
    }
    unset($masterpw,$shouldLogOut,$sessionmasterpw);
}
/**
 * MasterCrypt uses the master password to encrypt, decrypt and reencrypt strings. A return value of MasterCrypt can be used to chain calls.$_COOKIE
 */
class MasterCrypt{
    var $iv; //string(108)
    var $iv2; //string(16)
    var $password;
    var $old; //MasterCrypt
    /**
     * Creates a new MasterCrypt object. It uses $_SESSION["masterpassword"] for the password. If $iv and $iv2 are omitted, new values are generated.
     *
     * @param string|boolean $iv
     * @param string|boolean $iv2
     */
    function __construct($iv = false,$iv2 = false){
        $this->password = $_SESSION["masterpassword"] ?? false;
        $this->old = false;
        if($iv && $iv2){
            $this->iv = $iv;
            $this->iv2 = $iv2;
        }else{
            $this->regenerateIVandIV2();
        }
    }
    /**
     * Generates new Values for $iv and $iv2 based on the current password (not the session variable)
     *
     * @return MasterCrypt
     */
    function regenerateIVandIV2(){
        $iv = openssl_random_pseudo_bytes(32);
        $iv = bin2hex($iv);
        $iv2 = openssl_random_pseudo_bytes(8);
        $iv2 = bin2hex($iv2);
        $iv = openssl_encrypt($iv, 'aes-256-cbc', $this->password, 0, $iv2);            
        $this->iv = $iv;
        $this->iv2 = $iv2;
        return $this;
    }
    /**
     * If a password is set, it encrypts $unencrypted
     *
     * @param string $unencrypted
     * @return string
     */
    function encrypt(string $unencrypted){
        if($this->password){
            $iv = openssl_decrypt($this->iv, 'aes-256-cbc', $this->password, 0, $this->iv2);
            $encrypted = mc_encrypt($unencrypted, $iv);
            return $encrypted;
        }else{
            return $unencrypted;
        }
    }
    /**
     * If a password is set, it decrypts $encrypted
     *
     * @param string $encrypted
     * @return string
     */
    function decrypt($encrypted){
        if($this->password){
            $iv = openssl_decrypt($this->iv, 'aes-256-cbc', $this->password, 0, $this->iv2);
            return mc_decrypt($encrypted, $iv);
        }else{
            return $encrypted;
        }
    }
    /**
     * Generates a new Password, iv and iv2 and uses $old to reencrypt old values in MasterCrypt::change()
     *
     * @param MasterCrypt $old
     * @param string $newpassword
     * @return MasterCrypt
     */
    function from(MasterCrypt $old,string $newpassword){
        $this->old = $old;
        $this->password = $newpassword;
        $this->regenerateIVandIV2();
        return $this;
    }
    /**
     * Change uses $old to reencrypt values with another password, iv and iv2
     *
     * @param string $encrypted
     * @return string
     */
    function change(string $encrypted){
        if($this->old){
            $decrypted = $this->old->decrypt($encrypted);
            return $this->encrypt($decrypted);
        }else{
            throw new Exception("No old MasterCrypt set");
        }
    }
}
/**
 * mc is a shortcut for new MasterCrypt
 *
 * @param string|boolean $iv
 * @param string|boolean $iv2
 * @return MasterCrypt
 */
function mc($iv = false, $iv2 = false){
    return new MasterCrypt($iv,$iv2);
}


//Examples
        // Encrypt
        // $c = mc();
        // $encrypted = $c->encrypt("this is a test");
        // $encrypted2 = $c->encrypt("this is a test");
        // INSERT INTO table1 (var1,var2,iv,iv2) VALUES ('$encrypted','$encrypted2','$iv','$iv2');

        // Decrypt
        // SELECT var1,var2,iv,iv2 FROM table1;
        // $c = mc($iv,$iv2);
        // echo $c->decrypt($var1);
        // echo $c->decrypt($var2);

        // Password Change
        // $_SESSION["masterpassword"] = "test password";
        // $iv = "zNO5qG8wmDyPBx+mFl8qIfpogFJejvsvd7WN4QeVT7tognIMCK4YIva+QZ1y3XMVv3qxhNwTNb9fNhp74HTz4SIQGAriZA0IniH4xRJu4zo=";
        // $iv2 = "27a119dc5cd9e338";
        // $encrypted = "3qozv1QeuwsN8WhvfedJf9DhPmrsb2e4aUwp7BfQRad/LDsY9/nn1WbIfvfTBmnd0l5Ut+tcDe/3aagy5JdJfB1r1Ep4WpHgP2hvxE/ob9oyD5vGYzjp/9VUWGLPmLWj|ESDuK9Jk9CPS2QjQ6SRE7y7C9UVe7hxPgVAoqN58HLM=";
        // $old = mc($iv,$iv2);
        // $new = mc()->from($old,"new password");
        // $newencrypted = $new->change($encrypted);
        // INSERT INTO table1 (var1,iv,iv2) VALUES ('$newencrypted','$iv','$iv2');
/**
 * Returns rows affected by master password change
 *
 * @return int
 */
function mc_total_row_count(){
    require __DIR__."/connection.php";
    $total_count = 0;
    $total_count += $conn->query("SELECT * FROM articles")->num_rows ?? 0;
    $total_count += $conn->query("SELECT * FROM products")->num_rows ?? 0;
    return $total_count;
}
/**
 * Changes all values in all tables to use the new master password. If newpassword is false, don't apply encryption. Does not return to caller.
 *
 * @param string|boolean $newpassword
 * @return void
 */
function mc_master_password_changed(string $newpassword){
    require __DIR__."/connection.php";
    set_time_limit("600"); // max 10 minutes before error
    $logFile = fopen("./cryptlog.txt","a");
    $text = $newpassword ? ($_SESSION["masterpassword"] ? "changed": "added") :"removed";
    fwrite($logFile, "\r\n".date("y-m-d h:i:s").": Master password $text\r\n");
    $result = $conn->query("SELECT * FROM articles,products");
    //articles table
    $result = $conn->query("SELECT * FROM articles");
    fwrite($logFile, "\t".date("y-m-d h:i:s").": altering articles\r\n");
    while($row = $result->fetch_assoc()){
        $old = mc($row["iv"],$row["iv2"]);
        $new = mc()->from($old,$newpassword);
        $iv = $new->iv;
        $iv2 = $new->iv2;
        $name = $new->change($row["name"]);
        $desc = $new->change($row["description"]);
        $id = $row["id"];
        if(!$conn->query("UPDATE articles SET name = '$name', description = '$desc', iv = '$iv', iv2 = '$iv2' WHERE id = $id")){
            $error = $conn->error;
            fwrite($logFile, "\t\t".date("y-m-d h:i:s").": Error in row with id $id: $error\r\n");
        }
    }
    //products table
    fwrite($logFile, "\t".date("y-m-d h:i:s").": altering products\r\n");
    $result = $conn->query("SELECT * FROM products");
    while($row = $result->fetch_assoc()){
        $old = mc($row["iv"],$row["iv2"]);
        $new = mc()->from($old,$newpassword);
        $iv = $new->iv;
        $iv2 = $new->iv2;
        $name = $new->change($row["name"]);
        $desc = $new->change($row["description"]);
        $id = $row["id"];
        if(!$conn->query("UPDATE products SET name = '$name', description = '$desc', iv = '$iv', iv2 = '$iv2' WHERE id = $id")){
            $error = $conn->error;
            fwrite($logFile, "\t\t".date("y-m-d h:i:s").": Error in row with id $id: $error\r\n");
        }
    }
    fwrite($logFile,date("y-m-d h:i:s").": Finished\r\n");
    fwrite($logFile,date("y-m-d h:i:s").": ".mc_total_row_count()." rows affected\r\n");
    fclose($logFile);
    redirect("../system/cryptlog");
}