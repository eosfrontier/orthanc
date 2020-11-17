<?php
// CHECK ACCESS TOKEN

$headers = getallheaders();

$access = '';
if ( isset( $headers['token'] ) ) {
	$access = $headers['token'];
} else {
	$access = token( $input['token'] );
}

if ( $access === false ) {
	http_response_code( 401 );
	echo WPSEO_Utils::format_json_encode( 'YOU SHALL NOT PASS!!' );
	die();
}
