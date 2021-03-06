<?php

if ( isset( $input['meta_name'] ) ) {
	$meta_name = $input['meta_name'];
	$a_metas   = explode( ',', $meta_name );
	$meta_name = '';
	foreach ( $a_metas as $a_meta ) {
		$meta_name .= "'" . $a_meta . "',";
	}
	$meta_name = rtrim( $meta_name, ',' );

	if ( isset( $input['id'] ) && ! isset( $input['wildcard'] ) ) {
		$a_result = $c_meta->get_by_meta_name( $input['id'], $meta_name );
	}
	elseif ( isset( $input['id'] ) && isset( $input['wildcard'] ) ) {
		$operator = 'like';

		$a_result = $c_meta->get_by_meta_name( $input['id'], $meta_name, $operator );
	}
	elseif ( isset( $input['wildcard'] ) ) {
		$operator = 'like';
		$a_result = $c_meta->get_all_by_meta_name( $meta_name, $operator );
	}
	else {
		$a_result = $c_meta->get_all_by_meta_name( $meta_name );
	}
}
else {
	if ( isset( $input['id'] ) ) {
		$a_result = $c_meta->get_all_meta_by_id( $input['id'] );
	}
	else {
		http_response_code( 400 );
		echo json_encode( "You haven't included a 'id' or 'meta_name'." );
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