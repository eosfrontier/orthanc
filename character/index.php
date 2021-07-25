<?php
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
$input = json_decode( file_get_contents( 'php://input' ), true );
require_once '../includes/include.php';

require_once '../includes/token.php';

$c_fetch = new Char_Player();

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
