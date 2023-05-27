<?php

class Char_Figu {

	public function get_all_active() {
		$stmt = Database::$conn->prepare(
			"SELECT DISTINCT c.*, replace(replace(replace(CONCAT(r.first_name, ' ', COALESCE(v6.field_value,''),' ', r.last_name),' ','<>'),'><',''),'<>',' ') as figu_name FROM ecc_characters c
		LEFT JOIN jml_users u ON u.id = c.figu_accountID
		left JOIN jml_eb_registrants r ON u.id = r.user_id
		LEFT join jml_eb_field_values v5 on (v5.registrant_id = r.id and v5.field_id = 14)
		left join jml_eb_field_values v6 on (v6.registrant_id = r.id and v6.field_id = 16)
		WHERE status LIKE 'figurant%' AND sheet_status != 'deleted'"
		);
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get_all() {
		$stmt = Database::$conn->prepare(
			"SELECT DISTINCT c.*, replace(replace(replace(CONCAT(r.first_name, ' ', COALESCE(v6.field_value,''),' ', r.last_name),' ','<>'),'><',''),'<>',' ') as figu_name FROM ecc_characters c
		LEFT JOIN jml_users u ON u.id = c.figu_accountID
		left JOIN jml_eb_registrants r ON u.id = r.user_id
		LEFT join jml_eb_field_values v5 on (v5.registrant_id = r.id and v5.field_id = 14)
		left join jml_eb_field_values v6 on (v6.registrant_id = r.id and v6.field_id = 16) 
		WHERE status LIKE 'figurant%'"
		);
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get( $id, $needle ) {
		if ( $needle == 'card_id' ) {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id = ? AND status LIKE 'figurant%'" );
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

				$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id LIKE ? AND status LIKE 'figurant%'" );
				$res  = $stmt->execute( [ $s_dec ] );
				$res  = $stmt->fetch( PDO::FETCH_ASSOC );
			}
		}
		else {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND status LIKE 'figurant%'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		}

		return $res;
	}

	public function get_active( $id, $needle ) {
		if ( $needle == 'card_id' ) {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id = ? AND status LIKE 'figurant%' AND sheet_status != 'deleted'" );
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

				$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE card_id LIKE ? AND status LIKE 'figurant%' AND sheet_status NOT LIKE 'deleted'" );
				$res  = $stmt->execute( [ $s_dec ] );
				$res  = $stmt->fetch( PDO::FETCH_ASSOC );
			}
		}
		else {
			$stmt = Database::$conn->prepare( "SELECT * FROM ecc_characters where $needle = ? AND status LIKE 'figurant%' AND sheet_status NOT LIKE 'deleted'" );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
		}

		return $res;
	}

