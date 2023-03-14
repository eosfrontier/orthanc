<?php
if ( isset( $input['all_characters'] ) ) {
	$all_characters = $c_fetch->get_all_background_checks();
	if ( empty( $all_characters ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $all_characters );
	die();
}

// CHECK BY CHARACTER ID

if ( isset( $input['id'] ) ) {
	$a_character = $c_fetch->get_background_check( $input['id'], 'characterID' );
if ( isset( $input['char_id'] ) ) {
	$a_character = $c_fetch->get_background_check( $input['char_id'], 'characterID' );
	if ( empty( $a_character ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	else {
		die("You forgot to include an 'id'");
	}


	http_response_code( 200 );
	echo json_encode( $a_character );
	die();
}




// Haven't answered a way to access.
http_response_code( 400 );
echo json_encode( "You haven't included a 'char_id'" );
