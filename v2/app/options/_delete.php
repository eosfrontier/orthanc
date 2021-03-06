<?php
if ( empty( $input['id'] ) || empty( $input['option'] ) ) {
	http_response_code( 400 ); // Haven't answered a way to access..
	echo json_encode( "You haven't included an 'id'." );
	die();
}

$id     = $input['id'];
$option = $input['option'];

$a_result = $c_option->delete_option( $id, $option );

if ( ! empty( $a_result ) ) {
	http_response_code( 200 );
	echo json_encode( $a_result );
}
else {
	http_response_code( 404 );
	echo 'No result found';
}
die();
