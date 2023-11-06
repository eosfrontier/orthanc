<?php
if ( isset( $input['all_characters'] ) ) {
	$all_characters = $c_fetch->get_all_active();
	if ( empty( $all_characters ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $all_characters );
	die();
}

if ( isset( $input['all_characters_no_backstory'] ) ) {
	$all_characters = $c_fetch->get_all_active_no_backstory();
	if ( empty( $all_characters ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $all_characters );
	die();
}

if ( isset( $input['all_characters_all_statuses'] ) ) {
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

// CHECK BY JOOMLA ID
if ( isset( $input['accountID'] ) || isset( $input['accountid'] ) ) {
	if ( isset( $input['accountID'] ) ) {
		$accountid = $input['accountID'];
	}
	if ( isset( $input['accountid'] ) ) {
		$accountid = $input['accountid'];
	}
	$a_character = $c_fetch->get_active( $accountid, 'accountID' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found by accountID.' );
		die();
	}

	http_response_code( 200 );
	echo json_encode( $a_character );
	die();
}

// CHECK BY CHARACTER ID

if ( isset( $input['char_id'] ) || isset( $input['id'] ) ) {
	$charid      = isset( $input['char_id'] ) ? $input['char_id'] : ( isset( $input['id'] ) ? $input['id'] : '' );
	$a_character = $c_fetch->get_active( $charid, 'characterID' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo json_encode( $a_character );
	die();
}


// CHECK BY CARD ID
if ( isset( $input['card_id'] ) ) {
	$a_character = $c_fetch->get_active( $input['card_id'], 'card_id' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo json_encode( $a_character );
	die();
}

// CHECK BY ICC NUMBER
if ( isset( $input['icc_number'] ) ) {
	$a_character = $c_fetch->get_active( $input['icc_number'], 'icc_number' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}

	http_response_code( 200 );
	echo json_encode( $a_character );
	die();
}

// Haven't answered a way to access.
http_response_code( 400 );
echo json_encode( "You haven't included a 'accountID', 'char_id', 'card_id' or 'icc_number'." );
