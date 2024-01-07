<?php

class Event {

	public function get_eventid( $which ) {
		switch ( $which ) {
			case 'current':
				$stmt = Database::$conn->prepare(
					"SELECT e.id from jml_eb_events e
				JOIN jml_eb_event_categories c ON (c.event_id = e.id)
				WHERE SUBSTRING_INDEX(event_end_date,' ',1) >= CURDATE() AND c.category_id = 1 ORDER BY SUBSTRING_INDEX(event_date,' ',1) ASC LIMIT 1;"
				);
				$res  = $stmt->execute();
				$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
				return ( $res['0'] )['id'];
			case 'next':
				$stmt = Database::$conn->prepare(
					"SELECT e.id FROM jml_eb_events e
				JOIN jml_eb_event_categories c ON (c.event_id = e.id)
				WHERE SUBSTRING_INDEX(event_end_date,' ',1) >= CURDATE() AND c.category_id = 1 ORDER BY SUBSTRING_INDEX(event_date,' ',1) ASC LIMIT 1,1;"
				);
				$res  = $stmt->execute();
				$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
				return ( $res['0'] )['id'];
		}
	}

	public function get_event( $which ) {
		$eventid = $this->get_eventid( $which );
		$stmt    = Database::$conn->prepare(
			"SELECT e.id, parent_id,  c.category_id, location_id, title, event_type, SUBSTRING_INDEX(event_date,' ',1) AS start_date, SUBSTRING_INDEX(event_end_date,' ',1) AS end_date
        from jml_eb_events e
        JOIN jml_eb_event_categories c ON (c.event_id = e.id)
        WHERE e.id = $eventid;"
		);
		$res     = $stmt->execute();
		$res     = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get_player_ids( $which ) {
		$eventid = $this->get_eventid( $which );
		$stmt    = Database::$conn->prepare(
			"SELECT SUBSTRING_INDEX(v1.field_value,' - ',-1)  as id from jml_eb_registrants r
			join joomla.jml_eb_field_values v1 on (v1.registrant_id = r.id and v1.field_id = 21)
			join jml_eb_field_values v5 on (v5.registrant_id = r.id and v5.field_id = 14)
			where v5.field_value = 'Speler' AND r.event_id = $eventid and ((r.published = 1 AND (r.payment_method = 'os_bancontact' OR r.payment_method = 'os_ideal' OR r.payment_method = 'os_paypal')) OR 
			(r.published in (0,1) AND r.payment_method = 'os_offline'));"
		);
		$res     = $stmt->execute();
		$res     = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get_players( $which ) {
		$player_ids  = $this->get_player_ids( $which );
		$whereclause = '';
		foreach ( $player_ids as $player_id ) {
			$id          = $player_id['id'];
			$whereclause = $whereclause . 'characterID = ' . $id . ' OR ';
		}
		$whereclause = rtrim( $whereclause, ' OR ' );
		$stmt        = Database::$conn->prepare( "SELECT * FROM ecc_characters WHERE $whereclause;" );
		$res         = $stmt->execute();
		$res         = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get_figuranten( $which ) {
		$eventid = $this->get_eventid( $which );
		$stmt    = Database::$conn->prepare(
			"SELECT r.user_id, v5.field_value as POSITION,
		REPLACE(REPLACE(REPLACE(CONCAT(r.first_name, ' ', COALESCE(v6.field_value,''),' ', r.last_name),' ','<>'), '><',''),  '<>',' ') as NAME, 
		r.phone, r.email from jml_eb_registrants r
		left join joomla.jml_eb_field_values v5 on (v5.registrant_id = r.id and v5.field_id = 14)
		left join joomla.jml_eb_field_values v6 on (v6.registrant_id = r.id and v6.field_id = 16)
		left join joomla.jml_eb_field_values v7 on (v7.registrant_id = r.id and v7.field_id = 59)
			where ((v5.field_value != 'Speler' AND v7.field_value != 'No') or v5.field_value = 'Spelleider') 
			AND r.event_id = 15 and ((r.published = 1 AND (r.payment_method = 'os_ideal' OR r.payment_method = 'os_paypal')) OR 
			(r.published in (0,1) AND r.payment_method = 'os_offline')) ORDER BY POSITION	;"
		);
		$res     = $stmt->execute();
		$res     = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	public function get_sleeping( $which ) {
		$eventid = $this->get_eventid( $which );
		$stmt2   = Database::$conn->prepare(
			"SELECT r.id, SUBSTRING_INDEX(v1.field_value,' - ',1) as name, SUBSTRING_INDEX(v1.field_value,' - ',-1) as characterID, v2.field_value as building, v3.field_value as bastion_room, v4.field_value as tweede_room from jml_eb_registrants r
			join joomla.jml_eb_field_values v1 on (v1.registrant_id = r.id and v1.field_id = 21)
			join joomla.jml_eb_field_values v2 on (v2.registrant_id = r.id and v2.field_id = 36)
			join jml_eb_field_values v5 on (v5.registrant_id = r.id and v5.field_id = 14)
			left join joomla.jml_eb_field_values v3 on (v3.registrant_id = r.id and v3.field_id = 37)
			left join joomla.jml_eb_field_values v4 on (v4.registrant_id = r.id and v4.field_id = 38)
			where v5.field_value = 'Speler' AND r.event_id = $eventid and ((r.published = 1 AND (r.payment_method = 'os_ideal' OR r.payment_method = 'os_paypal')) OR 
			(r.published in (0,1) AND r.payment_method = 'os_offline')) AND v2.field_value NOT LIKE 'medische%'
			UNION
			/* This TSQL Statement Grabs Figuranten (with real bed), SLs and Keuken Crew */
			SELECT r.id, CONCAT(v5.field_value,' ',r.first_name, ' ', COALESCE(v6.field_value,''),' ', SUBSTRING(r.last_name,1,1),'.') as name, NULL as characterID, 'tweede gebouw' as building, 
			NULL as bastion_room, CONCAT(COALESCE(v4.field_value,''),COALESCE(v3.field_value,''),COALESCE(v8.field_value,'')) as tweede_room from jml_eb_registrants r
			left join joomla.jml_eb_field_values v3 on (v3.registrant_id = r.id and v3.field_id = 73)
			left join joomla.jml_eb_field_values v4 on (v4.registrant_id = r.id and v4.field_id = 72)
			left join joomla.jml_eb_field_values v5 on (v5.registrant_id = r.id and v5.field_id = 14)
			left join joomla.jml_eb_field_values v6 on (v6.registrant_id = r.id and v6.field_id = 16)
			left join joomla.jml_eb_field_values v7 on (v7.registrant_id = r.id and v7.field_id = 59)
			left join joomla.jml_eb_field_values v8 on (v8.registrant_id = r.id and v8.field_id = 38)
			where r.event_id = $eventid and ((v5.field_value != 'Speler' AND v7.field_value != 'No') or (v5.field_value = 'Keuken Crew' or v5.field_value = 'Spelleider')) and ((r.published = 1 AND (r.payment_method = 'os_ideal' OR r.payment_method = 'os_paypal')) OR 
			(r.published in (0,1) AND r.payment_method = 'os_offline'))
			UNION
			/* This TSQL Statement grabs data for medical sleepers in the Bastion */
			SELECT r.id, SUBSTRING_INDEX(v1.field_value,' - ',1) as name, SUBSTRING_INDEX(v1.field_value,' - ',-1) as characterID, LEFT(v6.field_value,LOCATE(',',v6.field_value) - 1) as building, 
			substring_index(LEFT(v6.field_value,LOCATE(' - ',v6.field_value) - 1),',',-1) as bastion_room, 
			v4.field_value as tweede_room from jml_eb_registrants r
			join joomla.jml_eb_field_values v1 on (v1.registrant_id = r.id and v1.field_id = 21)
			left join joomla.jml_eb_field_values v3 on (v3.registrant_id = r.id and v3.field_id = 37)
			left join joomla.jml_eb_field_values v4 on (v4.registrant_id = r.id and v4.field_id = 38)
			left join joomla.jml_eb_field_values v6 on (v6.registrant_id = r.id and v6.field_id = 71)
			where LEFT(v6.field_value,LOCATE(',',v6.field_value) - 1) = 'Bastion' AND r.event_id = $eventid
			and ((r.published = 1 AND (r.payment_method = 'os_ideal' OR r.payment_method = 'os_paypal')) OR
			(r.published in (0,1) AND r.payment_method = 'os_offline'))
			UNION
			/* This TSQL Statement grabs data for medical sleepers in the tweede gebouw	*/
			SELECT r.id, SUBSTRING_INDEX(v1.field_value,' - ',1) as name, SUBSTRING_INDEX(v1.field_value,' - ',-1) as characterID, LEFT(v6.field_value,LOCATE(',',v6.field_value) - 1) as building, v3.field_value as bastion_room,
			substring_index(LEFT(v6.field_value,LOCATE(' - ',v6.field_value) - 1),',',-1) as tweede_room from jml_eb_registrants r
			join joomla.jml_eb_field_values v1 on (v1.registrant_id = r.id and v1.field_id = 21)
			left join joomla.jml_eb_field_values v3 on (v3.registrant_id = r.id and v3.field_id = 37)
			left join joomla.jml_eb_field_values v4 on (v4.registrant_id = r.id and v4.field_id = 38)
			left join joomla.jml_eb_field_values v6 on (v6.registrant_id = r.id and v6.field_id = 71)
			where LEFT(v6.field_value,LOCATE(',',v6.field_value) - 1) = 'tweede gebouw' AND r.event_id = $eventid
			and ((r.published = 1 AND (r.payment_method = 'os_ideal' OR r.payment_method = 'os_paypal')) OR
			(r.published in (0,1) AND r.payment_method = 'os_offline')) ORDER by length(tweede_room), tweede_room, length(bastion_room), bastion_room ASC;"
		);
		$res2    = $stmt2->execute();
		$res2    = $stmt2->fetchAll( PDO::FETCH_ASSOC );
		return $res2;
	}
}


