<?php 
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "misc" . DIRECTORY_SEPARATOR . "helpcenter.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "gpg.php";

?>

<div class="">
    <div class="page-header clearfix">
        <h4 style="display:inline-block;" class="pull-left">GPG &nbsp;</h4>
            <?php GPGMixins::show_new_keypair_button() ?>
            <span class="pull-left">&nbsp;</span>
            <?php GPGMixins::show_encrypt_decrypt_modal() ?>        
    </div>
</div>
<div class="">
    <?php GPGMixins::show_public_key_list("SELECT userID, companyID, teamID, public_key, fingerprint, private_key FROM gpg_keys") ?>
</div>
    <?php include dirname(__DIR__) . '/footer.php'; ?>