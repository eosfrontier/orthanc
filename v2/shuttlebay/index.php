<?php
require_once __DIR__ . '/../../includes/include.php';

/**
 * Resolves the optional trailing :id path segment for /v2/shuttlebay/:id requests.
 *
 * Returns null if no route id was provided. Rejects deeper nested paths with 404.
 */
function shuttlebay_resolve_route_id(): ?string
{
	if (array_key_exists('id', $_GET)) {
		if (! is_string($_GET['id'])) {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid shuttle id. Must be a positive integer.']);
			die();
		}
		$trimmed = trim($_GET['id']);
		return $trimmed === '' ? null : $trimmed;
	}

	if (! empty($_SERVER['PATH_INFO'])) {
		$pathInfo = trim((string) $_SERVER['PATH_INFO'], '/');
		if ($pathInfo !== '') {
			if (strpos($pathInfo, '/') !== false) {
				http_response_code(404);
				echo json_encode(['error' => 'Endpoint not found.']);
				die();
			}
			return urldecode($pathInfo);
		}
	}

	if (! empty($_SERVER['REQUEST_URI'])) {
		$path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
		if (is_string($path)) {
			$normalized = preg_replace('#(/v2/shuttlebay)/index\.php(/|$)#i', '$1$2', $path);
			if (preg_match('#/v2/shuttlebay/([^/]+)/.+$#i', $normalized)) {
				http_response_code(404);
				echo json_encode(['error' => 'Endpoint not found.']);
				die();
			}
			if (preg_match('#/v2/shuttlebay/([^/]+)/?$#i', $normalized, $matches)) {
				return urldecode($matches[1]);
			}
		}
	}

	return null;
}

/**
 * Validates that a route id represents a positive integer, exiting with 400 if not.
 */
function shuttlebay_require_route_id(?string $rawId): int
{
	if ($rawId === null || $rawId === '') {
		http_response_code(400);
		echo json_encode(['error' => 'Missing shuttle id in URL path (expected /v2/shuttlebay/:id).']);
		die();
	}

	$parsed = filter_var($rawId, FILTER_VALIDATE_INT);
	if (! ctype_digit($rawId) || $parsed === false || $parsed <= 0 || $parsed > 2147483647) {
		http_response_code(400);
		echo json_encode(['error' => 'Invalid shuttle id. Must be a positive integer.']);
		die();
	}

	return (int) $parsed;
}

/**
 * Decodes a required JSON object request body, exiting with 400 on malformed or non-object input.
 */
function shuttlebay_read_json_body(string $rawInput): array
{
	if (trim($rawInput) === '') {
		http_response_code(400);
		echo json_encode(['error' => 'Request body is required and must be a JSON object.']);
		die();
	}

	$decoded = json_decode($rawInput, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		http_response_code(400);
		echo json_encode(['error' => 'Invalid JSON request body: ' . json_last_error_msg()]);
		die();
	}

	if (! is_array($decoded) || substr(ltrim($rawInput), 0, 1) !== '{') {
		http_response_code(400);
		echo json_encode(['error' => 'Request body must be a JSON object.']);
		die();
	}

	return $decoded;
}

$routeId    = shuttlebay_resolve_route_id();
$shuttlebay = new Shuttlebay();

switch ($method) {
	case 'GET':
		require_once __DIR__ . '/_get.php';
		break;
	case 'POST':
		require_once __DIR__ . '/../../includes/token.php';
		require_once __DIR__ . '/_post.php';
		break;
	case 'PUT':
		require_once __DIR__ . '/../../includes/token.php';
		require_once __DIR__ . '/_put.php';
		break;
	case 'DELETE':
		require_once __DIR__ . '/../../includes/token.php';
		require_once __DIR__ . '/_delete.php';
		break;
	case 'PATCH':
		http_response_code(501);
		echo json_encode(['error' => 'PATCH method is not implemented for /v2/shuttlebay/.']);
		break;
	case 'OPTIONS':
		http_response_code(204);
		break;
	default:
		header('Allow: GET, POST, PUT, DELETE, OPTIONS');
		http_response_code(405);
		echo json_encode(['error' => 'Method not allowed.']);
		break;
}
