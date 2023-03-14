<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';

$app = new App();

$app_id = $app->get_appid_by_token( $input['token'] );
if ( empty( $app_id ) ) {
	http_response_code( 404 );
	echo json_encode( 'None found.' );
	die();
}
http_response_code( 200 );
echo json_encode( $app_id );
die();
