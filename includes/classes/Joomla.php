<?php

$method = $_SERVER['REQUEST_METHOD']; // Grab HTTP REST Method
if ($method === 'OPTIONS') {
	http_response_code(204);
	die();
}
class Joomla
{

	/**
	 * To use Joomla's Database Class.
	 *
	 * @return void
	 */
	public function get_joomla_user_and_group()
	{
		// Required Files
		define('_JEXEC', 1);
		define('JPATH_BASE', '/var/www/html');
		require_once JPATH_BASE . '/includes/defines.php';
		require_once JPATH_BASE . '/includes/framework.php';

		$app = JFactory::getApplication('site');
		$user = JFactory::getUser();

		if ($user->get('guest')) {
			$cookie_name =
				'joomla_remember_me_' . JUserHelper::getShortHashedUserAgent();
			// Check for the cookie
			if ($app->input->cookie->get($cookie_name)) {
				$app->login(['username' => ''], ['silent' => true]);
				$user = JFactory::getUser();
			}
		}

		$myobj = new \stdClass();
		$myobj->id = $user->get('id');
		$myobj->groups = $user->get('groups');

		// Generate pretty Json array for Silvester to use
		foreach ($myobj->groups as $array) {
			$array1[] = $array;
		}

		$array = [
			'id' => $myobj->id,
			'groups' => $array1,
		];

		return $array;
	}

	public function get_joomla_users_by_group($group_id)
	{
		$response = [];
		$stmt = Database::$conn->prepare(
			"SELECT r2.user_id as id, 
			coalesce(replace(replace(replace(CONCAT(r2.first_name, ' ', COALESCE(v6.field_value,''),' ', r2.last_name),' ','<>'),'><',''),'<>',' ')) as name
			FROM jml_eb_registrants r2
				left join jml_eb_field_values v6 on (v6.registrant_id = r2.id and v6.field_id = 16)
			WHERE r2.id = 
				(SELECT max(r1.id) FROM jml_eb_registrants r1 
				WHERE r1.user_id = r2.user_id) AND r2.user_id IN (SELECT user_id FROM jml_user_usergroup_map 
				WHERE group_id IN  (SELECT id FROM jml_usergroups 
				WHERE id = $group_id UNION SELECT id FROM jml_usergroups WHERE parent_id = $group_id)) ORDER BY name"
		);
		$users = $stmt->execute();
		$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $users;
	}

	public function get_joomla_users_by_group_and_event($group_id, $event)
	{
		$response = [];
		$stmt = Database::$conn->prepare(
			"SELECT DISTINCT u.id, coalesce(replace(replace(replace(CONCAT(r.first_name, ' ', COALESCE(v6.field_value,''),' ', r.last_name),' ','<>'),'><',''),'<>',' '), u.name) as name FROM jml_users u
		left JOIN jml_eb_registrants r ON u.id = r.user_id
		left join jml_eb_field_values v5 on (v5.registrant_id = r.id and v5.field_id = 14)
		left join jml_eb_field_values v6 on (v6.registrant_id = r.id and v6.field_id = 16)
		WHERE r.event_id = $event and ((r.published = 1 AND (r.payment_method = 'os_ideal' OR r.payment_method = 'os_paypal')) OR 
(r.published in (0,1) AND r.payment_method = 'os_offline')) AND u.id in (SELECT user_id FROM jml_user_usergroup_map WHERE group_id IN (SELECT id FROM jml_usergroups WHERE id = $group_id UNION SELECT id FROM jml_usergroups WHERE parent_id = $group_id)) ORDER by name"
		);
		$users = $stmt->execute();
		$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $users;
	}

	public function get_joomla_groups($group_name)
	{
		$stmt = Database::$conn->prepare("SELECT id,parent_id,title FROM jml_usergroups WHERE title like '$group_name' ORDER by title asc");
		$groups = $stmt->execute();
		$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $groups;
	}
}
