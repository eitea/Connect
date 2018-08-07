<?php
session_start();
require dirname(__DIR__)."/connection.php";

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
			$id = intval($_POST['userid']);
	        $result = $conn->query("SELECT COUNT(*) AS unreadMessages FROM messenger_messages m
			INNER JOIN relationship_conversation_participant rcp ON (rcp.id = m.participantID AND (partType != 'USER' OR partID != '$id'))
			WHERE m.sentTime >= (SELECT lastCheck FROM relationship_conversation_participant rcp2 WHERE rcp2.conversationID = rcp.conversationID
			AND rcp2.status != 'exited' AND rcp2.partType = 'USER' AND rcp2.partID = '$id')");
	        echo $conn->error;
	        if ($result && ($row = $result->fetch_assoc()) && $row['unreadMessages'] > 0) {
	            echo $row['unreadMessages'];
	        }
			break;

		case 'isSessionAlive':
			if (!isset($_SESSION['userid'])) {
	            echo "false";
	        } else {
	            echo "true";
	        }
			break;

		case 'getNextMessage':
			include dirname(__DIR__).'/utilities.php';
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
			while($result && ($row_cw = $result->fetch_assoc())){
				if($row_cw['partType'] == 'USER' && $row_cw['partID'] != $userID){
					echo '<div style="display:table;width:100%;">';
					echo '<p style="font-size:75%;">', substr(carryOverAdder_Hours($row_cw['sentTime'], $timeToUTC),11,5), '</p>';
					echo '<div class="well" style="width:70%;margin-bottom:10px;float:left;" >';
					if($row_cw['type'] == 'text') echo asymmetric_encryption('CHAT', $row_cw['message'], $userID, $privateKey, $row_cw['vKey']);
					if($row_cw['type'] == 'file' && $row_cw['fileName']) {
						echo '<form method="POST" action="../project/detailDownload" target="_blank">
						<input type="hidden" name="keyReference" value="CHAT_',$row_cw['messageID'],'" />
						<button type="submit" class="btn btn-link" name="download-file" value="',$row_cw['message'],'"><i class="fa fa-file-text-o"></i> ',$row_cw['fileName'],'.',$row_cw['fileType'],'</form>';
					} elseif($row_cw['type'] == 'file'){
						$conn->query("DELETE FROM messenger_messages WHERE id = ".$row_cw['messageID']); //remove this after the update.
					}
					echo '</div>';
					echo '</div>';
				}
			}
			$conn->query("UPDATE relationship_conversation_participant SET lastCheck = UTC_TIMESTAMP
				WHERE conversationID = $conversationID AND partType = 'USER' AND partID = $userID");
			break;

	}

}
