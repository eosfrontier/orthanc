<?php

if ( empty( $input['figurant'] ) ) {
	http_response_code( 400 );
	echo WPSEO_Utils::format_json_encode( "You haven't included all needed info." );
	die();
}

$aFigurant = $input['figurant'];

$aResult = $cFigurant->addFigurant( $aFigurant );
http_response_code( 200 );
echo WPSEO_Utils::format_json_encode( $aResult );
die();
