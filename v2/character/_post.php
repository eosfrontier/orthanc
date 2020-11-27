<?php

if ( empty( $input['character'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included all needed info." );
	die();
}
$a_account   = $input['accountID'];
$a_character = json_decode( $input['character'], true );

$a_result = $c_fetch->add_character( $a_account, $a_character );
http_response_code( 200 );
echo json_encode( $a_result );
die();
