<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$post = json_decode(file_get_contents('php://input'), true);

include_once "../../includes/include.php";
include_once "../../includes/token.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once "./get.php";
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    require_once "./update.php";
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    require_once "./delete.php";
}

?>
