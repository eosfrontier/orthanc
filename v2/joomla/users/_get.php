<?php
$current_event = $e_fetch->get_eventid( 'current' );

if ( isset( $input['group_id'] ) ) {
	$j_users = $j_fetch->get_joomla_users_by_group( $input['group_id'], $current_event );
	if ( empty( $j_users ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $j_users );
} else {
	http_response_code( 400 );
		echo json_encode( 'You must provide a group_id.' );
		die();
}



die();
