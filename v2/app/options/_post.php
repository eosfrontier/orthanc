<?php

if ( empty( $input['id'] ) ) {
	// Haven't answered a way to access.
	http_response_code( 400 );
	echo json_encode( "You haven't included an 'id'." );
	die();
}

if ( empty( $input['option'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included option." );
	die();
}

$id       = $input['id'];
$options  = json_decode( $input['option'], true );
$a_result = $c_option->post_options( $id, $options );
	http_response_code( 200 );
	echo json_encode( $a_result );
die();
