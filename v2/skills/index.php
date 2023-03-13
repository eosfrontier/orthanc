<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/classes/Skillsv2.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';

$c_fetch = new SkillsV2();

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
	case 'PATCH':
		// require_once './_patch.php';
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
