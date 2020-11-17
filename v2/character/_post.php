<?php

if (empty($input["character"])) {
    http_response_code(400);
    echo json_encode("You haven't included all needed info.");
    die();
}

$acharacter  = $input["character"];

$aResult = $ccharacter->addcharacter($acharacter);
http_response_code(200);
echo json_encode($aResult);
die();
