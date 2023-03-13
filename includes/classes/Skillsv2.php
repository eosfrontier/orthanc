<?php

class charSkillsv2 {

	public function get_char_type_by_id( $id ) {
		$stmt = Database::$conn->prepare( 'SELECT status FROM ecc_characters WHERE characterID = ? AND sheet_status != "deleted"' );
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchColumn();

		return $res;
	}

	public function get_skills_all_chars() {
		$response = [];
		$stmt     = Database::$conn->prepare( 'SELECT characterID, sheet_status FROM ecc_characters ORDER BY characterID' );
		$res      = $stmt->execute();
		$a_chars  = $stmt->fetchAll( PDO::FETCH_ASSOC );

		foreach ( $a_chars as $a_char ) {
			$sheet_type = $this->get_char_type_by_id( $a_char['characterID'] );
			if ( ( ! strpos( $sheet_type, 'figurant' ) !== false ) && $a_char['sheet_status'] != 'deleted' ) {
				$result        = new stdClass();
				$getcharskills = $this->get_skills( $a_char['characterID'] );
				$response      = ( $response + $getcharskills );
			}
		}
		return $response;
	}

	public function get_skills( $id ) {
		$response = [];
		$stmt     = Database::$conn->prepare( 'SELECT * FROM ecc_char_skills_v2 WHERE charID = ? ORDER BY skill_ID' );
		$res      = $stmt->execute( [ $id ] );
		if ( $stmt->rowCount() < 1 ) {
			$response = [ 'http_response' => '404' ];
			return $response;
			die();
		}
		$a_char_skills = $stmt->fetchAll( PDO::FETCH_ASSOC );
		$results       = [];
		foreach ( $a_char_skills as $a_char_skill ) {
			$result = new stdClass();
			$level  = $a_char_skill['level'];
			if ( $level > 5 ) {
				$skilllevel = ( $level - 5 );
			}
			else {
				$skilllevel = $level;
			}
			$stmt2 = Database::$conn->prepare( 'SELECT * from ecc_skills WHERE skill_id = ' . $a_char_skill['skill_id'] );
			$res2  = $stmt2->execute();
			$res2  = $stmt2->fetcHAll( PDO::FETCH_ASSOC );
			foreach ( $res2 as $skill ) {
				$result->name  = $skill['name'];
				$result->level = $level;
				// $result->level_name = $skill["level_".$skilllevel."_name"]; // save for skill detail fetch
				$result->psychic = $skill['psychic'];
				$result->status  = $skill['status'];
				if ( $level > 5 ) {
					$result->specialty = true;
				}
				else {
					$result->specialty = false;
				}
				// $result->description =  $skill["level_".$skilllevel."_description"]; // save for skill detail fetch
				$result->parents = $skill['parents'];
				$results         = ( $results + [ $a_char_skill['skill_id'] => $result ] );
			}
		}
		$response = [ $id => $results ];
		return $response;
	}

	public function del_skill( $char_id, $skill_id ) {
		$stmtfinal = Database::$conn->prepare( "DELETE FROM ecc_char_skills_v2 WHERE charID = $char_id AND skill_id = $skill_id" );
		$resfinal  = $stmtfinal->execute();
		$count     = $stmtfinal->rowCount();
		return $count;
	}
}
