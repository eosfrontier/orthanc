<?php

class char_figu {

	public function get_all() {
		$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters WHERE status LIKE 'figurant%' AND sheet_status != 'deleted'" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get( $id, $needle ) {
		if ( $needle == 'card_id' ) {
			$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id = ? AND status LIKE 'figurant%' AND sheet_status != 'deleted'" );
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

				$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id LIKE ? AND status LIKE 'figurant%' AND sheet_status NOT LIKE 'deleted'" );
				$res  = $stmt->execute( [ $s_dec ] );
				$res  = $stmt->fetch( PDO::FETCH_ASSOC );
			}
		} elseif ( $needle == 'accountID' ) {
			$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND sheet_status = 'active' AND status LIKE 'figurant%' AND sheet_status NOT LIKE 'deleted'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		} else {
			$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND status LIKE 'figurant%' AND sheet_status NOT LIKE 'deleted'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		}

		return $res;
	}

	public function get_skills( $id ) {
		$stmt          = database::$conn->prepare( 'SELECT skill_id FROM ecc_char_skills where charID = ? ORDER BY skill_ID' );
		$res           = $stmt->execute( [ $id ] );
		$a_char_skills = $stmt->fetchAll( PDO::FETCH_ASSOC );

		$s_skillid = '';

		foreach ( $a_char_skills as $a_char_skill ) {
			$s_skillid .= $a_char_skill['skill_id'] . ',';
		}

		$s_skillid = rtrim( $s_skillid, ',' );



		$stmt          = database::$conn->prepare( "SELECT label, skill_index, level FROM ecc_skills_allskills WHERE skill_id IN ($s_skillid)" );
		$res           = $stmt->execute();
		$a_char_skills = $stmt->fetchAll( PDO::FETCH_ASSOC );

		$a_skills = [];

		foreach ( $a_char_skills as $a_char_skill ) {

			$a_char_skill['skill_index'] = substr( $a_char_skill['skill_index'], 0, strpos( $a_char_skill['skill_index'], '_' ) );

			array_push( $a_skills, $a_char_skill );
		}

		$stmt       = database::$conn->prepare( "SELECT type, skillgroup_siteindex, skillgroup_level, description FROM ecc_char_implants WHERE charID = ? AND status = 'active' AND type != 'flavour'" );
		$res        = $stmt->execute( [ $id ] );
		$a_implants = $stmt->fetchAll( PDO::FETCH_ASSOC );



		foreach ( $a_implants as $a_implant ) {
			$arr['level']       = $a_implant['skillgroup_level'];
			$arr['label']       = $a_implant['description'];
			$arr['skill_index'] = $a_implant['skillgroup_siteindex'];
			$arr['type']        = $a_implant['type'];
			array_push( $a_skills, $arr );
		}


		foreach ( $a_skills as $key => $item ) {
			$a_listed_skills[ $item['skill_index'] ][ $key ] = $item;
		}

		$a_skills = [];

		foreach ( $a_listed_skills as $a_listed_skill ) {

			$level = array_column( $a_listed_skill, 'level' );
			array_multisort( $level, SORT_ASC, $a_listed_skill );

			// var_dump($a_listed_skill);
			$group               = [];
			$group['name']       = '';
			$group['level']      = 0;
			$group['specialty']  = false;
			$group['sub_skills'] = [];

			foreach ( $a_listed_skill as $arr ) {
				$array = [
					'name'  => $arr['label'],
					'level' => intval( $arr['level'] ),
				];

				if ( isset( $arr['type'] ) ) {
					$array['source'] = $arr['type'];
				}

				array_push( $group['sub_skills'], $array );

				$group['name'] = $arr['skill_index'];

				if ( ! isset( $arr['type'] ) ) {
					if ( $arr['level'] > $group['level'] ) {
						$group['level'] = intval( $arr['level'] );
					}
				}
			}

			if ( $group['level'] > 5 ) {
				$group['specialty'] = true;
			}

			array_push( $a_skills, $group );
		}

		return $a_skills;
	}

	function add_figurant( $character ) {
		$check = $this->check_card_id( $character['card_id'] );

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

		$figustatus = 'figurant';
		if ( isset( $character['recurring'] ) ) {
			$figustatus = 'figurant-recurring';
		}

		if ( ! $check ) {

			$stmt = database::$conn->prepare(
				'INSERT into ecc_characters
                    (accountID, character_name, card_id, faction, status, rank, threat_assessment, douane_disposition, douane_notes, bastion_clearance, icc_number, bloodtype, ic_birthday, homeplanet)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
			);
			$res  = $stmt->execute(
				[
					0,
					$character_name,
					$card_id,
					$faction,
					$figustatus,
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

			$last_insert_id = database::$conn->lastInsertId();
			if ( $last_insert_id === '0' ) {
				$error = $stmt->errorInfo();
				return $error['2'];
			}
			return $last_insert_id;
		}
		else {
			if ( $check['status'] == 'figurant-recurring' ) {

				$stmt = database::$conn->prepare( 'UPDATE ecc_characters SET card_id=? WHERE characterID = ?' );
				$res  = $stmt->execute( [ null, $check['characterID'] ] );

				$stmt = database::$conn->prepare(
					'INSERT into ecc_characters
                        (accountID, character_name, card_id, faction, status, rank, threat_assessment, douane_disposition, douane_notes, bastion_clearance, icc_number, bloodtype, ic_birthday, homeplanet)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
				);
				$res  = $stmt->execute(
					[
						0,
						$character_name,
						$card_id,
						$faction,
						$figustatus,
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
				$res  = $stmt->execute( [ $character_name, $faction, $figustatus, $rank, $threat_assessment, $douane_disposition, $douane_notes, $bastion_clearance, $icc_number, $bloodtype, $ic_birthday, $homeplanet, $check['characterID'] ] );

				return 'success';
			}
		}
	}

	function put_figurant( $id, $character ) {
		$count = 0;
		foreach ($character as $key => $value) {
			if ($key == 'card_id') {
				$check = $this->check_card_id( $character['card_id'] );
			}
			
			$stmt = database::$conn->prepare("UPDATE `ecc_characters` SET `$key` = '$value' WHERE `characterID` = '$id'");
			$res  = $stmt->execute();
			$count += $stmt->rowCount();
		}
		return $count;
	}

	public function delete_figurant( $id ) {
		 $stmt = database::$conn->prepare( "UPDATE ecc_characters SET sheet_status = 'deleted', card_id = NULL WHERE status LIKE 'figurant%' AND characterID = $id  AND sheet_status != 'deleted'" );
		$res   = $stmt->execute();
		$count = $stmt->rowCount();
		if ( $count > 0 ) {
			$stmt2 = database::$conn->prepare( "INSERT INTO ecc_meta_character(character_id,name,value) VALUES($id,'deleted_date',UNIX_TIMESTAMP());" );
			$res2  = $stmt2->execute();
		}
		return $count;
	}

	public function restore_figurant( $id ) {
		$stmt  = database::$conn->prepare( "UPDATE ecc_characters SET sheet_status = 'active' WHERE status LIKE 'figurant%' AND characterID = $id  AND sheet_status = 'deleted'" );
		$res   = $stmt->execute();
		$count = $stmt->rowCount();
		if ( $count > 0 ) {
			$stmt2 = database::$conn->prepare( "DELETE FROM ecc_meta_character where character_id = $id and name = 'deleted_date'" );
			$res2  = $stmt2->execute();
		}
		return $count;
	}

	private function check_card_id( $card_id ) {
		$stmt = database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id = ? AND status like 'figurant%'" );
		$res  = $stmt->execute( [ $card_id ] );
		$res  = $stmt->fetch( PDO::FETCH_ASSOC );

		return $res;
	}
}
