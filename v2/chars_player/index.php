<?php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include_char_player.php';
	require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/token.php';
	$c_fetch = new char_player();

	switch ( $method ) {
		case 'DELETE':
			require_once './_delete.php';
			break;
		case 'POST':
			require_once './_post.php';
			break;
		case 'PUT':
			require_once './_put.php';
			break;
		case 'PATCH':
			// require_once './_patch.php';
			http_response_code( 501 );
			break;
		case 'GET':
			if( isset( $input['get_logged_in']))
			{
				require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/joomla.php';
				$j_fetch    = new joomla();
				$j_session = $j_fetch->get_joomla_user_and_group();
				if ($j_session['id'] != '0'){
					$a_character = $c_fetch->get( $j_session['id'], 'accountID' );
					if ( empty( $a_character ) ) {
						http_response_code( 404 );
						echo json_encode( 'None found by accountID.' );
						die();
					}

					http_response_code( 200 );
					echo json_encode( $a_character );
					break;
				} elseif ($j_session['id'] == '0') {
					http_response_code ( 401 );
					echo 'Not logged in.' ;
					break;
				} 
			}
			require_once './_get.php';
			break;
		case 'OPTIONS':
			http_response_code( 200 );
		default:
			require_once './_get.php';
		break;
	}
