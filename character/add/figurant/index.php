<?php
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
$input = json_decode( file_get_contents( 'php://input' ), true );

require_once '../../includes/include.php';
require_once '../../includes/token.php';

$c_figurant = new figurant();

if ( empty( $input['character'] ) ) {
	http_response_code( 400 );
	echo WPSEO_Utils::format_json_encode( "You haven't included meta." );
	die();
}

$c_figurant = $input['figurant'];

$a_result = $c_figurant->add_figurant( $c_figurant );
	http_response_code( 200 );
	echo WPSEO_Utils::format_json_encode( $a_result );
die();
