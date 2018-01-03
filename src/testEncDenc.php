<?php
  require __DIR__."/connection.php";
  require 'vendor/autoload.php';

  $publicKey =
       'mKkET3cSJAEE4MxHNHKr46q8qXbXI4xYNYHwo1sAwHIKNlRsUwgpCj6G6qyZ95DQ
       35fI0UeZBUsMKfbjz5JZSquRSpMevpdhsCKbdPbyGo11dz8jFYwQ3zTz/qFgaL0p
       UKmFf1ZzzOM1rVXXXKLsF3YCIR0QShwmbPCmbaShSd0KA+cMVkMVNPOgroBrEdVj
       HPGPx/luVBB+PIFuuUX4tvmDSqu29wARAQABtCRUZXN0IEtleSAoUlNBKSA8dGVz
       dGtleUBleGFtcGxlLm9yZz6I2gQTAQIAKAUCT3cSJAIbAwUJEOrPgAYLCQgHAwIG
       FQgCCQoLBBYCAwECHgECF4AACgkQ1NVOoW+HBA6VPQTeOPMNrd9c3y3qmvg5rh03
       AUVkhKyqO7N82syZavT6tiVgOaFe6f+IYQr68pqJHk0efp+doY6GVQIzcLig6tGw
       dWjueOG2sdhb1g9XiK6Ki2u144XGU2byQtYprEMzHN0LHO4lmqcIQwxdaCwP7p8R
       /3jxLZNmyZf3EL5JClFdlbaXmy0WbGl1Q9lhpyRgbcpsNcVlMbzOpRehse9uuKkE
       T3cSJAEE4Lqemm8jk+YgmqqDCVzwWPkiC75stgHs7jkHnZNO6/HN+XZg5njuXXCC
       QvRB84TrCbtoyf+0elO3WNdbc+/TJ63lFVMcnTR+ESMQk8Hoe+fcHVQNVmA7fpKy
       HyDkJ+n/s2+4Xfwz7FUujS21UHOKwNquD9r9PlP5fX/d4CUZTmIIeCU5bcujtxxe
       CP9sxRsRiyL5OkMOg//QKh8DPQARAQABiMEEGAECAA8FAk93EiQCGwwFCRDqz4AA
       CgkQ1NVOoW+HBA45CATdErZSCbeaXFLUXhKkAW2U+Pn4nKNOypbIDmx5B3PLAcrP
       1MlRJXj78LvUPQ7Tv2JHImyliWbVDiEloUYqlaB3lP6DsNXNjme8uMRMIyH3Xsz/
       4F8UqqXccQdsNXFXq836ZS88qwuggLQwaylz3+EUTDOiNKLKuvNt08xFeYok8J6B
       w6nRSCNUUC6eDmZcG953PZCPyAjzarq4NGFr';

  if(session_status()==PHP_SESSION_NONE){
    session_start();
    $encryptedText = '';
    $encrypted = '';
    $decrypted = '';
    $decryptedText = '';
  }
 

  

  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['encrypt']) && !empty($_POST['rawtext'])&& !empty($_POST['keyE'])){
      $key = OpenPGP_Message::parse(OpenPGP::unarmor($_POST['keyE'],"PGP PUBLIC KEY BLOCK"));

      $data = new OpenPGP_LiteralDataPacket($_POST['rawtext'],array('format' => 'u', 'filename' => 'encrypted.gpg'));

      $encrypted = OpenPGP_Crypt_Symmetric::encrypt($key, new OpenPGP_Message(array($data)));

      $enc = OpenPGP::enarmor($encrypted->to_bytes(), "PGP MESSAGE");

      $encryptedText = wordwrap($enc, 64, "\n", 1);

      
    }else if(isset($_POST['decrypt']) && !empty($_POST['encryptedtext'])&& !empty($_POST['keyD'])){

      $stillencrypted = OpenPGP_Message::parse(OpenPGP::unarmor($_POST['encryptedtext'],"PGP MESSAGE"));

      $key = OpenPGP_Message::parse(OpenPGP::unarmor($_POST['keyD'],"PGP PRIVATE KEY BLOCK"));

      $decryptor = new OpenPGP_Crypt_RSA($key);

      $decrypted = $decryptor->decrypt($stillencrypted);
      
      $plain_text='';
      if($decrypted->packets[0] instanceof OpenPGP_LiteralDataPacket){

            $plain_text = $decrypted->packets[0]->data;

            }
      }else{

            foreach($decrypted->packets[0]->data->packets  as $obj){

          if($obj instanceof OpenPGP_LiteralDataPacket){
          
            $plain_text = $obj->data;
            
            break;

          }
        }
      }

    $decryptedText = $plain_text;


      
      
    

  }



  function unwrap($text){
    $text = substr($text,strpos($text,"\n"));
    $text = substr($text,strpos($text,"\n"));
    $text = substr($text,0,strpos($text,"\n="));
    return $text;
  }

  
  
  
  
  // Store the public key in a file
  


// Encryption


// Decryption

  
?>


<!DOCTYPE html>
<html>
<head>
<meta charset="ANSI">
</head>
<body>
<div>
<p>Verschlüsseln</p>
<form method="POST">
  <input type="text" name="rawtext"></input><label for="rawtext">Text</label></br></br>
  <textarea type="text" name="keyE" rows="10" cols="30"></textarea><label>Schlüssel</label></br></br> 
  <textarea type="text" name="encrypted" rows="10" cols="30" disabled><?php echo $encryptedText ?></textarea><label for="encrypted">Verschlüsselter Text</label></br></br> 
  <input type="submit" value="Calc" name="encrypt"></input>
</form>
</div>
<div>
<p>Entschlüsseln</p>
<form method="POST">
  <textarea type="text" name="encryptedtext" rows="10" cols="30"><?php echo $encryptedText ?></textarea><label for="encryptedtext">Verschlüsselter Text</label></br></br>
  <textarea type="text" name="keyD" rows="10" cols="30"></textarea><label>Schlüssel</label></br></br> 
  <label>Text : <?php echo $decryptedText ?></label></br></br> 
  <input type="submit" value="Calc" name="decrypt"></input>
</form>
</div>
</body>
</html> 