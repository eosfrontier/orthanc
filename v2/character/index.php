<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$input = json_decode(file_get_contents('php://input'), true);
include_once "../includes/include.php";

include_once "../includes/token.php";

$cCharacter = new character();

if(isset($input["all_characters"])){
    $allCharacters = $cCharacter->getAll();
    if(empty($allCharacters)){
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }
    http_response_code(200);
    echo json_encode($allCharacters);
    die();
}

//CHECK BY JOOMLA ID
if(isset($input["accountID"])){
    $aCharacter = $cCharacter->get($input["accountID"], "accountID");
    if(empty($aCharacter)){
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }

    http_response_code(200);
    echo json_encode($aCharacter);
    die();
}

//CHECK BY CHARACTER ID
if(isset($input["char_id"])){
    $aCharacter = $cCharacter->get($input["char_id"], "characterID");
    if(empty($aCharacter)){
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }

    http_response_code(200);
    echo json_encode($aCharacter);
    die();
}

//CHECK BY CARD ID
if(isset($input["card_id"])){
    $aCharacter = $cCharacter->get($input["card_id"], "card_id");
    if(empty($aCharacter)){
        http_response_code(404);
        echo json_encode("None found.");
        die();
    }

    http_response_code(200);
    echo json_encode($aCharacter);
    die();
}

//CHECK BY ICC NUMBER
if(isset($input["icc_number"])){
    $aCharacter = $cCharacter->get($input["icc_number"], "ICC_number");
    if(empty($aCharacter)){
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
?>

