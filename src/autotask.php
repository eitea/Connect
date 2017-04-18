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

$result = $conn->query("SELECT * FROM $taskTable WHERE repeatPattern != '-1'"); //0. grab all active tasks
while($result && ($row = $result->fetch_assoc())){
  $task_id = $row['id'];
  $pattern = $row['repeatPattern']; //1. grab schedule of task
  $lastRuntime =  $row['lastRuntime']; //2. grab last runtime of tasks
  $runtime = $row['runtime'];
  echo "<br>Checking for task $task_id:";
  //3. check if task is supposed to run now
  $now = getCurrentTimestamp();
  if(timeDiff_Hours($now, $runtime) < 0 && timeDiff_Hours($now, $lastRuntime) < 0){
    echo "<br> Times have passed, checking for schedule";
    if($pattern === '0'){
      include $row['callee'];
      $conn->query("UPDATE $taskTable SET lastRuntime = UTC_TIMESTAMP, repeatPattern = '-1' WHERE id = $task_id");
    } elseif($pattern === '1') {
      $expiryDate = strtotime($lastRuntime . ' + 1 day');
    } elseif($pattern === '2') {
      $expiryDate = strtotime($lastRuntime . ' + 1 week');
    } elseif($pattern === '3') {
      $expiryDate = strtotime($lastRuntime . ' + 1 month');
    } elseif($pattern === '4') {
      $expiryDate = strtotime($lastRuntime . ' + 1 year');
    }

    if($pattern && timeDiff_Hours($now, $expiryDate) < 0){ //schedule has also expired
      //4. execute task
      include $row['callee'];
      //5. update last runtime
      $conn->query("UPDATE $taskTable SET lastRuntime = UTC_TIMESTAMP WHERE id = $task_id");
    }
  }
}
