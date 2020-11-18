<?php

class character {

	public function get_all() {
		$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters WHERE status NOT LIKE 'figurant%' AND sheet_status != 'deleted'" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get( $id, $needle ) {
		if ( $needle == 'card_id' ) {
			$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id = ? AND status NOT LIKE 'figurant%' AND sheet_status != 'deleted'" );
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

				$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id LIKE ? AND status NOT LIKE 'figurant%' AND sheet_status != 'deleted'" );
				$res  = $stmt->execute( [ $s_dec ] );
				$res  = $stmt->fetch( PDO::FETCH_ASSOC );
			}
		} elseif ( $needle == 'accountID' ) {
			$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND status NOT LIKE 'figurant%' AND sheet_status != 'deleted'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		} else {
			$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND status NOT LIKE 'figurant%' AND sheet_status != 'deleted'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		}

		return $res;
	}

	function add_character( $account, $character ) {
		$check              = $this->check_card_id( $character['card_id'] );
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

		if ( ! $check ) {

			$stmt = database::$conn->prepare(
				'INSERT into ecc_characters
                    (accountID, character_name, card_id, faction, status, rank, threat_assessment, douane_disposition, douane_notes, bastion_clearance, icc_number, bloodtype, ic_birthday, homeplanet)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
			);
			$res  = $stmt->execute(
				[
					$account,
					$character_name,
					$card_id,
					$faction,
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

			return database::$conn->lastInsertId();
		} else {
			$stmt = database::$conn->prepare(
				'UPDATE ecc_characters SET
                        character_name=?,
                        faction=?,
                        status=?,
                        rank=?,
                        threat_assessment=?,
                        douane_disposition=?,
                        douane_notes=?,
                        bastion_clearance=?,
                        icc_number=?,
                        bloodtype=?,
                        ic_birthday=?,
                        homeplanet=?
                    WHERE characterID = ?'
			);
			$res  = $stmt->execute( [ $character_name, $faction, $rank, $threat_assessment, $douane_disposition, $douane_notes, $bastion_clearance, $icc_number, $bloodtype, $ic_birthday, $homeplanet, $check['characterID'] ] );

			return 'success';
		}
	}

	public function delete_character( $id ) {
		$stmt  = database::$conn->prepare( "UPDATE ecc_characters SET sheet_status = 'deleted', card_id = NULL WHERE status NOT LIKE 'figurant%' AND characterID = $id  AND sheet_status != 'deleted'" );
		$res   = $stmt->execute();
		$count = $stmt->rowCount();
		if ( $count > 0 ) {
			$stmt2 = database::$conn->prepare( "INSERT INTO ecc_meta_character(character_id,name,value) VALUES($id,'deleted_date',UNIX_TIMESTAMP());" );
			$res2  = $stmt2->execute();
		}
		return $count;
	}

	private function check_card_id( $card_id ) {
		$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id = ? AND status NOT LIKE 'figurant%' AND sheet_status != 'deleted'" );
		$res  = $stmt->execute( [ $card_id ] );
		$res  = $stmt->fetch( PDO::FETCH_ASSOC );

		return $res;
	}
}
