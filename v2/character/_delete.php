<?php

if ( ! isset( $input['id'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't include an id to delete." );
	die();
} else {
	$delete_figu = $c_character->delete_character( $input['id'] );
	if ( $delete_figu == 0 ) {
		http_response_code( 404 );
		echo json_encode( 'No Characters deleted. Either specified id is not a Character, or they were already deleted.' );
		die();
	} else {
		http_response_code( 200 );
		echo json_encode( $delete_figu . ' Characters deleted.' );
		die();
	}
}
