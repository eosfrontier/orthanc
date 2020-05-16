<?php
$cFigurant = new figurant();

if(empty($input["figurant"])){
    http_response_code(400);
    echo json_encode("You haven't included all needed info.");
    die();
}

$aFigurant  = $input["figurant"];

$aResult = $cFigurant->addFigurant($aFigurant);
    http_response_code(200);
    echo json_encode($aResult);
die();