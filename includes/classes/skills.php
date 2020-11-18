<?php

class skills {

    function get_char_type_by_id( $id ) {
		$stmt = database::$conn->prepare( 'SELECT status FROM ecc_characters WHERE characterID = ? AND sheet_status != "deleted"');
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchColumn();

		return $res;
	}

    public function get_skills( $id ) {
		$stmt        = database::$conn->prepare( 'SELECT skill_id FROM ecc_char_skills where charID = ? ORDER BY skill_ID' );
		$res         = $stmt->execute( [ $id ] );
		$a_char_skills = $stmt->fetchAll( PDO::FETCH_ASSOC );

		$s_skillid = '';

		foreach ( $a_char_skills as $a_char_skill ) {
			$s_skillid .= $a_char_skill['skill_id'] . ',';
		}

		$s_skillid = rtrim( $s_skillid, ',' );



		$stmt        = database::$conn->prepare( "SELECT label, skill_index, level FROM ecc_skills_allskills WHERE skill_id IN ($s_skillid)" );
		$res         = $stmt->execute();
		$a_char_skills = $stmt->fetchAll( PDO::FETCH_ASSOC );

		$a_skills = [];

		foreach ( $a_char_skills as $a_char_skill ) {

			$a_char_skill['skill_index'] = substr( $a_char_skill['skill_index'], 0, strpos( $a_char_skill['skill_index'], '_' ) );

			array_push( $a_skills, $a_char_skill );
		}

		$stmt      = database::$conn->prepare( "SELECT type, skillgroup_siteindex, skillgroup_level, description FROM ecc_char_implants WHERE charID = ? AND status = 'active' AND type != 'flavour'" );
		$res       = $stmt->execute( [ $id ] );
		$a_implants = $stmt->fetchAll( PDO::FETCH_ASSOC );



		foreach ( $a_implants as $a_implant ) {
			$arr['level']       = $a_implant['skillgroup_level'];
			$arr['label']       = $a_implant['description'];
			$arr['skill_index'] = $a_implant['skillgroup_siteindex'];
			$arr['type']        = $a_implant['type'];
			array_push( $a_skills, $arr );
		}

        $a_listed_skills = [];
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
}