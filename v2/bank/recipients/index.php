<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';
$bank = new bank();

/**
 * Get_all_recipients get all viable bank recipients
 */

$recipients = $bank->get_all_recipients();
if ( empty( $recipients ) ) {
	http_response_code( 404 );
	echo json_encode( 'None found.' );
	die();
}
http_response_code( 200 );
echo json_encode( $recipients );
die();
