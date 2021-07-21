<?php
class Atm {

	/**
	 * Checks if the current code exists in the database
	 *
	 * @param  mixed $code The code that must be checked.
	 * @return string
	 */
	public function check_if_code_exist( $code ) {
		$stmt = database::$conn->prepare( 'SELECT * FROM atm_logging WHERE code= ?' );
		$res  = $stmt->execute( [ $code ] );
		$res  = $stmt->fetch( PDO::FETCH_ASSOC );

		return $res;
	}

	/**
	 * This will insert a chit in the db.
	 *
	 * @param  mixed $post The posted arguments.
	 * @return string
	 */
	public function generate_chit( $post ) {
		$amount      = $post['amount'];
		$withdraw_id = $post['withdraw_id'];
		$create_date = time();
		$string      = $amount . ' - ' . $withdraw_id . ' - ' . $create_date;
		$code        = sha1( $string );

		$stmt   = database::$conn->prepare(
			'INSERT INTO 
			atm_logging (code, amount, withdraw_id, create_date) values (?, ?, ?, ?)'
		);
		$result = $stmt->execute( [ $code, $amount, $withdraw_id, $create_date ] );

		$return = [];
		if ( $result === true ) {
			$return['succes'] = true;
			$return['code']   = $code;
			$return['amount'] = $amount;

			$stmt   = database::$conn->prepare(
				'INSERT INTO 
				bank_logging (character_id, amount, id_to, description) values (?, ?, ?, ?)'
			);
			$result = $stmt->execute( [ $withdraw_id, $amount, 225, 'Generate Sonuren Chit' ] );

			return json_encode( $return );
		}

		$return['succes'] = false;
		return json_encode( $return );
	}

	/**
	 * Updates the scanned chit if needed.
	 *
	 * @param  mixed $post the scanned chit.
	 * @return string
	 */
	public function scan_chit( $post ) {
		$deposit_id   = $post['deposit_id'];
		$code         = $post['code'];
		$deposit_date = time();

		$atm_row = $this->check_if_code_exist( $code );

		$return['success'] = false;
		$return['message'] = 'Chit not found.';

		if ( isset( $atm_row['deposit_id'] ) ) {
			if ( $atm_row['deposit_id'] === '0' ) {

				$atm_update = database::$conn->prepare( 'UPDATE atm_logging SET deposit_id = ?, deposit_date = ? WHERE id = ?' );
				$res        = $atm_update->execute( [ $deposit_id, $deposit_date, $atm_row['id'] ] );

				$amount      = $atm_row['amount'];
				$from        = '225';
				$recipient   = $deposit_id;
				$description = 'Scanned Sonuren chit';

				$stmt   = database::$conn->prepare(
					'INSERT INTO 
					bank_logging (character_id, id_to, amount, description) values (?, ?, ?, ?)'
				);
				$result = $stmt->execute( [ $from, $recipient, $amount, $description ] );

				$return['success'] = true;
				$return['message'] = 'Sonuren has been deposited.';
			}

			if ( $atm_row['deposit_id'] !== '0' ) {
				$return['success'] = false;
				$return['message'] = 'Chit already scanned.';
			}
		}

		return json_encode( $return );
	}
}
