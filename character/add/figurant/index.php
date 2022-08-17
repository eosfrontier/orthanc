<?php
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
$input = json_decode( file_get_contents( 'php://input' ), true );

require_once '../../includes/include.php';
require_once '../../includes/token.php';

$c_fetch = new Char_Figu();

if ( empty( $input['character'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included meta." );
	die();
}

$c_fetch = $input['figurant'];

$a_result = $c_fetch->add_figurant( $c_fetch );
	http_response_code( 200 );
	echo json_encode( $a_result );
die();
