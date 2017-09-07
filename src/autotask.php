<?php
//schedule all tasks (this page designed to be called about every minute)
require 'connection.php';
require 'createTimestamps.php';

/* repeat pattern identifiers
'-1' => 'Inactive',
'0' => 'Once',
'1' => 'Daily',
'2' => 'Weekly',
'3' => 'Monthly',
'4' => 'Yearly'
*/

$result = $conn->query("SELECT * FROM $taskTable WHERE repeatPattern != '-1'"); //grab all active tasks
while($result && ($row = $result->fetch_assoc())){
  $task_id = $row['id'];
  $pattern = $row['repeatPattern']; //1. grab schedule of task
  $lastRuntime = $row['lastRuntime']; //2. grab last runtime of tasks
  $runtime = $row['runtime'];
  //3. check if task is supposed to run now
  $now = getCurrentTimestamp();
  if(timeDiff_Hours($now, $runtime) < 0 && timeDiff_Hours($now, $lastRuntime) < 0){
    $expiryDate = new DateTime($lastRuntime);
    if($pattern === '0'){
      include $row['callee'];
      $conn->query("UPDATE $taskTable SET lastRuntime = UTC_TIMESTAMP, repeatPattern = '-1' WHERE id = $task_id");
    } elseif($pattern === '1') {
      $expiryDate->add(new DateInterval('P1D'));
    } elseif($pattern === '2') {
      $expiryDate->add(new DateInterval('P7D'));
    } elseif($pattern === '3') {
      $expiryDate->add(new DateInterval('P1M'));
    } elseif($pattern === '4') {
      $expiryDate->add(new DateInterval('P12M'));
    }

    $expiryDate = $expiryDate->format('Y-m-d H:i:s');
    if($pattern && timeDiff_Hours($now, $expiryDate) < 0){ //execute if schedule has expired
      //4. execute task
      require $row['callee'];
      //5. update last runtime
      if($task_id != 3){
        $conn->query("UPDATE $taskTable SET lastRuntime = UTC_TIMESTAMP WHERE id = $task_id");
      }
    }
  }
}
