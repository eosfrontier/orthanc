<?php
if ( isset( $input['all_characters'] ) ) {
	$all_char_players = $c_fetch_players->get_all_active();
	$all_char_figus   = $c_fetch_figus->get_all_active();
	if ( is_array( $all_char_figus ) && ! is_array( $all_char_players ) ) {
		$all_characters = $all_char_figus;
	}
	elseif ( ! is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$all_characters = $all_char_players;
	}
	elseif ( is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$all_characters = array_merge( $all_char_players, $all_char_figus );
	}
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
	$all_char_players = $c_fetch_players->get_all();
	$all_char_figus   = $c_fetch_figus->get_all();
	if ( is_array( $all_char_figus ) && ! is_array( $all_char_players ) ) {
		$all_characters = $all_char_figus;
	}
	elseif ( ! is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$all_characters = $all_char_players;
	}
	elseif ( is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$all_characters = array_merge( $all_char_players, $all_char_figus );
	}
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
if ( isset( $input['accountID'] ) ) {
	$all_char_players = $c_fetch_players->get_active( $input['accountID'], 'accountID' );
	$all_char_figus   = $c_fetch_figus->get_active( $input['accountID'], 'accountID' );
	if ( is_array( $all_char_figus ) && ! is_array( $all_char_players ) ) {
		$a_character = $all_char_figus;
	}
	elseif ( ! is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$a_character = $all_char_players;
	}
	elseif ( is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$a_character = array_merge( $all_char_players, $all_char_figus );
	}
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
	$charid           = isset( $input['char_id'] ) ? $input['char_id'] : ( isset( $input['id'] ) ? $input['id'] : '' );
	$all_char_players = [];
	$all_char_players = $c_fetch_players->get_active( $charid, 'characterID' );
	$all_char_figus   = [];
	$all_char_figus   = $c_fetch_figus->get_active( $charid, 'characterID' );
	if ( is_array( $all_char_figus ) && ! is_array( $all_char_players ) ) {
		$a_character = $all_char_figus;
	}
	elseif ( ! is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$a_character = $all_char_players;
	}
	elseif ( is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$a_character = array_merge( $all_char_players, $all_char_figus );
	}

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
	$all_char_players = $c_fetch_players->get_active( $input['card_id'], 'card_id' );
	$all_char_figus   = $c_fetch_figus->get_active( $input['card_id'], 'card_id' );
	if ( is_array( $all_char_figus ) && ! is_array( $all_char_players ) ) {
		$a_character = $all_char_figus;
	}
	elseif ( ! is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$a_character = $all_char_players;
	}
	elseif ( is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$a_character = array_merge( $all_char_players, $all_char_figus );
	}
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
	$all_char_players = $c_fetch_players->get_active( $input['icc_number'], 'icc_number' );
	$all_char_figus   = $c_fetch_figus->get_active( $input['icc_number'], 'icc_number' );
	if ( is_array( $all_char_figus ) && ! is_array( $all_char_players ) ) {
		$a_character = $all_char_figus;
	}
	elseif ( ! is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$a_character = $all_char_players;
	}
	elseif ( is_array( $all_char_figus ) && is_array( $all_char_players ) ) {
		$a_character = array_merge( $all_char_players, $all_char_figus );
	}
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
