<?php
if (isset($input["all_characters"])) {
    $allCharacters = $cFigurant->getAll("player");
    if (empty($allCharacters)) {
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }
    http_response_code(200);
    echo json_encode($allCharacters);
    die();
}

//CHECK BY JOOMLA ID
if (isset($input["accountID"])) {
    $aCharacter = $cFigurant->get($input["accountID"], "accountID");
    if (empty($aCharacter)) {
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }

    http_response_code(200);
    echo json_encode($aCharacter);
    die();
}

//CHECK BY CHARACTER ID

if (isset($input["char_id"]) || isset($input["id"])) {
    $charid = isset($input["char_id"]) ? $input["char_id"] : (isset($input["id"]) ? $input["id"] : '');
    $aCharacter = $cFigurant->get($charid, "characterID");
    if (empty($aCharacter)) {
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }

    http_response_code(200);
    echo json_encode($aCharacter);
    die();
}


//CHECK BY CARD ID
if (isset($input["card_id"])) {
    $aCharacter = $cFigurant->get($input["card_id"], "card_id");
    if (empty($aCharacter)) {
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }

    http_response_code(200);
    echo json_encode($aCharacter);
    die();
}

//CHECK BY ICC NUMBER
if (isset($input["icc_number"])) {
    $aCharacter = $cFigurant->get($input["icc_number"], "ICC_number");
    if (empty($aCharacter)) {
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }

    http_response_code(200);
    echo json_encode($aCharacter);
    die();
}

//HAVEN'T ANSWERED A WAY TO ACCESS
http_response_code(400);
echo json_encode("You haven't included a 'accountID', 'char_id', 'card_id' or 'icc_number'.");
