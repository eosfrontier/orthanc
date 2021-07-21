<?php

$response = $atm->scan_chit( $input );

http_response_code( 201 );
echo json_encode( $response );
die();
