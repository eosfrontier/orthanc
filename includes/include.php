<?php

// ini_set( 'display_errors', 1 );
// ini_set( 'display_startup_errors', 1 );
// error_reporting( E_ALL );

// Inject Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: *');

// Helper function to get normalized request headers
if (!function_exists('get_normalized_headers')) {
    function get_normalized_headers(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace('_', '-', substr($name, 5))] = $value;
                } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                    $headers[str_replace('_', '-', $name)] = $value;
                }
            }
        }
        return array_change_key_case($headers, CASE_LOWER);
    }
}

// Store Input
$input = json_decode(file_get_contents('php://input'), true);

// Retrieve and merge normalized headers into $input
$normalizedHeaders = get_normalized_headers();
$input = is_array($input) ? array_merge($input, $normalizedHeaders) : $normalizedHeaders;

// Compatibility mapping for hyphens, underscores, and legacy camel-case keys
if (is_array($input)) {
	// Duplicate hyphenated keys with underscores (e.g., 'char-id' becomes also accessible as 'char_id')
	foreach ($input as $key => $value) {
		if (strpos($key, '-') !== false) {
			$underscoreKey = str_replace('-', '_', $key);
			if (!isset($input[$underscoreKey])) {
				$input[$underscoreKey] = $value;
			}
		}
	}

	$compatMap = [
		'token'         => 'Token',
		'authorization' => 'Authorization',
	];
	foreach ($compatMap as $lower => $original) {
		if (isset($input[$lower]) && !isset($input[$original])) {
			$input[$original] = $input[$lower];
		}
	}
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
