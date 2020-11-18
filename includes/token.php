<?php

function token( $token ) {
	$stmt = database::$conn->prepare( 'SELECT * FROM eos_tokens WHERE token = ?' );
	$res  = $stmt->execute( [ $token ] );
	$res  = $stmt->fetch( PDO::FETCH_ASSOC );

	if ( $res !== null ) {
		return 'valid';
	} 
	else {
		return false;
	}
}
// CHECK ACCESS TOKEN.

$headers = getallheaders();

$access = '';
if ( isset( $headers['token'] ) ) {
	$access = token( $headers['token'] );
}
elseif ( isset( $input['token'] ) ) {
	$access = token( $input['token'] );
}

else {
	http_response_code( 401 );
	echo json_encode( 'YOU SHALL NOT PASS!!' );
	die();
}
