<?php

// CHECK BY CHARACTER ID

if ( !isset( $input['type'] ) ) {
	http_response_code( 400 );
	die(json_encode("You must include a 'type'" ));
}

else {
	if ($input['type'] != 'concept' && $input['type'] != 'backstory'){
		http_response_code( 400 );
		die(json_encode("Invalid type. 'type' must be 'concept' or 'backstory'"));
	}
$a_result = $c_fetch->get_statuses( $input['type'] );
if ( empty( $a_result ) ) {
	http_response_code( 404 );
	die(json_encode('None found.'));
}

http_response_code( 200 );
echo json_encode( $a_result );
die();

}