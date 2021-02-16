<?php
namespace Frontier\Orthanc\Db;

use PDO;

class Db {

	/**
	 * database
	 *
	 * @var mixed
	 */
	public $database;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		// Could be lazy-loaded as well
		$this->database = new PDO(
			'mysql:host=localhost;dbname=frontier;charset=utf8',
			'username',
			'password',
			[
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES   => false,
			]
		);
	}

	/**
	 * Get_db
	 *
	 * @return object
	 */
	public function get_db() {
		return $this->database;
	}
}

$db = new Db();

$blaat = $db->get_db()->query( "SELECT * FROM ecc_characters WHERE status NOT LIKE 'figurant%' AND sheet_status != 'deleted'" )->fetchAll();
var_dump( $blaat );
