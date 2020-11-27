<?php
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
$input = json_decode( file_get_contents( 'php://input' ), true );

require_once '../../includes/include.php';

require_once '../../includes/token.php';

$c_fetch = new character();

if ( empty( $input['id'] ) ) {
	//Haven't answered a way to access.
	http_response_code( 400 );
	echo json_encode( "You haven't included an 'id'." );
	die();
}

$id = $input['id'];

$a_skills = $c_fetch->get_skills( $id );

http_response_code( 200 );
echo json_encode( $a_skills );
die();
