<?php
require dirname(__DIR__)."/connection.php";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        if(!empty($_POST['id'])){
            $id = intval($_POST['id']);
            $result = $conn->query("SELECT * FROM taskemailrules WHERE emailaccount = $id");
            if($result){
                $rules = array();
                $i = 0;
                while($row = $result->fetch_assoc()){
                    array_push($rules,$row);
                    $company = $conn->query("SELECT name FROM companyData WHERE id = ".$rules[$i]['company']);
                    if($conn->error){
                        $conn->query("INSERT INTO emailprojectlogs (timeofoccurence,body) VALUES (CURRENT_TIMESTAMP,$conn->error)");
                    }else{
                        $rules[$i]['company'] = $company->fetch_assoc()['name'];
                    }

                    $company = $conn->query("SELECT name FROM clientData WHERE id = ".$rules[$i]['client']);
                    if($conn->error){
                        $conn->query("INSERT INTO emailprojectlogs (timeofoccurence,body) VALUES (CURRENT_TIMESTAMP,$conn->error)");
                    }else{
                        $rules[$i]['client'] = $company->fetch_assoc()['name'];
                    }

                    $company = $conn->query("SELECT projectname FROM dynamicprojects WHERE projectid = '".$rules[$i]['parent']."'");
                    if($conn->error){
                        $conn->query("INSERT INTO emailprojectlogs (timeofoccurence,body) VALUES (CURRENT_TIMESTAMP,$conn->error)");
                    }else{
                        if($company){
                            $rules[$i]['parent'] = $company->fetch_assoc()['projectname'];
                        }
                    }

                    $company = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS name FROM UserData WHERE id = ".$rules[$i]['owner']);
                    if($conn->error){
                        $conn->query("INSERT INTO emailprojectlogs (timeofoccurence,body) VALUES (CURRENT_TIMESTAMP,$conn->error)");
                    }else{
                        $rules[$i]['owner'] = $company->fetch_assoc()['name'];
                    }

                    $company = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS name FROM UserData WHERE id = ".$rules[$i]['leader']);
                    if($conn->error){
                        $conn->query("INSERT INTO emailprojectlogs (timeofoccurence,body) VALUES (CURRENT_TIMESTAMP,$conn->error)");
                    }else{
                        $rules[$i]['leader'] = $company->fetch_assoc()['name'];
                    }

                    $employees = explode(",",$rules[$i]['employees']);
                        for($y = 0;$y<count($employees)-1;$y++){
                            if($employees[$y] = strstr($employees[$y],';')) $employees[$y] = ltrim($employees[$y],';');
                            $company = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS name FROM UserData WHERE id = ".$employees[$y]);
                            if($conn->error){
                                $conn->query("INSERT INTO emailprojectlogs (timeofoccurence,body) VALUES (CURRENT_TIMESTAMP,$conn->error)");
                            }else{
                                $employees[$y] = $company->fetch_assoc()['name'];
                            }
                        }
                 
                    $rules[$i]['employees'] = rtrim(implode(", ",$employees),", ");


                    if(!empty($rules[$i]['optionalemployees'])){
                        $employees = explode(",",$rules[$i]['optionalemployees']);
                        for($y = 0;$y<count($employees)-1;$y++){
                            if($employees[$y] = strstr($employees[$y],';')) $employees[$y] = ltrim($employees[$y],';');
                            $company = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS name FROM UserData WHERE id = ".$employees[$y]);
                            if($conn->error){
                                $conn->query("INSERT INTO emailprojectlogs (timeofoccurence,body) VALUES (CURRENT_TIMESTAMP,$conn->error)");
                            }else{
                                $employees[$y] = $company->fetch_assoc()['name'];
                            }
                        }
                        $rules[$i]['optionalemployees'] = rtrim(implode(", ",$employees),", ");
                    }
                    $i++;
                }
                
                echo json_encode($rules);
            }else{
                return;
            }
        }else{
            return;
        }
    }
    return;
?>