<?php

require dirname(__DIR__) . "/connection.php";

$companyID = intval($_POST['companyID']);
$userID = intval($_POST['userID']);


echo "<option value=0> User ... </option>";
$query = "SELECT $companyToUserRelationshipTable.*,firstname,lastname
FROM $companyToUserRelationshipTable INNER JOIN $userTable ON $companyToUserRelationshipTable.userID = $userTable.id
WHERE companyID = $companyID";

$result = mysqli_query($conn, $query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clientName = $row['firstname'] . ' ' . $row['lastname'];
        $selected = "";
        if ($userID == $row['userID']) {
            $selected = "selected";
        }
        echo "<option $selected name='act' value=" . $row['userID'] . ">$clientName</option>";
    }
}
?>
