<?php
if ( ! isset( $input['id'] ) ) {
	http_response_code( 400 );
	echo json_encode( "You haven't include an id to delete." );
	die();
}
elseif ( ! isset( $input['restore'] ) ) {
	$delete_figu = $c_fetch->delete_character( $input['id'] );
	if ( $delete_figu == 0 ) {
		http_response_code( 404 );
		echo json_encode( 'No Characters deleted. Either specified id is not a Character, or they were already deleted.' );
		die();
	}
	else {
		http_response_code( 200 );
		echo json_encode( $delete_figu . ' Characters deleted.' );
		die();
	}
}

else {
	$restore = $input['restore'];
	if ( isset( $restore )  ) {
		$restore_char = $c_fetch->restore_character( $input['id'] );
		if ( $restore_char == 0 ) {
			http_response_code( 404 );
			echo json_encode( 'No characters restored. Either specified id is not a figurant, or they were already deleted.' );
			die();
		}
		else {
			http_response_code( 200 );
			echo json_encode( $restore_char . ' characters restored.' );
			die();
		}
	}
	else {
		http_response_code( 400 );
		echo 'restore header set, but not set to true.';
		die();
	}
}
