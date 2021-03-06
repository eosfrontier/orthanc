<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';

$c_meta     = new meta();

switch ( $method ) {
	case 'DELETE':
		require_once '../../meta/_delete.php';
		break;
	case 'POST':
		require_once '../../meta/_post.php';
		break;
	case 'PUT':
		require_once '../../meta/_put.php';
		break;
	case 'PATCH':
		require_once '../../meta/_patch.php';
		break;
	case 'GET':
		require_once '../../meta/_get.php';
		break;
	case 'OPTIONS':
		http_response_code( 200 );
	default:
		require_once '../../meta/_get.php';
		break;
}
