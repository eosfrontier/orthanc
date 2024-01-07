<?php

class Char_Player {

	private function check_card_id( $card_id ) {
		$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id = ? AND status NOT LIKE 'figurant%' AND sheet_status = 'active'" );
		$res  = $stmt->execute( [ $card_id ] );
		$res  = $stmt->fetch( PDO::FETCH_ASSOC );

		return $res;
	}

	public function get_all() {
		$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE status NOT LIKE 'figurant%'" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get_all_active() {
		$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE status NOT LIKE 'figurant%' AND sheet_status = 'active' AND character_name IS NOT NULL" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get_all_active_no_backstory() {
		$stmt = Database::$conn->prepare( "SELECT c.* FROM ecc_characters c
		LEFT JOIN ecc_backstory b on (c.characterID = b.characterID)
		WHERE status NOT LIKE 'figurant%' AND sheet_status = 'active' AND ( b.backstory_status IS NULL OR b.backstory_status = 0 )  AND c.character_name IS NOT NULL
		ORDER by c.character_name" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get( $id, $needle ) {
		if ( $needle == 'card_id' ) {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id = ? AND status NOT LIKE 'figurant%'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );

			if ( $res == null ) {
				$s_hex = dechex( $id );
				$a_dec = str_split( $s_hex, 2 );
				if ( ! isset( $a_dec[1] ) ) {
					return 'false';
				}
				$s_dec = '%' . $a_dec[3] . $a_dec[2] . $a_dec[1] . $a_dec[0] . '%';
				if ( $s_dec == '%0%' ) {
					return 'false';
				}

				$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id LIKE ? AND status NOT LIKE 'figurant%'" );
				$res  = $stmt->execute( [ $s_dec ] );
				$res  = $stmt->fetch( PDO::FETCH_ASSOC );
			}
		}
		elseif ( $needle == 'accountID' ) {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND status NOT LIKE 'figurant%'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		}
		else {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND status NOT LIKE 'figurant%'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		}

		return $res;
	}

	public function get_active( $id, $needle ) {
		if ( $needle == 'card_id' ) {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id = ? AND status NOT LIKE 'figurant%' AND sheet_status = 'active'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );

			if ( $res == null ) {
				$s_hex = dechex( $id );
				$a_dec = str_split( $s_hex, 2 );
				if ( ! isset( $a_dec[1] ) ) {
					return 'false';
				}
				$s_dec = '%' . $a_dec[3] . $a_dec[2] . $a_dec[1] . $a_dec[0] . '%';
				if ( $s_dec == '%0%' ) {
					return 'false';
				}

				$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id LIKE ? AND status NOT LIKE 'figurant%' AND sheet_status = 'active'" );
				$res  = $stmt->execute( [ $s_dec ] );
				$res  = $stmt->fetch( PDO::FETCH_ASSOC );
			}
		}
		elseif ( $needle == 'accountID' ) {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND status NOT LIKE 'figurant%' AND sheet_status = 'active'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		}
		else {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND status NOT LIKE 'figurant%' AND sheet_status = 'active'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		}

		return $res;
	}

	public function add_character( $account, $character ) {
		// $check              = $this->check_card_id( $character['card_id'] );
		$character_name     = $character['character_name'];
		$card_id            = $character['card_id'];
		$faction            = $character['faction'];
		$rank               = $character['rank'];
		$threat_assessment  = $character['threat_assessment'];
		$douane_disposition = $character['douane_disposition'];
		$douane_notes       = $character['douane_notes'];
		$bastion_clearance  = $character['bastion_clearance'];
		$icc_number         = $character['icc_number'];
		$bloodtype          = $character['bloodtype'];
		$ic_birthday        = $character['ic_birthday'];
		$homeplanet         = $character['homeplanet'];
		$status             = 'active';

		// if ( ! $check ) {

			$stmt           = Database::$conn->prepare(
				'INSERT into ecc_characters
                    (accountID, character_name, card_id, faction, status, rank, threat_assessment, douane_disposition, douane_notes, bastion_clearance, icc_number, bloodtype, ic_birthday, homeplanet)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
			);
			$res            = $stmt->execute(
				[
					$account,
					$character_name,
					$card_id,
					$faction,
					$status,
					$rank,
					$threat_assessment,
					$douane_disposition,
					$douane_notes,
					$bastion_clearance,
					$icc_number,
					$bloodtype,
					$ic_birthday,
					$homeplanet,
				]
			);
			$last_insert_id = Database::$conn->lastInsertId();
		if ( $last_insert_id === '0' ) {
			$error = $stmt->errorInfo();
			return $error['2'];
		}
			return $last_insert_id;
	}

	public function put_character( $account, $id, $character ) {
		$count = 0;
		foreach ( $character as $key => $value ) {
			$stmt   = Database::$conn->prepare( "UPDATE `ecc_characters` SET `$key` = '$value' WHERE `characterID` = '$id' AND `accountID` = '$account'" );
			$res    = $stmt->execute();
			$count += $stmt->rowCount();
		}
		return $count;
	}

	public function patch_character( $account, $id, $character ) {
		$count = 0;
		foreach ( $character as $key => $value ) {
			$stmt = Database::$conn->prepare( "SELECT $key from `ecc_characters` WHERE `characterID` = '$id' AND `accountID` = '$account'" );
			$res  = $stmt->execute();
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
			// $stmt2 = Database::$conn->prepare("UPDATE `ecc_characters` SET `$key` = '$value' WHERE `characterID` = '$id' AND `accountID` = '$account' ");
			// $res2  = $stmt2->execute();
			// $count += $stmt2->rowCount();
		}
		return $res;
	}

	public function delete_character( $id ) {
		$stmt  = Database::$conn->prepare( "UPDATE ecc_characters SET sheet_status = 'deleted', card_id = NULL WHERE status NOT LIKE 'figurant%' AND characterID = $id  AND sheet_status = 'active'" );
		$res   = $stmt->execute();
		$count = $stmt->rowCount();
		if ( $count > 0 ) {
			$stmt2 = Database::$conn->prepare( "INSERT INTO ecc_meta_character(character_id,name,value) VALUES($id,'deleted_date',UNIX_TIMESTAMP());" );
			$res2  = $stmt2->execute();
		}
		return $count;
	}

	public function restore_character( $id ) {
		$stmt  = Database::$conn->prepare( "UPDATE ecc_characters SET sheet_status = 'active' WHERE status NOT LIKE 'figurant%' AND characterID = $id  AND sheet_status = 'deleted'" );
		$res   = $stmt->execute();
		$count = $stmt->rowCount();
		if ( $count > 0 ) {
			$stmt2 = Database::$conn->prepare( "DELETE FROM ecc_meta_character where character_id = $id and name = 'deleted_date'" );
			$res2  = $stmt2->execute();
		}
		return $count;
	}
}
