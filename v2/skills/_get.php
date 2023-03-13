<?php
//Get All Skills
if ( isset( $input['category'] ) && $input['category'] == 'all' ) {
	if (isset ($input['include_disabled'])){
		$all_skills = $c_fetch->get_all_skills('include_disabled');
	}
	else {
		$all_skills = $c_fetch->get_all_skills('do_not_include_disabled');
	}
	if ( empty( $all_skills ) ) {
		http_response_code( 404 );
		echo json_encode( 'None found.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $all_skills );
	die();
}

// Get Skills by Category
if ( isset( $input['category'] ) && $input['category'] != 'all' ) {
	$selected_category = $input['category'];
	$stmt     = Database::$conn->prepare("SELECT siteindex from ecc_skills_groups WHERE siteindex LIKE '$selected_category';");
	$res      = $stmt->execute();
	$this_category  = $stmt->fetchAll( PDO::FETCH_ASSOC );
	if (count( $this_category ) == 0){
	$stmt2     = Database::$conn->prepare("SELECT siteindex from ecc_skills_groups ORDER by siteindex;");
	$res2      = $stmt2->execute();
	$all_categories  = $stmt2->fetchAll( PDO::FETCH_ASSOC );
	$categories_response = "Invalid category '$selected_category' selected. Valid options are all";
	foreach ($all_categories as $category) {
		$categories_response = $categories_response . ", " . $category['siteindex'];
		}
	echo json_encode($categories_response);
	http_response_code( 400 );
	die();
	}
	else {
		if (isset ($input['include_disabled'])){
			$selected_skills = $c_fetch->get_skills_by_category('include_disabled',$selected_category);
		}
		else {
			$selected_skills = $c_fetch->get_skills_by_category('do_not_include_disabled',$selected_category);
		}
		if ( empty( $selected_skills ) ) {
			http_response_code( 404 );
			echo json_encode( 'None found.' );
			die();
		}
		http_response_code( 200 );
		echo json_encode( $selected_skills );
		die();
	}
}

// Get Skills by ID
if ( isset( $input['skill_id'] ) ) {
	if (isset ($input['include_disabled'])){
		$skill = $c_fetch->get_skill('include_disabled', $input['skill_id']);
	}
	else {
		$skill = $c_fetch->get_skill('do_not_include_disabled', $input['skill_id']);
	}
	if ( empty( $skill ) ) {
		http_response_code( 404 );
		echo json_encode( 'Invalid skill id.' );
		die();
	}
	http_response_code( 200 );
	echo json_encode( $skill );
	die();
}