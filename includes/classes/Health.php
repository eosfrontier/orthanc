<?php
class Health {

	/**
	 * Check the API is responding and can access its DB
	 *
	 * @return mixed returns the version of MySQL is the server is up and running and can access its DB.
	 */
	public function health_check( $db_host, $db_name, $db_user, $db_password ) {
		try {
			$dbh = new pdo(
				'mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8mb4',
				$db_user,
				$db_password,
				[ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
			);
			die( json_encode( [ 'healthy' => true ] ) );
		}
		catch ( PDOException $ex ) {
			die(
				json_encode(
					[
						'healthy' => false,
						'message' => 'Unable to connect',
					]
				)
			);
		}
	}
}
