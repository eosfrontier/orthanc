<?php

class Options {

	function get_all_options_by_id( $id ) {
		$stmt = Database::$conn->prepare( 'SELECT id, app_id, name, value FROM ecc_app_options WHERE app_id = ?' );
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );

		return $res;
	}

	function get_by_option_name( $id, $option_name, $operator = 'in' ) {
		$stmt = Database::$conn->prepare( "SELECT id, app_id, name, value FROM ecc_app_options WHERE app_id = ? AND name $operator ($option_name)" );
		$res  = $stmt->execute( [ $id ] );
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	function get_all_by_option_name( $option_name, $operator = 'in' ) {
		$stmt = Database::$conn->prepare( "SELECT id, app_id, name, value, app_id FROM ecc_app_options WHERE name $operator ($option_name)" );
		$res  = $stmt->execute();
		$res  = $stmt->fetchAll( PDO::FETCH_ASSOC );
		return $res;
	}

	function put_options( $id, $options ) {
		foreach ( $options as $option ) {
			$stmt  = Database::$conn->prepare( 'SELECT id,  app_id, name, value FROM ecc_app_options WHERE app_id = ? AND name = ?' );
			$res   = $stmt->execute( [ $id, $option['name'] ] );
			$count = $stmt->rowCount();
			if ( $count > 0 ) {
				$stmt = Database::$conn->prepare( 'UPDATE ecc_app_options SET value=? WHERE app_id = ? AND name = ?' );
				$res  = $stmt->execute( [ $option['value'], $id, $option['name'] ] );
			}
			else {
				return '404';
			}
		}

		return 'success';
	}

	function patch_options( $id, $options ) {
		$response = [];
		foreach ( $options as $option ) {
			$result = new stdClass();
			$stmt   = Database::$conn->prepare( 'SELECT id,  app_id, name, value FROM ecc_app_options WHERE app_id = ? AND name = ?' );
			$res    = $stmt->execute( [ $id, $option['name'] ] );
			$count  = $stmt->rowCount();
			if ( $count > 0 ) {
				$stmt = Database::$conn->prepare( 'SELECT id, app_id, name, value from ecc_app_options WHERE app_id = ? AND name = ?' );
				$res  = $stmt->execute( [ $id, $option['name'] ] );
				$res  = $stmt->fetchall( PDO::FETCH_ASSOC );
				$res  = $res[0];
				if ( $option['old_value'] == $res['value'] ) {
					$stmt2            = Database::$conn->prepare( 'UPDATE ecc_app_options SET value=? WHERE app_id = ? AND name = ?' );
					$res2             = $stmt2->execute( [ $option['value'], $id, $option['name'] ] );
					$result->response = 'HTTP_200';
					$result->message  = 'success';
					$response         = ( $response + [ $option['name'] => $result ] );
				}
				else {
					$result->response = 'HTTP_422';
					$result->message  = 'Option Name: ' . $option['name'] . ' for AppID ' . $id . ' current value does not match old_value provided for validation. ';
					$response         = ( $response + [ $option['name'] => $result ] );
				}
			}
			else {
				$result           = new stdClass();
				$result->response = 'HTTP_404';
				$result->message  = 'No option called ' . $option['name'] . ' found for AppID ' . $id;
				$response         = ( $response + [ $option['name'] => $result ] );
			}
		}

		return $response;
	}

	function post_options( $id, $options ) {
		$response = [];
		foreach ( $options as $option ) {
			$result = new stdClass();
			$stmt   = Database::$conn->prepare( 'SELECT id,  app_id, name, value FROM ecc_app_options WHERE app_id = ? AND name = ?' );
			$res    = $stmt->execute( [ $id, $option['name'] ] );
			$count  = $stmt->rowCount();
			if ( $count < 1 ) {
				$stmt             = Database::$conn->prepare( 'INSERT into ecc_app_options (value, app_id, name) VALUES (?, ?, ?)' );
				$res              = $stmt->execute( [ $option['value'], $id, $option['name'] ] );
				$last_insert_id   = Database::$conn->lastInsertId();
				$result->response = 'HTTP_200';
				$result->message  = 'success';
				$response         = ( $response + [ $option['name'] => $result ] );
			}
			else {
				$result->response = 'HTTP_422';
				$result->message  = 'CharacterId ' . $id . ' already has a option called ' . $option['name'] . '. To update existing option, use PUT or PATCH instead.';
				$response         = ( $response + [ $option['name'] => $result ] );
			}
		}
		return $response;
	}

	function delete_option( $id, $options ) {
		$total_deleted = 0;
		foreach ( $options as $option ) {
			if ( array_key_exists( 'value', $option ) ) {
				$stmt = Database::$conn->prepare( 'DELETE FROM ecc_app_options WHERE app_id = ? AND name = ? AND value = ?' );
				$res  = $stmt->execute( [ $id, $option['name'], $option['value'] ] );
			}
			else {
				$stmt = Database::$conn->prepare( 'DELETE FROM ecc_app_options WHERE app_id = ? AND name = ?' );
				$res  = $stmt->execute( [ $id, $option['name'] ] );
			}
			$count = $stmt->rowCount();
			if ( $count > 0 ) {
				$total_deleted += $count;


			}
		}

		if ( $total_deleted > 0 ) {
			return 'success';
		}
		else {
			return 'nothing deleted';
		}
	}
}
