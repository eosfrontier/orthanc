<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
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