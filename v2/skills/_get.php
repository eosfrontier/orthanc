<?php
if ( isset( $input['all'] ) ) {
	$all_skills = $c_fetch->get_all_active();
	if ( empty( $all_skills ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $all_skills );
	die();
}