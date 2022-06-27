<?php
	$health = $health->health_check( $db_host, $db_name, $db_user, $db_password );
// if ( empty( $all_characters ) ) {
// http_response_code( 404 );
// echo json_encode( 'None found.' );
// die();
// }
	http_response_code( 200 );
	echo json_encode( $health );
	die();
