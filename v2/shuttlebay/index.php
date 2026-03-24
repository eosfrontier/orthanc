<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include.php';
#require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';
$shuttlebay = new Shuttlebay();

switch ($method) {
	case 'POST':
		http_response_code(501);
		break;
	case 'GET':
		require_once './_get.php';
		break;
	case 'PATCH':
		http_response_code(501);
		break;
	case 'OPTIONS':
		http_response_code(200);
		break;
	default:
		require_once './_get.php';
		break;
}
