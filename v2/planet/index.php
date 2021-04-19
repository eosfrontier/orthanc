<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';

$planet = new Planet();

switch ( $method ) {
	case 'GET':
		require_once './_get.php';
		break;
	default:
		require_once './_get.php';
		break;
}
