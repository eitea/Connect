<?php
//schedule all tasks (this page will be called every minute)
include 'connection.php';

/*
1. grab last runtime of tasks
2. grab schedule of task
3. check if task is supposed to run now (time from last runtime and now amounts to schedule)
4. execute task
5. update last runtime
*/

 ?>
