<?php

/**
 * Get all planets
 */

token( $input['token'] );

if ( isset( $_GET['portal'] ) ) {
	$planets = $planet->get_planets_with_portals();
}
else {
	$planets = $planet->get_planets();
}

if ( empty( $planets ) ) {
	http_response_code( 404 );
	echo json_encode( 'None found.' );
	die();
}
http_response_code( 200 );
echo json_encode( $planets );
die();
