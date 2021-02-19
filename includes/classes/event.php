<?php

class event {

    public function get_upcoming() {
		$stmt = database::$conn->prepare( "SELECT e.id, parent_id,  c.category_id, location_id, title, event_type, SUBSTRING_INDEX(event_date,' ',1) AS start_date, SUBSTRING_INDEX(event_end_date,' ',1) AS end_date
        FROM joomla.jml_eb_events e                                                          
        JOIN jml_eb_event_categories c ON (c.event_id = e.id)                                
        WHERE SUBSTRING_INDEX(event_end_date,' ',1) >= CURDATE() AND c.category_id = 1 ORDER BY start_date ASC LIMIT 1;" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}
    public function get_next() {
		$stmt = database::$conn->prepare( "SELECT e.id, parent_id,  c.category_id, location_id, title, event_type, SUBSTRING_INDEX(event_date,' ',1) AS start_date, SUBSTRING_INDEX(event_end_date,' ',1) AS end_date
        FROM joomla.jml_eb_events e                                                          
        JOIN jml_eb_event_categories c ON (c.event_id = e.id)                                
        WHERE SUBSTRING_INDEX(event_end_date,' ',1) >= CURDATE() AND c.category_id = 1 ORDER BY start_date ASC LIMIT 1,1;" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}
}