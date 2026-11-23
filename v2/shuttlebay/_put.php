<?php

$id      = shuttlebay_require_route_id($routeId);
$payload = shuttlebay_read_json_body((string) $rawInput);

try {
	$updated = $shuttlebay->updateShuttle($id, $payload);
	if ($updated === null) {
		http_response_code(404);
		echo json_encode(['error' => 'Shuttle not found.']);
		die();
	}

	http_response_code(200);
	echo json_encode($updated);
} catch (InvalidArgumentException $e) {
	http_response_code(400);
	echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['error' => 'Failed to update shuttle.']);
}
die();
