<?php 
/**
 * @OA\Info(title="Orthanc Character API", version="0.1")
 */

/**
 * @OA\Post(
 *     path="/orthanc/character/",
 *     summary="Retrieve a character from the database",
 *     @OA\Response(response="200", description="A character from the database"),
 *     @OA\RequestBody(
 *       description="Search parameters",
 *       content=oneOf(
 *         @OA\JsonContent(
 *           type="object",
 *           @OA\Property(property="all_characters", type="boolean", description="Get all characters"),
 *         ),
 *         @OA\JsonContent(
 *           type="object",
 *           @OA\Property(property="accountID", type="number", description="by Joomla account ID"),
 *         ),
 *         @OA\JsonContent(
 *           type="object",
 *           @OA\Property(property="char_id", type="number", description="by character ID"),
 *         ),
 *         @OA\JsonContent(
 *           type="object",
 *           @OA\Property(property="card_id", type="string", description="by card ID"),
 *         ),
 *         @OA\JsonContent(
 *           type="object",
 *           @OA\Property(property="icc_number", type="string", description="by ICC number")
 *         )
 *       )
 *     )
 * )
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$post = json_decode(file_get_contents('php://input'), true);
include_once "../includes/include.php";

include_once "../includes/token.php";

$cCharacter = new character();

if(isset($post["all_characters"])){
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
if(isset($post["accountID"])){
    $aCharacter = $cCharacter->get($post["accountID"], "accountID");
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
echo json_encode("You haven't included a 'accountID', 'char_id', 'card_id' or 'icc_number'.");
?>

