<?php
if ( isset( $input['all_characters'] ) ) {
	$all_characters = $c_figurant->get_all( 'player' );
	if ( empty( $all_characters ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $all_characters );
	die();
}

// CHECK BY JOOMLA ID
if ( isset( $input['accountID'] ) ) {
	$a_character = $c_figurant->get( $input['accountID'], 'accountID' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $a_character );
	die();
}

// CHECK BY CHARACTER ID

if ( isset( $input['char_id'] ) || isset( $input['id'] ) ) {
	$charid     = isset( $input['char_id'] ) ? $input['char_id'] : ( isset( $input['id'] ) ? $input['id'] : '' );
	$a_character = $c_figurant->get( $charid, 'characterID' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $a_character );
	die();
}


// CHECK BY CARD ID
if ( isset( $input['card_id'] ) ) {
	$a_character = $c_figurant->get( $input['card_id'], 'card_id' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $a_character );
	die();
}

// CHECK BY ICC NUMBER
if ( isset( $input['icc_number'] ) ) {
	$a_character = $c_figurant->get( $input['icc_number'], 'icc_number' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $a_character );
	die();
}

// HAVEN'T ANSWERED A WAY TO ACCESS
http_response_code( 400 );
echo WPSEO_Utils::format_json_encode( "You haven't included a 'accountID', 'char_id', 'card_id' or 'icc_number'." );
