<?php

if ( isset( $input['meta'] ) ) {
	$meta    = $input['meta'];
	$a_metas = explode( ',', $meta );
	$meta    = '';
	foreach ( $a_metas as $a_meta ) {
		$meta .= "'" . $a_meta . "',";
	}
	$meta = rtrim( $meta, ',' );

	if ( isset( $input['id'] ) ) {
		$a_result = $c_meta->get_by_meta( $input['id'], $meta );
	}
	else {
		$a_result = $c_meta->get_all_by_meta( $meta );
	}
}
else {
	if ( isset( $input['id'] ) ) {
		$a_result = $c_meta->get_all_meta_by_id( $input['id'] );
	}
	else {
		http_response_code( 400 );
		echo json_encode( "You haven't included a 'id' or 'meta'." );
		die();
	}
}


if ( ! empty( $a_result ) ) {
	http_response_code( 200 );
	echo json_encode( $a_result );
} else {
	http_response_code( 404 );
	echo 'No result found';
}
die();
