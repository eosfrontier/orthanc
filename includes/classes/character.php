<?php

class character{
    
    public function getAll(){
        $stmt = database::$conn->prepare("SELECT * FROM ecc_characters");
		$res = $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC); 
        return $res;
    }

    public function get($id, $needle){
        $stmt = database::$conn->prepare("SELECT * FROM ecc_characters where $needle = ?");
		$res = $stmt->execute(array($id));
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC); 
        return $res;
    }

}
?>
