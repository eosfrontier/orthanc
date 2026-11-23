<?php

$requestedId   = $routeId !== null ? $routeId : ($input['id'] ?? null);
$hasId         = $requestedId !== null && $requestedId !== '';
$hasGetAll     = isset($input['getall']);
$hasGetClasses = isset($input['getclasses']);

$selectedCount = (int) $hasId + (int) $hasGetAll + (int) $hasGetClasses;

if ($selectedCount === 0) {
	http_response_code(400);
	die(json_encode("You must include an 'id', 'getall', OR 'getclasses'"));
} elseif ($selectedCount > 1) {
	http_response_code(400);
	die(json_encode("You must include ONLY one of 'id', 'getall', OR 'getclasses'"));
} elseif ($hasId) {
	if (! is_scalar($requestedId)) {
		http_response_code(400);
		die(json_encode("Invalid 'id'. Must be a positive integer."));
	}
	$idString = trim((string) $requestedId);
	$parsedId = filter_var($idString, FILTER_VALIDATE_INT);
	if (! ctype_digit($idString) || $parsedId === false || $parsedId <= 0 || $parsedId > 2147483647) {
		http_response_code(400);
		die(json_encode("Invalid 'id'. Must be a positive integer."));
	}
	$response = $shuttlebay->getShuttle((int) $parsedId);
} elseif ($hasGetClasses) {
	$response = $shuttlebay->getAllClasses();
} else {
	$response = $shuttlebay->getAllShuttles();
}

if (empty($response)) {
	http_response_code(404);
	echo json_encode('None found.');
	die();
}

http_response_code(200);
echo json_encode($response);
die();
