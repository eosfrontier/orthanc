<?php

if ( empty( $input['id'] ) ) {
	// Haven't answered a way to access.
	http_response_code( 400 );
	echo json_encode( "You haven't included an 'id'." );
	die();
}

$id = $input['id'];

$a_skills = $c_fetch->get_skills( $id );

http_response_code( 200 );
echo json_encode( $a_skills );
die();
