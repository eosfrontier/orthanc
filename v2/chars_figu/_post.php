<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);
if ( empty( $input['figurant'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included all needed info." );
	die();
}

$a_figurant = json_decode( utf8_encode( $input['figurant']), true );

$a_result = $c_fetch->add_figurant( $a_figurant );
http_response_code( 200 );
echo json_encode( $a_result );
die();
