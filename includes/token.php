<?php
$token_table = 'eos_tokens';

/**
 * Accepts token from header and checks for the token's existence in the table specified by $token_table.
 *
 * @param  mixed $token - token provided by header.
 * @param  mixed $token_table - name of token table.
 * @return mixed - Return either the name associated with the token as a string, if it exists, or boolean false if the token is invalid.
 */
function token( $token, $token_table ) {
	$stmt  = Database::$conn->prepare( "SELECT * FROM $token_table WHERE token = ?" );
	$res   = $stmt->execute( [ $token ] );
	$res   = $stmt->fetch( PDO::FETCH_ASSOC );
	$count = $stmt->rowCount();

	if ( $count > 0 ) {
		return $res['name'];
	}
	else {
		return false;
	}
}
// CHECK ACCESS TOKEN.

$headers = getallheaders();

$access = '';
if ( isset( $headers['token'] ) ) {
	$access = token( $headers['token'], $token_table );
	if ( $access === false ) {
		http_response_code( 401 );
		echo json_encode( 'YOU SHALL NOT PASS!!' );
		die();
	}
}
elseif ( isset( $input['token'] ) ) {
	$access = token( $input['token'], $token_table );
	if ( $access === false ) {
		http_response_code( 401 );
		echo json_encode( 'YOU SHALL NOT PASS!!' );
		die();
	}
}

else {
	http_response_code( 401 );
	echo json_encode( 'YOU SHALL NOT PASS!!' );
	die();
}
