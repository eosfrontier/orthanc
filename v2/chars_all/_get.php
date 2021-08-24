<?php
if ( isset( $input['all_characters'] ) ) {
	$all_char_players = $c_fetch_players->get_all( 'player' );
	$all_char_figus = $c_fetch_figus->get_all( 'player' );
	if (is_array($a_char_figus) && !is_array($a_char_players)) { 
		$a_character = $a_char_figus;
	} elseif (!is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = $a_char_players;
	} elseif (is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = array_merge($a_char_players, $a_char_figus);
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
	$a_char_players = $c_fetch_players->get( $input['accountID'], 'accountID' );
	$a_char_figus = $c_fetch_figus->get( $input['accountID'], 'accountID' );
	if (is_array($a_char_figus) && !is_array($a_char_players)) { 
		$a_character = $a_char_figus;
	} elseif (!is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = $a_char_players;
	} elseif (is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = array_merge($a_char_players, $a_char_figus);
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

if ( isset( $input['accountid'] ) ) {
        $a_char_players = $c_fetch_players->get( $input['accountid'], 'accountid' );
        $a_char_figus = $c_fetch_figus->get( $input['accountid'], 'accountid' );
        if (is_array($a_char_figus) && !is_array($a_char_players)) {
                $a_character = $a_char_figus;
        } elseif (!is_array($a_char_figus) && is_array($a_char_players)) {
                $a_character = $a_char_players;
        } elseif (is_array($a_char_figus) && is_array($a_char_players)) {
                $a_character = array_merge($a_char_players, $a_char_figus);
        }
        if ( empty( $a_character ) ) {
                http_response_code( 404 );
                echo json_encode( 'None found by accountid.' );
                die();
        }

        http_response_code( 200 );
        echo json_encode( $a_character );
        die();
}


// CHECK BY CHARACTER ID

if ( isset( $input['char_id'] ) || isset( $input['id'] ) ) {
	$charid      = isset( $input['char_id'] ) ? $input['char_id'] : ( isset( $input['id'] ) ? $input['id'] : '' );
	$a_char_players = array(); $a_char_players = $c_fetch_players->get( $charid, 'characterID' );
	$a_char_figus = array(); $a_char_figus = $c_fetch_figus->get( $charid, 'characterID' );
	if (is_array($a_char_figus) && !is_array($a_char_players)) { 
		$a_character = $a_char_figus;
	} elseif (!is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = $a_char_players;
	} elseif (is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = array_merge($a_char_players, $a_char_figus);
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
	$a_char_players = $c_fetch_players->get( $input['card_id'], 'card_id' );
	$a_char_figus = $c_fetch_figus->get( $input['card_id'], 'card_id' );
	if (is_array($a_char_figus) && !is_array($a_char_players)) { 
		$a_character = $a_char_figus;
	} elseif (!is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = $a_char_players;
	} elseif (is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = array_merge($a_char_players, $a_char_figus);
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
	$a_char_players = $c_fetch_players->get( $input['icc_number'], 'icc_number' );
	$a_char_figus = $c_fetch_figus->get( $input['icc_number'], 'icc_number' );
	if (is_array($a_char_figus) && !is_array($a_char_players)) { 
		$a_character = $a_char_figus;
	} elseif (!is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = $a_char_players;
	} elseif (is_array($a_char_figus) && is_array($a_char_players)) {
		$a_character = array_merge($a_char_players, $a_char_figus);
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
