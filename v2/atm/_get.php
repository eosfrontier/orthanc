<?php

/**
 * Get_amount_by_id returns the the amount of sonuren someone has based on character ID
 */

$amount = $bank->get_amount_by_id( $input['id'] );
if ( empty( $amount ) ) {
	http_response_code( 404 );
	echo json_encode( 'None found.' );
	die();
}
http_response_code( 200 );
echo json_encode( $amount );
die();
