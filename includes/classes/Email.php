<?php

class Email {
	public function get_email( $id ) {
			$query = "SELECT `email` FROM `jml_users` u
			LEFT JOIN ecc_characters c
			on u.id = c.accountID
			where c.characterID = $id";

		$stmt = Database::$conn->prepare($query);
		$res  = $stmt->execute();
		$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		return $res;
	}
}
