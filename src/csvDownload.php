<?php
$result = rawurldecode($_GET['csv']);

header('Content-Type: application/csv');
header("Content-Disposition: attachment; filename='export.csv'");
header("Content-Length: " . strlen($result));
echo $result;
exit;
?>

<script type='text/javascript'>
window.close();
</script>
