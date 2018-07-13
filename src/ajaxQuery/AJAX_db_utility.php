<?php
session_start();
require dirname(__DIR__)."/connection.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
	//TODO: remove these reading operations from Ajax ASAP
    if($_POST['function']==="forcePwdChange"){
        $id = intval($_POST['userid']);
        try{
            $conn->query("UPDATE UserData SET forcedPwdChange = 1 WHERE id=$id");
            if($conn->error){
                echo $conn->error;
            }
        }catch(Exception $e){
            echo "\n" . $e;
        }
    }
    if($_POST['function']==="changeReview"){
        $id = $_POST['projectid'];
        $needsReview = $_POST['needsReview'];
        try{
            $conn->query("UPDATE dynamicprojects SET needsreview = '$needsReview' WHERE projectid = '$id'");
            if($conn->error){
                echo $conn->error;
            }
        }catch(Exception $e){
            echo "\n" . $e;
        }
    }

	if($_POST['function'] == 'getUnreadMessages'){
		$id = intval($_POST['userid']);
		$result = $conn->query("SELECT COUNT(*) AS unreadMessages FROM messenger_messages m
		INNER JOIN relationship_conversation_participant rcp ON (rcp.id = m.participantID AND (partType != 'USER' OR partID != '$id'))
		WHERE m.sentTime >= (SELECT lastCheck FROM relationship_conversation_participant rcp2 WHERE rcp2.conversationID = rcp.conversationID
		AND rcp2.status != 'exited' AND rcp2.partType = 'USER' AND rcp2.partID = '$id')"); echo $conn->error;
		if($result && ($row = $result->fetch_assoc()) && $row['unreadMessages'] > 0){
			echo $row['unreadMessages'];
		}
    }
    
    if($_POST['function'] == 'isSessionAlive'){
        if(!$_SESSION || !isset($_SESSION['userid'])){
            echo "false";
        }else{
            echo "true";
        }
    }
}
?>
