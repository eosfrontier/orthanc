<?php

if ( empty( $input['id'] ) ) {
	// Haven't answered a way to access.
	http_response_code( 400 );
	echo json_encode( "You haven't included a 'id'." );
	die();
}

if ( empty( $input['meta'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included meta." );
	die();
}

$id    = $input['id'];
$metas = $input['meta'];

$a_result = $c_meta->update_meta( $id, $metas );
	http_response_code( 200 );
	echo json_encode( $a_result );
die();
