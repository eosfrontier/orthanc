<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';
$c_meta     = new meta();

$sheet_type = $c_meta->get_char_type_by_id( $input['id'] );
if ( !(strpos( $sheet_type, 'figurant' ) !== false) ) {
	http_response_code( 406 );
	echo json_encode("CharacterID ".$input['id']." is not a figurant.");
	exit;
}
else {
	switch ( $method ) {
		case 'DELETE':
			require_once './_delete.php';
			break;
		case 'POST':
			// require_once './_post.php';
			http_response_code( 501 );
			break;
		case 'PUT':
			require_once './_put.php';
		case 'GET':
			require_once './_get.php';
			break;
		case 'OPTIONS';
			http_response_code( 200 );
		default:
			require_once './_get.php';
			break;
	}
}
