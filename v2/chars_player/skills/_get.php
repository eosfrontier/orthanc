<?php
if ( empty( $input['id'] ) ) {
	// Haven't answered a way to access.
	http_response_code( 400 );
	echo json_encode( "You haven't included an 'id'." );
	die();
}

if ( isset( $input['tree'] )) {
	$a_skills = $c_fetch->get_skills_by_tree( $input['id'], $input['tree'] );
}
else {
	$a_skills = $c_fetch->get_skills( $input['id'] );
}




http_response_code( 200 );
echo json_encode( $a_skills );
die();
