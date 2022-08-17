<?php
$faction = $input['faction'];

switch ( $faction ) {
	case 'aquila':
		// Get the contents of the JSON file
		$str_json_file_contents = file_get_contents( '/var/eos_apps/PersonaGenerator/namelists/aquila.json' );
		echo $str_json_file_contents;
		break;
	case 'dugo':
		// Get the contents of the JSON file
		$str_json_file_contents = file_get_contents( '/var/eos_apps/PersonaGenerator/namelists/dugo.json' );
		echo $str_json_file_contents;
		break;
	case 'ekanesh':
		// Get the contents of the JSON file
		$str_json_file_contents = file_get_contents( '/var/eos_apps/PersonaGenerator/namelists/ekanesh.json' );
		echo $str_json_file_contents;
		break;
	case 'pendzal':
		// Get the contents of the JSON file
		$str_json_file_contents = file_get_contents( '/var/eos_apps/PersonaGenerator/namelists/pendzal.json' );
		echo $str_json_file_contents;
		break;
	case 'sona':
		// Get the contents of the JSON file
		$str_json_file_contents = file_get_contents( '/var/eos_apps/PersonaGenerator/namelists/sona.json' );
		echo $str_json_file_contents;
		break;
}

