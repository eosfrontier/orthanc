<?php

include(__DIR__ ."/../database.php");

class Character{
    
    public function read(){
        $stmt = db::$conn->prepare("SELECT * FROM ecc_characters");
	$res = $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC); 
        
        return $res;
    }

}
?>
