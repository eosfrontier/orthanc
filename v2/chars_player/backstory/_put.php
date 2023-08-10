<?php
if ( !isset( $input['char_id'] ) || !isset( $input['type'] ) || !isset($input['content']) ) {
	http_response_code( 400 );
	die(json_encode("You must include 'char_id', 'type' headers, and a json encoded request body containing the content" ));
}
else {
	$types = ['concept', 'backstory', 'concept_changes' ,'backstory_changes'];
	if ( in_array( $input['type'], $types ) ){
		http_response_code( 400 );
		die(json_encode("Invalid type. 'type' must be 'concept' or 'backstory'"));
	}
	$a_result = $c_fetch->set_backstory( $input['char_id'], $input['type'], $input['content'] );
	http_response_code( 200 );
	echo json_encode( $a_result );
	die();
}
