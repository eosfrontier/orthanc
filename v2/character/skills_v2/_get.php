<?php
if ( empty( $input['id'] ) && !isset($input['get_all']) ) {
	// Haven't answered a way to access.
	http_response_code( 400 );
	echo json_encode( "You haven't included an 'id' or 'get_all'." );
	die();
}
if(isset($input['id']))
{
	$id = $input['id'];
}

if( isset($input['get_all'])){
	$a_skills = $c_fetch->get_skills_all_chars();
} else {
$a_skills = $c_fetch->get_skills( $id );
}

http_response_code( 200 );
echo json_encode( $a_skills );
die();
