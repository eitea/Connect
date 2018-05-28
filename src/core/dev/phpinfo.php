<?php
$hash = '$2y$10$JbfxbTGZznvFEGzBmqa27ON.XkL9Z3.zXtwkxmfcsSFu5CF1TdbSO';
if(isset($_POST['phpinfo']) && crypt($_POST['phpinfo'], $hash) == $hash){
    phpinfo();
} elseif(isset($_POST['extensions']) && crypt($_POST['extensions'], $hash) == $hash){
	echo '<pre>'.print_r(get_loaded_extensions(), 1).'</pre>';
} elseif(isset($_POST['variables']) && crypt($_POST['variables'], $hash) == $hash){
	echo '<pre>'.print_r(get_defined_vars(), 1).'</pre>';
} else {
    echo '<form method="POST"><input type="password" name="phpinfo" placeholder="info"></form>';
	echo '<form method="POST"><input type="password" name="extensions" placeholder="extensions"></form>';
	echo '<form method="POST"><input type="password" name="variables" placeholder="vars"></form>';
}
?>
