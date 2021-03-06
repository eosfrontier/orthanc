<?php

if ( isset( $input['option_name'] ) ) {
	$option_name = $input['option_name'];
	$a_options   = explode( ',', $option_name );
	$option_name = '';
	foreach ( $a_options as $a_option ) {
		$option_name .= "'" . $a_option . "',";
	}
	$option_name = rtrim( $option_name, ',' );

	if ( isset( $input['id'] ) && ! isset( $input['wildcard'] ) ) {
		$a_result = $c_option->get_by_option_name( $input['id'], $option_name );
	}
	elseif ( isset( $input['id'] ) && isset( $input['wildcard'] ) ) {
		$operator = 'like';

		$a_result = $c_option->get_by_option_name( $input['id'], $option_name, $operator );
	}
	elseif ( isset( $input['wildcard'] ) ) {
		$operator = 'like';
		$a_result = $c_option->get_all_by_option_name( $option_name, $operator );
	}
	else {
		$a_result = $c_option->get_all_by_option_name( $option_name );
	}
}
else {
	if ( isset( $input['id'] ) ) {
		$a_result = $c_option->get_all_options_by_id( $input['id'] );
	}
	else {
		http_response_code( 400 );
		echo json_encode( "You haven't included a 'id' or 'option_name'." );
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
