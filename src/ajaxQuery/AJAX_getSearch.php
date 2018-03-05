<?php
session_start();
$userID = $_SESSION['userid'] or die("no user signed in");
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

if(isset($_REQUEST["modal"])){
    ?>
        <div class="modal fade">
            <div class="modal-dialog modal-content modal-md">
                <div class="modal-header">Suche</div>
                <div class="modal-body" style="height: 80vh">
                    <br>
                    <form id="searchForm">
                    <div class="input-group">
                        <input type="text" name="search" value="" class="form-control" id="searchQuery">
                        <span class="input-group-btn"><button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button></span>
                        </div>
                    </form>
                    <br>
                    <div id="searchResult"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
        <script>
            function fetchSearchResults(){
                $("#searchResult").html("<div style='width:100%;height:100%;'><div class='searchLoader'></div></div>");
                $.ajax({
                    url: 'ajaxQuery/AJAX_getSearch.php',
                    data: { query: $("#searchQuery").val() },
                    type: 'post',
                    success: function (resp) {
                        $("#searchResult").html(resp)
                    },
                    error: function (resp) { 
                        $("#searchResult").html(resp)
                    }
                });
            }
            $("#searchForm").submit(function(event){
                event.preventDefault();
                fetchSearchResults();
                return false;
            })
        </script>
    <?php
    die();
}
isset($_POST["query"]) or die("not a valid query");

$routes = array();
//ENG
$routes[] = array("name"=>"Home", "url"=>"../user/home", "tags"=>array("Overview"));
$routes[] = array("name"=>"My Times", "url"=>"../user/time", "tags"=>array("Monthly Report"));
$routes[] = array("name"=>"Request", "url"=>"../user/request");
$routes[] = array("name"=>"Book Projects", "url"=>"../user/book", "tags"=>array("Booking"));
$routes[] = array("name"=>"Suppliers", "url"=>"../erp/suppliers", "tags"=>array("Supplier List"));
$routes[] = array("name"=>"Clients", "url"=>"../erp/clients", "tags"=>array("Client List"));
$routes[] = array("name"=>"Edit Users", "url"=>"../system/users", "tags"=>array("User List", "Users"));
$routes[] = array("name"=>"User Saldo", "url"=>"../system/saldo", "tags"=>array("User List", "Users"));
$routes[] = array("name"=>"Add User", "url"=>"../system/register", "tags"=>array("New User", "Create User"));

//GER
$routes[] = array("name"=>"Übersicht", "url"=>"../user/home", "tags"=>array("Home"));
$routes[] = array("name"=>"Meine Zeiten", "url"=>"../user/time", "tags"=>array("Monatsbericht"));
$routes[] = array("name"=>"Anträge", "url"=>"../user/request");
$routes[] = array("name"=>"Projekte Buchen", "url"=>"../user/book", "tags"=>array("Buchungen"));

function test_input($data){
    $data = preg_replace("~[^A-Za-z0-9\-@.+/öäüÖÄÜß_ ]~", "", $data);
    $data = trim($data);
    return $data;
}
function formatList($list){
    $output = "";
    $output .= '<ul class="list-group">';
    foreach ($list as $idx => $item) {
        $output .= '<li class="list-group-item">';
        $name = $item["name"];
        $url = $item["url"];
        $tags = array();
        if(isset($item["tags"])){
            $tags = $item["tags"];
        }
        $output .= "<a href='$url'>$name</a>";
        foreach ($tags as $tag) {
            $output .= " <span class='label label-default pull-right' style='display:inline-block'>$tag</span> ";
        }
        $output .= '</li>';
    }
    $output .= '</ul>';
    return $output;
}
$query = strtolower(test_input($_POST["query"]));
function queryFunction($item){
    global $query;
    $name = strtolower($item["name"]);
    $url = $item["url"];
    $tags = array();
    if(isset($item["tags"])){
        $tags = $item["tags"];
    }
    if(strlen($query) > 0){
        if(strpos($name, $query) !== false){
            return true;
        }
        foreach ($tags as $tag) {
            $tag = strtolower($tag);
            if(strpos($tag, $query) !== false){
                return true;
            }
        }
        return false;
    }
    return true;
}

echo formatList(array_filter($routes,"queryFunction"));
?>