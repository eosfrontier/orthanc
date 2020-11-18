<?php

if ( empty( $input['figurant'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included all needed info." );
	die();
}

$a_figurant = $input['figurant'];

$a_result = $c_fetch->add_figurant( $a_figurant );
http_response_code( 200 );
echo json_encode( $a_result );
die();
