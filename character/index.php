<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once "../includes/include.php";

include_once "../includes/token.php";

$cCharacter = new character();

//CHECK BY CHARACTER ID
if(isset($post["char_id"])){
    $aCharacter = $cCharacter->get($post["char_id"], "characterID");
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
if(isset($post["card_id"])){
    $aCharacter = $cCharacter->get($post["card_id"], "card_id");
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
if(isset($post["icc_number"])){
    $aCharacter = $cCharacter->get($post["icc_number"], "ICC_number");
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
echo json_encode("You haven't included a 'char_id', 'card_id' or 'icc_number'.");
?>

