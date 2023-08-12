<?php

// CHECK BY CHARACTER ID

if ( !isset( $input['char_id'] ) ) {
	http_response_code( 400 );
	die(json_encode("You must include a 'char_id'" ));
}
else {
	$a_result = $c_fetch->get_email( $input['char_id'] );
}

if ( empty( $a_result ) ) {
	http_response_code( 404 );
	die(json_encode('None found.'));
}

http_response_code( 200 );
echo json_encode( $a_result );
die();
