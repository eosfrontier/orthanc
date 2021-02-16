<?php
namespace Frontier\Orthanc\character;

use Frontier\Orthanc\Db\Db;

class Character {

	private $database;

	public function __construct( Db $db ) {
		$this->database = $db;
	}

	public function get_all() {

		return $this->Db->get_db()->query( "SELECT * FROM ecc_characters WHERE status NOT LIKE 'figurant%' AND sheet_status != 'deleted'" )->fetchAll();
	}
}

$blaat = new Character();
echo $blaat->get_all();
