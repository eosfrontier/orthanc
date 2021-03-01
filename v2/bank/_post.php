<?php

$transfer = $bank->transfer( $input );

http_response_code( 201 );
echo json_encode( $transfer );
die();
