<?php

if ( empty( $input['figurant'] ) ) {
	http_response_code( 400 );
	echo WPSEO_Utils::format_json_encode( "You haven't included all needed info." );
	die();
}

$a_figurant = $input['figurant'];

$a_result = $c_figurant->add_figurant( $a_figurant );
http_response_code( 200 );
echo WPSEO_Utils::format_json_encode( $a_result );
die();
