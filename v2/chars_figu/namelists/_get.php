<?php
$faction = $input['faction'];

switch ( $faction ) {
	case 'aquila':
		// Get the contents of the JSON file 
		$strJsonFileContents = file_get_contents("/var/eos_apps/PersonaGenerator/namelists/aquila.json");
		echo $strJsonFileContents;
		break;
	case 'dugo':
		// Get the contents of the JSON file 
		$strJsonFileContents = file_get_contents("/var/eos_apps/PersonaGenerator/namelists/dugo.json");
		echo $strJsonFileContents;
		break;
	case 'ekanesh':
		// Get the contents of the JSON file 
		$strJsonFileContents = file_get_contents("/var/eos_apps/PersonaGenerator/namelists/ekanesh.json");
		echo $strJsonFileContents;
		break;
	case 'pendzal':
		// Get the contents of the JSON file 
		$strJsonFileContents = file_get_contents("/var/eos_apps/PersonaGenerator/namelists/pendzal.json");
		echo $strJsonFileContents;
		break;
	case 'sona':
		// Get the contents of the JSON file 
		$strJsonFileContents = file_get_contents("/var/eos_apps/PersonaGenerator/namelists/sona.json");
		echo $strJsonFileContents;
		break;
	}

