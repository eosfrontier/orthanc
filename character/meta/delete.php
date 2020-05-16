<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$input = json_decode(file_get_contents('php://input'), true);

include_once "../../includes/include.php";
include_once "../../includes/token.php";

$cMeta = new meta();

if(empty($input["id"]) || empty($input["meta"])){
    //HAVEN'T ANSWERED A WAY TO ACCESS
    http_response_code(400);
    echo json_encode("You haven't included a 'id'.");
    die();
}

$id     = $input["id"];
$meta   = $input["meta"];

$aResult = $cMeta->deleteMeta($id, $meta);

if(!empty($aResult)){
    http_response_code(200);
    echo json_encode($aResult);
}else{
    http_response_code(404);
    echo "No result found";
}
die();

?>
