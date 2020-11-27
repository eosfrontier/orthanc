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

header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
$input = json_decode( file_get_contents( 'php://input' ), true );
require_once '../includes/include.php';

require_once '../includes/token.php';

$c_fetch = new character();

if ( isset( $input['all_characters'] ) ) {
	$all_characters = $c_fetch->get_all();
	if ( empty( $all_characters ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $all_characters );
	die();
}

//CHECK BY JOOMLA ID
if ( isset( $input['accountID'] ) ) {
	$a_character = $c_fetch->get( $input['accountID'], 'accountID' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo json_encode( $a_character );
	die();
}

//CHECK BY CHARACTER ID
if ( isset( $input['char_id'] ) ) {
	$a_character = $c_fetch->get( $input['char_id'], 'characterID' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo json_encode( $a_character );
	die();
}

//CHECK BY CARD ID
if ( isset( $input['card_id'] ) ) {
	$a_character = $c_fetch->get( $input['card_id'], 'card_id' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo json_encode( $a_character );
	die();
}

//CHECK BY ICC NUMBER
if ( isset( $input['icc_number'] ) ) {
	$a_character = $c_fetch->get( $input['icc_number'], 'icc_number' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo json_encode( $a_character );
	die();
}

//Haven't answered a way to access.
http_response_code( 400 );
echo json_encode( "You haven't included a 'accountID', 'char_id', 'card_id' or 'icc_number'." );
