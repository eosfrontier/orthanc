<?php
// CHECK ACCESS TOKEN.

$headers = getallheaders();

$access = '';
if ( isset( $headers['token'] ) ) {
	$access = $headers['token'];
}
elseif ( isset( $input['token'] ) ) {
	$access = token( $input['token'] );
}

else {
	http_response_code( 401 );
	echo json_encode( 'YOU SHALL NOT PASS!!' );
	die();
}
