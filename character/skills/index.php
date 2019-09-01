<?php 
header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}"); 
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
$post = json_decode(file_get_contents('php://input'), true);

include_once "../../includes/include.php";

include_once "../../includes/token.php";

$cCharacter = new character();

if(empty($post["id"])){
    //HAVEN'T ANSWERED A WAY TO ACCESS
    http_response_code(400);
    echo json_encode("You haven't included a 'id'.");
    die();
}

$id = $post["id"];

$aSkills = $cCharacter->getSkills($id);

http_response_code(200);
echo json_encode($aSkills);
die();

?>