	public function get_skills( $id ) {
		$stmt          = Database::$conn->prepare( 'SELECT skill_id FROM ecc_char_skills where charID = ? ORDER BY skill_ID' );
		$res           = $stmt->execute( [ $id ] );
		$a_char_skills = $stmt->fetchAll( PDO::FETCH_ASSOC );

		$s_skillid = '';

		foreach ( $a_char_skills as $a_char_skill ) {
			$s_skillid .= $a_char_skill['skill_id'] . ',';
		}

		$s_skillid = rtrim( $s_skillid, ',' );



		$stmt          = Database::$conn->prepare( "SELECT label, skill_index, level FROM ecc_skills_allskills WHERE skill_id IN ($s_skillid)" );
		$res           = $stmt->execute();
		$a_char_skills = $stmt->fetchAll( PDO::FETCH_ASSOC );

		$a_skills = [];

		foreach ( $a_char_skills as $a_char_skill ) {

			$a_char_skill['skill_index'] = substr( $a_char_skill['skill_index'], 0, strpos( $a_char_skill['skill_index'], '_' ) );

			array_push( $a_skills, $a_char_skill );
		}

		$stmt       = Database::$conn->prepare( "SELECT type, skillgroup_siteindex, skillgroup_level, description FROM ecc_char_implants WHERE charID = ? AND status = 'active' AND type != 'flavour'" );
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

	public function add_figurant( $character ) {
		$check = $this->check_card_id( $character['card_id'] );
		if ( $check ) {
			if ( stripos( $check['status'], 'figurant' ) === false ) {
				echo json_encode( 'Cannot assign player card to figurant!' );
				http_response_code( 400 );
				die;
			}
		}
			$character_name     = $character['character_name'];
			$card_id            = $character['card_id'];
			$faction            = $character['faction'];
			$rank 				= !empty($character['rank']) ? $character['rank'] : "NULL";
			$threat_assessment  = $character['threat_assessment'];
			$douane_disposition = $character['douane_disposition'];
			$douane_notes       = $character['douane_notes'];
			$bastion_clearance  = $character['bastion_clearance'];
			$icc_number         = $character['icc_number'];
			$bloodtype          = $character['bloodtype'];
			$ic_birthday        = $character['ic_birthday'];
			$homeplanet         = $character['homeplanet'];
			$plotname 			= !empty($character['plotname']) ? $character['plotname'] : "NULL";
			$figustatus 		= 'figurant';

		if ( isset( $character['recurring'] ) ) {
			if ( $character['recurring'] == true ) {
				$figustatus = 'figurant-recurring';
			}
		}
		if ( isset( $character['figu_accountID'] ) ) {
			$figu_account_id = $character['figu_accountID'];
		}
		else {
			$figu_account_id = null;
		}

		if ( ! $check ) {

			$stmt = Database::$conn->prepare(
				'INSERT into ecc_characters
                    (accountID, character_name, card_id, faction, status, rank, threat_assessment, douane_disposition, douane_notes, bastion_clearance, icc_number, bloodtype, ic_birthday, homeplanet, figu_accountID, plotname)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
					$figu_account_id,
					$plotname,
				]
			);

			$last_insert_id = Database::$conn->lastInsertId();
			if ( $last_insert_id === '0' ) {
				$error = $stmt->errorInfo();
				return $error['2'];
			}
			return $last_insert_id;
		}
		else {
			$stmt = Database::$conn->prepare( 'UPDATE ecc_characters SET card_id=? WHERE characterID = ?' );
			$res  = $stmt->execute( [ null, $check['characterID'] ] );

			$stmt    = Database::$conn->prepare(
				'INSERT into ecc_characters
                        (accountID, character_name, card_id, faction, status, rank, threat_assessment, douane_disposition, douane_notes, bastion_clearance, icc_number, bloodtype, ic_birthday, homeplanet, figu_accountID, plotname)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
			);
				$res = $stmt->execute(
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
						$figu_account_id,
						$plotname,
					]
				);

				return Database::$conn->lastInsertId();
		}
	}

	public function put_figurant( $id, $character ) {
		$count = 0;
		foreach ( $character as $key => $value ) {
			if ( $key === 'card_id' ) {
				$check = $this->check_card_id( $value );
				if ( $check ) {
					if ( stripos( $check['status'], 'figurant' ) === false ) {
						echo json_encode( 'Cannot assign player card to figurant!' );
						http_response_code( 400 );
						die;
					}
					elseif ( $check['characterID'] === $id ) {
						$count += 0;
					}
					else {
						$existing_assignment = $check['characterID'];
						$stmt_cardid         = Database::$conn->prepare( "UPDATE `ecc_characters` SET $key = NULL WHERE `characterID` = $existing_assignment;" );
						$res_cardid          = $stmt_cardid->execute();
						$count              += $stmt_cardid->rowCount();
						$stmt_cardid2        = Database::$conn->prepare( "UPDATE `ecc_characters` SET $key = '$value' WHERE `characterID` = '$id'" );
						$res_cardid2         = $stmt_cardid2->execute();
						$count              += $stmt_cardid2->rowCount();
					}
				}


				elseif ( ! $check ) {
					$stmt_cardid = Database::$conn->prepare( "UPDATE `ecc_characters` SET $key = '$value' WHERE characterID = $id" );
					$res_cardid  = $stmt_cardid->execute();
					$count      += $stmt_cardid->rowCount();
				}
			}
			elseif ( $key === 'recurring' && ( $value === true || $value === 'true' ) ) {
				$stmt_recur = Database::$conn->prepare( "UPDATE `ecc_characters` SET `status` = 'figurant-recurring' WHERE `characterID` = '$id'" );
				$res_recur  = $stmt_recur->execute();
				$count     += $stmt_recur->rowCount();
			}
			elseif ( $key === 'recurring' && ( $value !== true || $value !== 'true' ) ) {
				$stmt_recur = Database::$conn->prepare( "UPDATE `ecc_characters` SET `status` = 'figurant' WHERE `characterID` = '$id'" );
				$res_recur  = $stmt_recur->execute();
				$count     += $stmt_recur->rowCount();
			}
			elseif ( $key === 'figu_accountID' && $value === 'null' ) {
				$stmt_figu = Database::$conn->prepare( "UPDATE `ecc_characters` SET $key = NULL WHERE `characterID` = '$id'" );
				$res_figu  = $stmt_figu->execute();
				$count    += $stmt_figu->rowCount();
			}
			else {
				$stmt   = Database::$conn->prepare( "UPDATE `ecc_characters` SET `$key` = '$value' WHERE `characterID` = '$id'" );
				$res    = $stmt->execute();
				$count += $stmt->rowCount();
			}
		}

		return $count;
	}

	public function delete_figurant( $id ) {
		 $stmt = Database::$conn->prepare( "UPDATE ecc_characters SET sheet_status = 'deleted', card_id = NULL WHERE status LIKE 'figurant%' AND characterID = $id  AND sheet_status != 'deleted'" );
		$res   = $stmt->execute();
		$count = $stmt->rowCount();
		if ( $count > 0 ) {
			$stmt2 = Database::$conn->prepare( "INSERT INTO ecc_meta_character(character_id,name,value) VALUES($id,'deleted_date',UNIX_TIMESTAMP());" );
			$res2  = $stmt2->execute();
		}
		return $count;
	}

	public function restore_figurant( $id ) {
		$stmt  = Database::$conn->prepare( "UPDATE ecc_characters SET sheet_status = 'active' WHERE status LIKE 'figurant%' AND characterID = $id  AND sheet_status = 'deleted'" );
		$res   = $stmt->execute();
		$count = $stmt->rowCount();
		if ( $count > 0 ) {
			$stmt2 = Database::$conn->prepare( "DELETE FROM ecc_meta_character where character_id = $id and name = 'deleted_date'" );
			$res2  = $stmt2->execute();
		}
		return $count;
	}

	private function check_card_id( $card_id ) {
		$stmt = Database::$conn->prepare( 'SELECT * FROM ecc_characters WHERE card_id = ?' );
		$res  = $stmt->execute( [ $card_id ] );
		$res  = $stmt->fetch( PDO::FETCH_ASSOC );

		return $res;
	}
}
