<?php
if ( isset( $input['group_id'] ) ) {
	if ( isset( $input['current_event'] ) ) {
		$e_fetch = new Event();
		$current_event = $e_fetch->get_eventid( 'current' );
		$j_users = $j_fetch->get_joomla_users_by_group_and_event( $input['group_id'], $current_event );
	}
	else {
		$j_users = $j_fetch->get_joomla_users_by_group( $input['group_id'] );
	}
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
