<?php

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

// Inject Headers
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
header( 'Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS' );
header( 'Access-Control-Allow-Headers: *' );

// Store Input
$input = json_decode( file_get_contents( 'php://input' ), true );

// Grab HTTP REST Method
$method = $_SERVER['REQUEST_METHOD'];

if ( $method === 'OPTIONS' ) {
	http_response_code( 204 );
	die();
}

spl_autoload_register(
	function ( $classname ) {
		include "classes/$classname.php";
	}
);

$app             = [];
$app['includes'] = []; // opens an array to be filled later with the CSS and JS, which will eventually be included by PHP.
$app['header']   = '/api/orthanc'; // location of the application. for example: http://localhost/api/orthanc/ == '/api/orthanc'. If the application is in the ROOT, you can leave this blank.
$app['root']     = $_SERVER['DOCUMENT_ROOT'] . $app['header']; // define the root folder by adding the header (location) to the server root, defined by PHP.

if ( ! isset( $input ) ) {
	$input = apache_request_headers();
}
