<?php
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
$input = json_decode( file_get_contents( 'php://input' ), true );

require_once '../../includes/include.php';
require_once '../../includes/token.php';

$c_meta = new Meta();

if ( empty( $input['id'] ) || empty( $input['meta'] ) ) {
	// Haven't answered a way to access.
	http_response_code( 400 );
	echo json_encode( "You haven't included an 'id'." );
	die();
}

$id   = $input['id'];
$meta = $input['meta'];

$a_result = $c_meta->delete_meta( $id, $meta );

if ( ! empty( $a_result ) ) {
	http_response_code( 200 );
	echo json_encode( $a_result );
}
else {
	http_response_code( 404 );
	echo 'No result found';
}
die();
