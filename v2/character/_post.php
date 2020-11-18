<?php

if ( empty( $input['character'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included all needed info." );
	die();
}

$a_character = $input['character'];

$a_result = $c_fetch->add_character( $a_character );
http_response_code( 200 );
echo json_encode( $a_result );
die();
