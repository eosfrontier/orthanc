<?php
if ( empty( $input['id'] ) || empty( $input['meta'] ) ) {
	http_response_code( 400 ); // Haven't answered a way to access..
	echo json_encode( "You haven't included a 'id'." );
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
