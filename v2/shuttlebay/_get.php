<?php

if (!isset($input['id']) && !isset($input['getAll'])) {
	http_response_code(400);
	die(json_encode("You must include a 'id' OR 'getAll'"));
} elseif (isset($input['id']) && isset($input['getAll'])) {
	http_response_code(400);
	die(json_encode("You must include ONLY an 'id' OR 'getAll'"));
} elseif (isset($input['id'])) {
	$response = $shuttlebay->getShuttle(($input['id']));
} elseif (isset($input['getAll'])) {
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
