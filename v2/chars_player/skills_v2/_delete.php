<?php
if ( empty( $input['id'] ) ) {
	// Haven't answered a way to access.
	http_response_code( 400 );
	echo json_encode( "You haven't included an 'id'." );
	die();
}
elseif ( empty( $input['skill_id'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included a 'skill_id'" );
	die();
}

$char_id  = $input['id'];
$skill_id = $input['skill_id'];

$a_delskills = $c_fetch->del_skill( $char_id, $skill_id );
if ( $a_delskills == 0 ) {
	http_response_code( 404 );
	echo json_encode( 'No matching skill ' . $skill_id . ' for character ' . $char_id . '.' );
	die();
}
else {
	http_response_code( 200 );
	echo json_encode( $a_delskills );
	die();
}
