<?php

if ( ! isset( $input['id'] ) ) {
	http_response_code( 400 );
	echo WPSEO_Utils::format_json_encode( "You haven't include an id to delete." );
	die();
} else {
	$deleteFigu = $cCharacter->deleteCharacter( $input['id'] );
	if ( $deleteFigu == 0 ) {
		http_response_code( 404 );
		echo WPSEO_Utils::format_json_encode( 'No Characters deleted. Either specified id is not a Character, or they were already deleted.' );
		die();
	} else {
		http_response_code( 200 );
		echo WPSEO_Utils::format_json_encode( $deleteFigu . ' Characters deleted.' );
		die();
	}
}
