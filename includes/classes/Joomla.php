<?php

$method = $_SERVER['REQUEST_METHOD']; // Grab HTTP REST Method
if ( $method === 'OPTIONS' ) {
	http_response_code( 204 );
	die();
}
class Joomla {

	/**
	 * To use Joomla's Database Class.
	 *
	 * @return void
	 */
	public function get_joomla_user_and_group() {
		// Required Files
		define( '_JEXEC', 1 );
		define( 'JPATH_BASE', '/var/www/html' );
		require_once JPATH_BASE . '/includes/defines.php';
		require_once JPATH_BASE . '/includes/framework.php';

		$app  = JFactory::getApplication( 'site' );
		$user = JFactory::getUser();

		if ( $user->get( 'guest' ) ) {
			$cookie_name =
			'joomla_remember_me_' . JUserHelper::getShortHashedUserAgent();
			// Check for the cookie
			if ( $app->input->cookie->get( $cookie_name ) ) {
				$app->login( [ 'username' => '' ], [ 'silent' => true ] );
				$user = JFactory::getUser();
			}
		}

		$myobj         = new \stdClass();
		$myobj->id     = $user->get( 'id' );
		$myobj->groups = $user->get( 'groups' );

		// Generate pretty Json array for Silvester to use
		foreach ( $myobj->groups as $array ) {
			$array1[] = $array;
		}

		$array = [
			'id'     => $myobj->id,
			'groups' => $array1,
		];

		return $array;
	}

	public function get_joomla_users_by_group( $group_id ) {
		$response = [];
		$stmt     = Database::$conn->prepare( "SELECT id,name,username FROM jml_users WHERE id in (SELECT user_id FROM jml_user_usergroup_map WHERE group_id = $group_id)" );
		$users    = $stmt->execute();
		$users    = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $users;
	}

	public function get_joomla_groups( $group_name ) {
		$stmt   = Database::$conn->prepare( "SELECT id,parent_id,title FROM jml_usergroups WHERE title like '$group_name' ORDER by title asc" );
		$groups = $stmt->execute();
		$groups = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $groups;
	}
}

