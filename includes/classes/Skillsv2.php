<?php

class SkillsV2 {
	public function get_all_skills($include_disabled) {
		if ( $include_disabled == 'do_not_include_disabled') {
			$where_clause = 'WHERE STATUS NOT LIKE "disabled"';
		}
		if ($include_disabled == 'include_disabled') {
			$where_clause = '';
		}
		$stmt     = Database::$conn->prepare( "SELECT sk.skill_id, sk.label, sk.skill_index,  sk.level, sk.version, sk.description,
		sk.parent AS parent_id, parent.name AS parent_name, parent.siteindex as parent_shortname, parent.psychic, parent.parents as grandparents, parent.`status`
		FROM ecc_skills_allskills sk
		LEFT JOIN ecc_skills_groups parent ON sk.parent = parent.primaryskill_id
		$where_clause
		ORDER BY parent.name, sk.level;" );
		$res      = $stmt->execute();
		$a_skills  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $a_skills;
	}
	public function get_skills_by_category($include_disabled, $category) {
		if ( $include_disabled == 'do_not_include_disabled') {
			$where_clause = 'STATUS NOT LIKE "disabled" AND';
		}
		if ($include_disabled == 'include_disabled') {
			$where_clause = '';
		}
		$stmt     = Database::$conn->prepare( "SELECT sk.skill_id, sk.label, sk.skill_index,  sk.level, sk.version, sk.description,
		sk.parent AS parent_id, parent.name AS parent_name, parent.siteindex as parent_shortname, parent.psychic, parent.parents as grandparents, parent.`status`
		FROM ecc_skills_allskills sk
		LEFT JOIN ecc_skills_groups parent ON sk.parent = parent.primaryskill_id
		WHERE $where_clause parent.siteindex = '$category'
		ORDER BY sk.level;" );
		$res      = $stmt->execute();
		$a_skills  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $a_skills;
	}
	public function get_skill($include_disabled, $id) {
		if ( $include_disabled == 'do_not_include_disabled') {
			$where_clause = "STATUS NOT LIKE 'disabled' AND";
		}
		if ($include_disabled == 'include_disabled') {
			$where_clause = '';
		}
		$stmt     = Database::$conn->prepare( "SELECT sk.skill_id, sk.label, sk.skill_index,  sk.level, sk.version, sk.description,
		sk.parent AS parent_id, parent.name AS parent_name, parent.siteindex as parent_shortname, parent.psychic, parent.parents as grandparents, parent.`status`
		FROM ecc_skills_allskills sk
		LEFT JOIN ecc_skills_groups parent ON sk.parent = parent.primaryskill_id
		WHERE $where_clause sk.skill_id = '$id'
		ORDER BY sk.level;" );
		$res      = $stmt->execute();
		$a_skills  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $a_skills;
	}
}