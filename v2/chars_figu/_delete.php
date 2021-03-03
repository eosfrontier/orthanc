<?php

if ( ! isset( $input['id'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't included an id to delete." );
	die();
}
elseif ( ! isset( $input['restore'] ) ) {
	$delete_figu = $c_fetch->delete_figurant( $input['id'] );
	if ( $delete_figu == 0 ) {
		http_response_code( 404 );
		echo json_encode( 'No figuranten deleted. Either specified id is not a figurant, or they were already deleted.' );
		die();
	}
	else {
		http_response_code( 200 );
		echo json_encode( $delete_figu . ' figuranten deleted.' );
		die();
	}
}

else {
	$restore = $input['restore'];
	if ( isset( $restore ) ) {
		$restore_figu = $c_fetch->restore_figurant( $input['id'] );
		if ( $restore_figu == 0 ) {
			http_response_code( 404 );
			echo json_encode( 'No figuranten restored. Either specified id is not a figurant, or they were already deleted.' );
			die();
		}
		else {
			http_response_code( 200 );
			echo json_encode( $restore_figu . ' figuranten restored.' );
			die();
		}
	}
	else {
		http_response_code( 400 );
		echo 'restore header set, but not set to true.';
		die();
	}
}
