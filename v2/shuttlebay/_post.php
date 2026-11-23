<?php

if ($routeId !== null) {
	http_response_code(400);
	echo json_encode(['error' => 'POST /v2/shuttlebay/ does not accept a shuttle id in the URL path. Use PUT /v2/shuttlebay/:id to update.']);
	die();
}

$payload = shuttlebay_read_json_body((string) $rawInput);

try {
	$created = $shuttlebay->createShuttle($payload);
	http_response_code(201);
	echo json_encode($created);
} catch (InvalidArgumentException $e) {
	http_response_code(400);
	echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['error' => 'Failed to create shuttle.']);
}
die();
