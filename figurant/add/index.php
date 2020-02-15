<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$post = json_decode(file_get_contents('php://input'), true);

include_once "../../includes/include.php";
include_once "../../includes/token.php";

$cFigurant = new figurant();

if(empty($post["figurant"])){
    http_response_code(400);
    echo json_encode("You haven't included all needed info.");
    die();
}

$aFigurant  = $post["figurant"];

$aResult = $cFigurant->addFigurant($aFigurant);
    http_response_code(200);
    echo json_encode($aResult);
die();

?>
