<?php

if(!isset($input["id"])){
    $allCharacters = $cFigurant->getAll();
    if(empty($allCharacters)){
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }
    http_response_code(200);
    echo json_encode($allCharacters);
    die();
}