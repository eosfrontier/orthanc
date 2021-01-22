<?php
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

// Inject Headers
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json; charset=UTF-8' );
header( 'Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS' );
header( 'Access-Control-Allow-Headers: *' );

// Store Input
$input = json_decode( file_get_contents( 'php://input' ), true );

// Grab HTTP REST Method
$method = $_SERVER['REQUEST_METHOD'];
if ( $method === 'OPTIONS' ) {
	http_response_code( 204 );
	die();
}


$app             = [];
$app['includes'] = []; // opens an array to be filled later with the CSS and JS, which will eventually be included by PHP.
$app['header']   = '/api/orthanc'; // location of the application. for example: http://localhost/api/orthanc/ == '/api/orthanc'. If the application is in the ROOT, you can leave this blank.
$app['root']     = $_SERVER['DOCUMENT_ROOT'] . $app['header']; // define the root folder by adding the header (location) to the server root, defined by PHP.

if ( ! isset( $input ) ) {
	$input = apache_request_headers();
}

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
$app = JFactory::getApplication('site');
$user = JFactory::getUser();

class joomla {
// Required Files
    // To use Joomla's Database Class
    function get_joomla_user_and_group(){ 
        
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

