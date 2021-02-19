<?php 

$a_event = $c_fetch->get_upcoming();
http_response_code( 200 );
echo json_encode( $a_event );
die();