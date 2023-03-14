<?php
if (isset($input['all_characters'])) {
	$all_characters = $c_fetch->get_all_background_checks();
	if (empty($all_characters)) {
		http_response_code(404);
		echo json_encode('None found.');
		die();
	}
	http_response_code(200);
	echo json_encode($all_characters);
	die();
}

// CHECK BY CHARACTER ID

if (isset($input['char_id'])) {
	$a_character = $c_fetch->get_background_check($input['char_id'], 'characterID');
	if (empty($a_character)) {
		http_response_code(404);
		echo json_encode('None found.');
		die();
	}
	http_response_code(200);
	echo json_encode($a_character);
	die();
}
// Haven't answered a way to access.
http_response_code(400);
die("You haven't included a 'char_id'");
