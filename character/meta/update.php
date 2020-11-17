<?php
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
$input = json_decode( file_get_contents( 'php://input' ), true );

require_once '../../includes/include.php';
require_once '../../includes/token.php';

$c_meta = new meta();

if ( empty( $input['id'] ) ) {
	http_response_code( 400 ); // Haven't answered a way t acccess.
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
