<?php
if((!isset($input['id']) && !isset($input['get_all'])) || (isset($input['id']) && isset($input['get_all'])) )
{
	http_response_code( 400 );
	echo json_encode( "You MUST provide an 'id' OR 'get_all'." );
	die();
} 
if (isset($input['id'])){
	$a_skills = $c_fetch->get_skills( $input['id'] );
	if (isset($a_skills['http_response'])) {
		if ($a_skills['http_response'] == '404'){
			http_response_code( 404 );
			echo json_encode ( 'CharacterID ' . $input['id'] . ' does not exist.');
			die();
	}
}
	http_response_code( 200 );
	echo json_encode( $a_skills );
	die();

}
if( isset($input['get_all']) && !(isset($input['id'])))
{
	$a_skills = $c_fetch->get_skills_all_chars();
	http_response_code( 200 );
	echo json_encode( $a_skills );
	die();
}

	

