<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$input = json_decode(file_get_contents('php://input'), true);

include_once "../../includes/include.php";
include_once "../../includes/token.php";

$cFigurant = new figurant();

if(empty($input["character"])){
    http_response_code(400);
    echo json_encode("You haven't included meta.");
    die();
}

$cFigurant  = $input["figurant"];

$aResult = $cFigurant->addFigurant($cFigurant);
    http_response_code(200);
    echo json_encode($aResult);
die();

?>