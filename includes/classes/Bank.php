<?php

class Bank {

	/**
	 * Get_amount_by_id returns the the amount of sonuren someone has based on character ID
	 *
	 * @param  mixed $id
	 * @return string
	 */
	public function get_amount_by_id( $id ): string {
		$stmt     = Database::$conn->prepare( 'SELECT SUM(amount) AS total FROM bank_logging WHERE character_id=?' );
		$res      = $stmt->execute( [ $id ] );
		$negative = $stmt->fetch( PDO::FETCH_ASSOC );

		$stmt     = Database::$conn->prepare( 'SELECT SUM(amount) AS total FROM bank_logging WHERE id_to=?' );
		$res      = $stmt->execute( [ $id ] );
		$positive = $stmt->fetch( PDO::FETCH_ASSOC );

		$amount = ( $positive['total'] - $negative['total'] );

		return intval( $amount );
	}

	/**
	 * Get_all_recipients get all viable bank recipients
	 *
	 * @return array
	 */
	public function get_all_recipients() {
		$stmt       = Database::$conn->prepare( 'SELECT * FROM ecc_characters WHERE bank = 1 ORDER BY character_name' );
		$recipients = $stmt->execute();
		$recipients = $stmt->fetchAll( PDO::FETCH_ASSOC );

		return $recipients;
	}

	/**
	 * Get_mutations get a list of al mutations from a character by ID
	 *
	 * @param  mixed $id
	 * @return array
	 */
	public function get_mutations( $id ): array {
		$stmt      = database::$conn->prepare( 'SELECT * FROM bank_logging WHERE character_id=? or id_to=? ORDER BY id DESC' );
		$mutations = $stmt->execute( [ $id, $id ] );
		$mutations = $stmt->fetchAll( PDO::FETCH_ASSOC );

		return $mutations;
	}

	/**
	 * Transfer
	 *
	 * @param  mixed $post
	 * @return void
	 */
	public function transfer( $post ) {
		$amount      = $post['amount'];
		$from        = $post['from'];
		$recipient   = $post['recipient'];
		$description = $post['description'];

		$stmt   = database::$conn->prepare(
			'INSERT INTO
			bank_logging (character_id, id_to, amount, description) values (?, ?, ?, ?)'
		);
		$result = $stmt->execute( [ $from, $recipient, $amount, $description ] );

		return str_replace( PHP_EOL, '', 'success' );
	}
}
