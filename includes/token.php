<?php
//CHECK ACCESS TOKEN
$access = token($input["token"]);

if($access == false){
    http_response_code(401);
    echo json_encode("YOU SHALL NOT PASS!!");
    die();
}

?>