<?php

class meta {

	function get_char_type_by_id( $id ) {
		$stmt = database::$conn->prepare( 'SELECT status FROM ecc_characters WHERE characterID = ? AND sheet_status != "deleted"' );
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchColumn();

		return $res;
	}

	function get_all_meta_by_id( $id ) {
		$stmt = database::$conn->prepare( 'SELECT id, character_id, name, value FROM ecc_meta_character WHERE character_id = ?' );
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );

		return $res;
	}

	function get_by_meta_name( $id, $meta_name, $operator = 'in' ) {
		$stmt = database::$conn->prepare( "SELECT id, character_id, name, value FROM ecc_meta_character WHERE character_id = ? AND name $operator ($meta_name)" );
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	function get_all_by_meta_name( $meta_name, $operator = 'in' ) {
		$stmt = database::$conn->prepare( "SELECT id, character_id, name, value, character_id FROM ecc_meta_character WHERE name $operator ($meta_name)" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	function put_metas( $id, $metas ) {
		foreach ( $metas as $meta ) {
			$stmt  = database::$conn->prepare( 'SELECT id,  character_id, name, value FROM ecc_meta_character WHERE character_id = ? AND name = ?' );
			$res   = $stmt->execute( [ $id, $meta['name'] ] );
			$count = $stmt->rowCount();
			if ( $count > 0 ) {
				$stmt = database::$conn->prepare( 'UPDATE ecc_meta_character SET value=? WHERE character_id = ? AND name = ?' );
				$res  = $stmt->execute( [ $meta['value'], $id, $meta['name'] ] );
			}else {
				return '404';
			}
		}

		return 'success';
	}

	function patch_metas( $id, $metas ) {
		$response = array();
		foreach ( $metas as $meta ) {
			$result = new stdClass();
			$stmt  = database::$conn->prepare( 'SELECT id,  character_id, name, value FROM ecc_meta_character WHERE character_id = ? AND name = ?' );
			$res   = $stmt->execute( [ $id, $meta['name'] ] );
			$count = $stmt->rowCount();
			if ( $count > 0 ) {
				$stmt = database::$conn->prepare('SELECT id, character_id, name, value from ecc_meta_character WHERE character_id = ? AND name = ?');
				$res = $stmt->execute([$id, $meta['name'] ] );
				$res  = $stmt->fetchall( PDO::FETCH_ASSOC );
				$res = $res[0];
				if ($meta['old_value'] == $res['value']){
					$stmt2 = database::$conn->prepare( 'UPDATE ecc_meta_character SET value=? WHERE character_id = ? AND name = ?' );
					$res2  = $stmt2->execute( [ $meta['value'], $id, $meta['name'] ] );
					$result->response = 'HTTP_200';
					$result->message = 'success';
					$response = $response + array($meta['name']=>$result);
				} else {
					$result->response = 'HTTP_422';
					$result->message = 'Meta Name: ' . $meta['name'] . ' for characterID ' . $id . ' current value does not match old_value provided for validation. ';
					$response = $response + array($meta['name']=>$result);
				}

			}else {
				$result = new stdClass();
				$result->response = 'HTTP_404';
				$result->message = 'No meta called ' . $meta['name'] . ' found for characterID ' . $id;
				$response = $response + array($meta['name']=>$result);
			}
		}

		return $response;
	}

	function post_metas( $id, $metas ) {
		$response = array();
		foreach ( $metas as $meta ) {
			$result = new stdClass();
			$stmt  = database::$conn->prepare( 'SELECT id,  character_id, name, value FROM ecc_meta_character WHERE character_id = ? AND name = ?' );
			$res   = $stmt->execute( [ $id, $meta['name'] ] );
			$count = $stmt->rowCount();
			if ( $count < 1 ) {
				$stmt = database::$conn->prepare( 'INSERT into ecc_meta_character (value, character_id, name) VALUES (?, ?, ?)' );
				$res  = $stmt->execute( [ $meta['value'], $id, $meta['name'] ] );
				$last_insert_id = database::$conn->lastInsertId();
				$result->response = 'HTTP_200';	
				$result->message = 'success';
				$response = $response + array($meta['name']=>$result);
			}else {
				$result->response = 'HTTP_422';
				$result->message = "CharacterId ". $id . ' already has a meta called '. $meta['name']. '. To update existing meta, use PUT or PATCH instead.';
				$response = $response + array($meta['name']=>$result);
			}
		}
		return $response;
	}

	function delete_meta( $id, $metas ) {
		$total_deleted = 0;
		foreach ( $metas as $meta ) {
			if ( array_key_exists( 'value', $meta ) ) {
				$stmt = database::$conn->prepare( 'DELETE FROM ecc_meta_character WHERE character_id = ? AND name = ? AND value = ?' );
				$res  = $stmt->execute( [ $id, $meta['name'], $meta['value'] ] );
			} else {
				$stmt = database::$conn->prepare( 'DELETE FROM ecc_meta_character WHERE character_id = ? AND name = ?' );
				$res  = $stmt->execute( [ $id, $meta['name'] ] );
			}
			$count = $stmt->rowCount();
			if ( $count > 0 ) {
				$total_deleted += $count;


			}
		}

		if ( $total_deleted > 0 ) {
			return 'success';
		} else {
			return 'nothing deleted';
		}
	}
}
