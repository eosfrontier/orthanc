<?php

/**
 * Get_amount_by_id returns the the amount of sonuren someone has based on character ID
 */
if ( isset( $input['amount'] ) ) {
	$amount = $bank->get_amount_by_id( $input['id'] );
	if ( empty( $amount ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $amount );
	die();
}

/**
 * Get_all_recepients get all viable bank recepients
 */
if ( isset( $input['recepients'] ) ) {
	$recepients = $bank->get_all_recepients();
	if ( empty( $recepients ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $recepients );
	die();
}

/**
 * Get_mutations get a list of al mutations from a character by ID
 */
$mutations = $bank->get_mutations( $input['id'] );
if ( empty( $mutations ) ) {
	http_response_code( 404 );
	echo json_encode();
	die();
}
http_response_code( 200 );
echo json_encode( $mutations );
die();
