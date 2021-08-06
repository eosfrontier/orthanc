<?php

$j_groups = $j_fetch->get_joomla_groups();

if ( empty( $j_groups ) ) {
	http_response_code( 404 );
	echo json_encode( 'None found.' );
	die();
}
else {
	http_response_code( 200 );
	echo json_encode( $j_groups );
}

	die();
