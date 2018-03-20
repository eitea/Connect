<?php
if(isset($_POST['password']) && crypt($_POST['password'], '$2y$10$JbfxbTGZznvFEGzBmqa27ON.XkL9Z3.zXtwkxmfcsSFu5CF1TdbSO') == '$2y$10$JbfxbTGZznvFEGzBmqa27ON.XkL9Z3.zXtwkxmfcsSFu5CF1TdbSO'){
    phpinfo();
} else {
    echo '<form method="POST"><input type="password" name="password"></form>';
}
?>
