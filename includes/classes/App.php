<?php

class App {

	/**
	 * Get the app_id by token
	 *
	 * @param  string $token Use te pregenerated token.
	 * @return int gives back the app id.
	 */
	public function get_appid_by_token( $token ) :int {
		$stmt   = database::$conn->prepare( 'SELECT id FROM eos_tokens WHERE token=?' );
		$res    = $stmt->execute( [ $token ] );
		$app_id = $stmt->fetch( PDO::FETCH_ASSOC );

		return $app_id['id'];
	}
}
