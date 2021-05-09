<?php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/orthanc/includes/include.php';
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
				echo 'Test';
				break;
			}
			require_once './_get.php';
			break;
		case 'OPTIONS':
			http_response_code( 200 );
		default:
			require_once './_get.php';
		break;
	}
