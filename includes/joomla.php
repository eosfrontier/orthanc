<?php
// Grab HTTP REST Method
$method = $_SERVER['REQUEST_METHOD'];
if ( $method === 'OPTIONS' ) {
	http_response_code( 204 );
	die();
}
class joomla {
    // To use Joomla's Database Class
    function get_joomla_user_and_group(){ 
    // Required Files
    define('_JEXEC', 1);
    define('JPATH_BASE', '/var/www/html');
    require_once JPATH_BASE . '/includes/defines.php';
    require_once JPATH_BASE . '/includes/framework.php';

    $app = JFactory::getApplication('site');
    $user = JFactory::getUser();

        if ($user->get('guest')) {
            $cookieName =
                'joomla_remember_me_' . JUserHelper::getShortHashedUserAgent();
            // Check for the cookie
            if ($app->input->cookie->get($cookieName)) {
                $app->login(array('username' => ''), array('silent' => true));
                $user = JFactory::getUser();
            }
        }

        $myobj = new \stdClass();
        $myobj->id = $user->get('id');
        $myobj->groups = $user->get('groups');

        //Generate pretty Json array for Silvester to use
        foreach ($myobj->groups as $array) {
            $array1[] = $array;
        }

        $array = array(
            'id' => $myobj->id,
            'groups' => $array1
        );

        return $array;
    }
}
