<?php

if ( empty( $input['figurant'] ) || empty( $input['id'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included all needed info." );
	die();
}
$a_id       = $input['id'];
$a_figurant = json_decode( $input['figurant'], true );

$a_result = $c_fetch->put_figurant( $a_id, $a_figurant );
	http_response_code( 200 );
	echo json_encode( $a_result );
