<?php
if ( isset( $input['all_characters'] ) ) {
	$allCharacters = $cCharacter->getAll( 'player' );
	if ( empty( $allCharacters ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $allCharacters );
	die();
}

// CHECK BY JOOMLA ID
if ( isset( $input['accountID'] ) ) {
	$aCharacter = $cCharacter->get( $input['accountID'], 'accountID' );
	if ( empty( $aCharacter ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $aCharacter );
	die();
}

// CHECK BY CHARACTER ID

if ( isset( $input['char_id'] ) || isset( $input['id'] ) ) {
	$charid     = isset( $input['char_id'] ) ? $input['char_id'] : ( isset( $input['id'] ) ? $input['id'] : '' );
	$aCharacter = $cCharacter->get( $charid, 'characterID' );
	if ( empty( $aCharacter ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $aCharacter );
	die();
}


// CHECK BY CARD ID
if ( isset( $input['card_id'] ) ) {
	$aCharacter = $cCharacter->get( $input['card_id'], 'card_id' );
	if ( empty( $aCharacter ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $aCharacter );
	die();
}

// CHECK BY ICC NUMBER
if ( isset( $input['icc_number'] ) ) {
	$aCharacter = $cCharacter->get( $input['icc_number'], 'ICC_number' );
	if ( empty( $aCharacter ) ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $aCharacter );
	die();
}

// HAVEN'T ANSWERED A WAY TO ACCESS
http_response_code( 400 );
echo WPSEO_Utils::format_json_encode( "You haven't included a 'accountID', 'char_id', 'card_id' or 'icc_number'." );
