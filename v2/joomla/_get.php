<?php

$j_session = $j_fetch->get_joomla_user_and_group();

http_response_code( 200 );
echo json_encode( $j_session );
die();
