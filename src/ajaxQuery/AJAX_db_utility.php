<?php

require dirname(__DIR__) . "/connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['function'] === "forcePwdChange") {
        $id = intval($_POST['userid']);
        try {
            $conn->query("UPDATE UserData SET forcedPwdChange = 1 WHERE id=$id");
            if ($conn->error) {
                echo $conn->error;
            }
        } catch (Exception $e) {
            echo "\n" . $e;
        }
    }
    if ($_POST['function'] === "addPosition") {
        $name = $_POST['name'];
        try {
            $conn->query("INSERT INTO position (name) VALUES ('$name')");
            if ($conn->error) {
                echo $conn->error;
            } else {
                $id = $conn->insert_id;
                $data = ['id' => $id, 'name' => $name];
                echo json_encode($data);
            }
        } catch (Exception $e) {
            echo "\n" . $e;
        }
    }
    if ($_POST['function'] === "changeReview") {
        $id = $_POST['projectid'];
        $needsReview = $_POST['needsReview'];
        try {
            $conn->query("UPDATE dynamicprojects SET needsreview = '$needsReview' WHERE projectid = '$id'");
            if ($conn->error) {
                echo $conn->error;
            }
        } catch (Exception $e) {
            echo "\n" . $e;
        }
    }
}
?>