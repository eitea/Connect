<?php
session_start();
require dirname(__DIR__)."/connection.php";
require dirname(__DIR__)."/utilities.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //TODO: remove these write operations from Ajax ASAP
	switch($_POST['function']){
		case 'forcePwdChange':
			$id = intval($_POST['userid']);
	        try {
	            $conn->query("UPDATE UserData SET forcedPwdChange = 1 WHERE id=$id");
	            if ($conn->error) {
	                echo $conn->error;
	            }
	        } catch (Exception $e) {
	            echo "\n" . $e;
	        }
			break;
		case 'changeReview':
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
			break;
		case 'getUnreadMessages':
			$userID = $_SESSION['userid'];
			$unread_messages = getUnreadMessages(); 
			if($unread_messages != 0)
				echo $unread_messages;
			break;

		case 'isSessionAlive':
			if (!isset($_SESSION['userid'])) {
	            echo "false";
	        } else {
	            echo "true";
	        }
			break;

		case 'getNextMessage':
			$conversationID = intval($_POST['conversationID']);
			$userID = $_SESSION['userid'];
			$timeToUTC = $_SESSION['timeToUTC'];
			$privateKey = $_SESSION['privateKey'];
			$result = $conn->query("SELECT * FROM (SELECT message, vKey, sentTime, m.type, rcp.partType, rcp.partID, rcp.status, rcp.lastCheck,
				archive.name AS fileName, archive.type AS fileType, m.id AS messageID
				FROM messenger_messages m
				INNER JOIN relationship_conversation_participant rcp ON rcp.id = m.participantID AND rcp.conversationID = $conversationID
				LEFT JOIN archive ON m.message = archive.uniqID
				WHERE m.sentTime > (SELECT lastCheck FROM relationship_conversation_participant rcp2 WHERE rcp2.conversationID = rcp.conversationID
				AND rcp2.status != 'exited' AND rcp2.partType = 'USER' AND rcp2.partID = '$userID')
				ORDER BY m.sentTime DESC) AS tbl ORDER BY tbl.sentTime ASC");
				echo $conn->error;
			$lastmessage = $response = '';
			while($result && ($row_cw = $result->fetch_assoc())){
				if($row_cw['partType'] == 'USER' && $row_cw['partID'] != $userID){
					$lastmessage = $row_cw['messageID'];
					$response .= '<div style="display:table;width:100%;">';
					$response .= '<p style="font-size:75%;">'. substr(carryOverAdder_Hours($row_cw['sentTime'], $timeToUTC),11,5). '</p>';
					$response .= '<div class="well" style="width:70%;margin-bottom:10px;float:left;" >';
					if($row_cw['type'] == 'text') $response .= asymmetric_encryption('CHAT', $row_cw['message'], $userID, $privateKey, $row_cw['vKey']);
					if($row_cw['type'] == 'file' && $row_cw['fileName']) {
						$response .= '<form method="POST" action="../project/detailDownload" target="_blank">
						<input type="hidden" name="keyReference" value="CHAT_'.$row_cw['messageID'].'" />
						<button type="submit" class="btn btn-link" name="download-file" value="'.$row_cw['message'].'">
						<i class="fa fa-file-text-o"></i> '.$row_cw['fileName'].'.'.$row_cw['fileType'].'</form>';
					}
					$response .= '</div></div>';
				}
			}
			// 5b6aa7855a90e - this shall not mark things as "read" but if this is not set, it will fetch the same message over and over again
			// $conn->query("UPDATE relationship_conversation_participant SET lastCheck = UTC_TIMESTAMP
			// 	WHERE conversationID = $conversationID AND partType = 'USER' AND partID = $userID");
			if($lastmessage) echo $lastmessage,'#DIVIDE#',$response;
			break;
	}

}
