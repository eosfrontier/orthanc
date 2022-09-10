<?php
if ( !isset( $input['char_id'] ) || !isset( $input['type'] ) || !isset($input['status']) ) {
	http_response_code( 400 );
	die(json_encode("You must include 'char_id', 'type' and 'status' headers" ));
}
else {
	if ($input['type'] != 'concept' && $input['type'] != 'backstory'){
		http_response_code( 400 );
		die(json_encode("Invalid type. 'type' must be 'concept' or 'backstory'"));
	}
	//Check that the chosen status is a valid status.
	$type = $input['type'];
	$stmt = Database::$conn->prepare("SELECT id, status_name, status_description FROM ecc_backstory_status WHERE status_type = '$type'");
	$res  = $stmt->execute();
	if ($stmt->rowCount() > 0) {
	$a_result = $c_fetch->update_status( $input['char_id'], $input['type'], $input['status'] );
	http_response_code( 200 );
	echo json_encode( $a_result );
	die();
	}
	else {
		die ("status is not a valid status_name");
	}
}