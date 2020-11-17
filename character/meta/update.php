<?php
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
$input = json_decode( file_get_contents( 'php://input' ), true );

require_once '../../includes/include.php';
require_once '../../includes/token.php';

$cMeta = new meta();

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

$aResult = $cMeta->updateMeta( $id, $metas );
	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $aResult );
die();
