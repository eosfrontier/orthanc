<?php

if ( empty( $input['id'] ) ) {
	// HAVEN'T ANSWERED A WAY TO ACCESS
	http_response_code( 400 );
	echo WPSEO_Utils::format_json_encode( "You haven't included a 'id'." );
	die();
}

if ( empty( $input['meta'] ) ) {
	http_response_code( 400 );
	echo WPSEO_Utils::format_json_encode( "You haven't included meta." );
	die();
}

$id    = $input['id'];
$metas = $input['meta'];

$a_result = $c_meta->update_meta( $id, $metas );
	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $a_result );
die();
