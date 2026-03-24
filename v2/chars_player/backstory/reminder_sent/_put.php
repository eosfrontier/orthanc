<?php
if ($input['type'] == 'reminder') {
	if (!isset($input['char_id']) || !isset($input['type'])) {
		http_response_code(400);
		die(json_encode("You must include 'char_id', and 'type' headers"));
	} else {
		$a_result = $c_fetch->set_reminder_date($input['char_id']);
		http_response_code(200);
		echo json_encode($a_result);
		die();
	}
}
