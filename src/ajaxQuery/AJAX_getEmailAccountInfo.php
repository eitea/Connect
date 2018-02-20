<?php

include dirname(__DIR__) . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $result = $conn->query("SELECT * FROM emailprojects WHERE id = '$id'");
        if ($result) {
            echo json_encode($result->fetch_assoc());
        }
    }
}
return;
?>