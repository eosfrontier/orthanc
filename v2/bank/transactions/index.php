<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';
$bank = new bank();

/**
 * Get_mutations get a list of all mutations from a character by ID
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
