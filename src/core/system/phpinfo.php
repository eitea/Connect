<?php
if(isset($_POST['password']) && crypt($_POST['password'], '$2y$10$LoEtod70wr098s7gqqQ2weC3Evn3fHWI6UFZbSP21vecUIkXSnLAW') == '$2y$10$LoEtod70wr098s7gqqQ2weC3Evn3fHWI6UFZbSP21vecUIkXSnLAW'){
    phpinfo();
} else {
    echo '<form method="POST"><input type="password" name="password"></form>';
}
?>