<?php
//Get All Skills
if ( isset( $input['category'] ) && $input['category'] == 'all' ) {
	$all_skills = $c_fetch->get_all_skills();
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
		$selected_skills = $c_fetch->get_skills_by_category($selected_category);
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