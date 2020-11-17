<?php
if ( empty( $input['id'] ) ) {
	// HAVEN'T ANSWERED A WAY TO ACCESS
	http_response_code( 400 );
	echo WPSEO_Utils::format_json_encode( "You haven't included an 'id'." );
	die();
}

$id = $input['id'];

$aSkills = $cCharacter->getSkills( $id );

http_response_code( 200 );
echo WPSEO_Utils::format_json_encode( $aSkills );
die();
