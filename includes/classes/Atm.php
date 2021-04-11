<?php

class Atm {

	/**
	 * Transfer
	 *
	 * @param  mixed $post
	 * @return void
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
			$result = $stmt->execute( [ $withdraw_id, $amount, 99999, 'Generate Sonuren Chit' ] );

			return json_encode( $return );
		}

		$return['succes'] = false;
		return json_encode( $return );
	}
}
