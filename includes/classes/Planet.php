<?php

class Planet {

	public function get_planets() {
		$stmt = Database::$conn->prepare( 'SELECT * FROM eos_starmap_planets ORDER BY name ASC' );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );

		return $res;
	}

	public function get_planets_with_portals() {
		$stmt = Database::$conn->prepare( 'SELECT * FROM eos_starmap_planets WHERE portal = 1 ORDER BY name ASC' );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );

		return $res;
	}
}
