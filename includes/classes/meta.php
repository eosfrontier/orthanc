<?php

class meta {

	function get_char_type_by_id( $id ) {
		$stmt = database::$conn->prepare( 'SELECT status FROM ecc_characters WHERE characterID = ? AND sheet_status != "deleted"');
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchColumn();

		return $res;
	}
	function get_all_meta_by_id( $id ) {
		$stmt = database::$conn->prepare( 'SELECT id, name, value FROM ecc_meta_character WHERE character_id = ?' );
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );

		return $res;
	}

	function get_by_meta( $id, $meta ) {
		$stmt = database::$conn->prepare( "SELECT id, name, value FROM ecc_meta_character WHERE character_id = ? AND name in ($meta)" );
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	function get_all_by_meta( $meta ) {
		$stmt = database::$conn->prepare( "SELECT id, name, value, character_id FROM ecc_meta_character WHERE name in ($meta)" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	function update_meta( $id, $metas ) {
		foreach ( $metas as $meta ) {
			if ( array_key_exists( 'oldvalue', $meta ) ) {
				$stmt = database::$conn->prepare( 'SELECT id, name, value FROM ecc_meta_character WHERE character_id = ? AND name = ? AND value = ?' );
				$res  = $stmt->execute( [ $id, $meta['name'], $meta['oldvalue'] ] );
			} else {
				$stmt = database::$conn->prepare( 'SELECT id, name, value FROM ecc_meta_character WHERE character_id = ? AND name = ?' );
				$res  = $stmt->execute( [ $id, $meta['name'] ] );
			}
			$count = $stmt->rowCount();
			if ( $count > 0 ) {
				if ( array_key_exists( 'oldvalue', $meta ) ) {
					$stmt = database::$conn->prepare( 'UPDATE ecc_meta_character SET value=? WHERE character_id = ? AND name = ? AND value = ?' );
					$res  = $stmt->execute( [ $meta['value'], $id, $meta['name'], $meta['oldvalue'] ] );

				} else {
					$stmt = database::$conn->prepare( 'UPDATE ecc_meta_character SET value=? WHERE character_id = ? AND name = ?' );
					$res  = $stmt->execute( [ $meta['value'], $id, $meta['name'] ] );
				}
			}else {
				$stmt = database::$conn->prepare( 'INSERT into ecc_meta_character (value, character_id, name) VALUES (?, ?, ?)' );
				$res  = $stmt->execute( [ $meta['value'], $id, $meta['name'] ] );
			}
		}

		return 'success';
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
