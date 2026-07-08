<?php

// ini_set( 'display_errors', 1 );
// ini_set( 'display_startup_errors', 1 );
// error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

// Inject Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: *');
echo json_encode($_SERVER);
// Helper function to get normalized request headers
if (!function_exists('get_normalized_headers')) {
	function get_normalized_headers(): array
	{
		if (function_exists('getallheaders')) {
			$headers = getallheaders();
		} else {
			$headers = [];
			foreach ($_SERVER as $name => $value) {
				if (strpos($name, 'HTTP_') === 0) {
					$original_name_part = substr($name, 5);
					$hyphenated_name = str_replace('_', '-', strtolower($original_name_part));
					$underscored_name = strtolower($original_name_part);

					$headers[$hyphenated_name] = $value;
					// Also add the underscored version for compatibility, if it's different
					if ($hyphenated_name !== $underscored_name) {
						$headers[$underscored_name] = $value;
					}
				} elseif (strpos($name, 'FIX_') === 0) {
					$original_name_part = substr($name, 4);
					$hyphenated_name = str_replace('_', '-', strtolower($original_name_part));
					$underscored_name = strtolower($original_name_part);

					$headers[$hyphenated_name] = $value;
					// Also add the underscored version for compatibility, if it's different
					if ($hyphenated_name !== $underscored_name) {
						$headers[$underscored_name] = $value;
					}
				} elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
					$headers[str_replace('_', '-', strtolower($name))] = $value;
					$headers[strtolower($name)] = $value; // Also add underscored version for these
				}
			}
		}
		return array_change_key_case($headers, CASE_LOWER);
	}
}

// Store Input
$rawInput = file_get_contents('php://input');
$decodedInput = json_decode($rawInput, true);

// Initialize $input as an array. If json_decode failed or returned null, it will be an empty array.
// This ensures we always have an array to merge headers into.
$input = is_array($decodedInput) ? $decodedInput : [];

// Merge headers from apache_request_headers() into $input.
// The `+` operator for arrays adds elements from the right-hand array
// only if the key does not already exist in the left-hand array.
// This means if a key exists in $input (from JSON body), it won't be overwritten by apache_request_headers().
// This is generally desired behavior for body data.
$input += apache_request_headers();

// Diagnostic output - REMOVE OR COMMENT OUT IN PRODUCTION
// echo 'Input (after JSON and apache_request_headers): '. json_encode($input) . "\n";
// Retrieve and merge normalized headers into $input
$normalizedHeaders = get_normalized_headers();
// Diagnostic output - REMOVE OR COMMENT OUT IN PRODUCTION
// echo 'Normalized Headers: ' . json_encode($normalizedHeaders) . "\n";
$input = is_array($input) ? array_merge($input, $normalizedHeaders) : $normalizedHeaders;
echo json_encode($input); // Diagnostic output - REMOVE OR COMMENT OUT IN PRODUCTION	
// Compatibility mapping for hyphens, underscores, and legacy camel-case keys
if (is_array($input)) {
}

// Grab HTTP REST Method
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') {
	http_response_code(204);
	die();
}

spl_autoload_register(
	function ($classname) {
		include "classes/$classname.php";
	}
);

$app             = [];
$app['includes'] = []; // opens an array to be filled later with the CSS and JS, which will eventually be included by PHP.
$app['header']   = '/api/orthanc'; // location of the application. for example: http://localhost/api/orthanc/ == '/api/orthanc'. If the application is in the ROOT, you can leave this blank.
$app['root']     = $_SERVER['DOCUMENT_ROOT'] . $app['header']; // define the root folder by adding the header (location) to the server root, defined by PHP.
