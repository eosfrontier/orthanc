<?php

$id = shuttlebay_require_route_id($routeId);

try {
	$deleted = $shuttlebay->deleteShuttle($id);
	if (! $deleted) {
		http_response_code(404);
		echo json_encode(['error' => 'Shuttle not found.']);
		die();
	}

	http_response_code(200);
	echo json_encode([
		'deleted'     => true,
		'id'          => $id,
		'status'      => Shuttlebay::STATUS_DELETED,
		'status_name' => 'Deleted',
	]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['error' => 'Failed to delete shuttle.']);
}
die();
