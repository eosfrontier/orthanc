<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once "../includes/classes/character.php";
 
$character = new Character();
$character = $character->read();

http_response_code(200);
echo json_encode($character);
?>