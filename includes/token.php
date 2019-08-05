<?php

//CHECK ACCESS TOKEN
$post = json_decode(file_get_contents('php://input'), true);
$access = token($post["token"]);

if($access == false){
    http_response_code(401);
    echo json_encode("YOU SHALL NOT PASS!!");
    die();
}

?>