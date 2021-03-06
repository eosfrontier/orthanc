<?php

if ( empty( $input['id'] ) ) {
	// Haven't answered a way to access.
	http_response_code( 400 );
	echo json_encode( "You haven't included an 'id'." );
	die();
}

if ( empty( $input['meta'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included meta." );
	die();
}

$id    = $input['id'];
$metas = json_decode($input['meta'], true);
$a_result = $c_meta->patch_metas( $id, $metas );
	http_response_code( 200 );
	echo json_encode( $a_result );
die();