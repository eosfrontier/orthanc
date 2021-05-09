<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/classes/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/joomla.php';
$j_fetch    = new joomla();

switch ( $method ) {
	case 'DELETE':
		http_response_code( 501 );
		break;
	case 'POST':
		http_response_code( 501 );
		break;
	case 'PUT':
		http_response_code( 501 );
		break;
	case 'GET':
		require_once './_get.php';
		break;
	case 'OPTIONS':
		http_response_code( 200 );
	default:
		require_once './_get.php';
		break;
}
