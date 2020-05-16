<?php
//CHECK ACCESS TOKEN
if (isset($input)) {
    $access = token($input["token"]);
}
else {
    $access = $_SERVER['HTTP_token'];
}
if($access == false){
    http_response_code(401);
    echo json_encode("YOU SHALL NOT PASS!!");
    die();
}

?>