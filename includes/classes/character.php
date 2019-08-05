<?php

class character{
    
    public function getAll(){
        $stmt = database::$conn->prepare("SELECT * FROM ecc_characters");
		$res = $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC); 
        return $res;
    }

    public function get($id, $needle){
        if($needle == "card_id"){
            $stmt = database::$conn->prepare("SELECT * FROM ecc_characters WHERE card_id = ?");
            $res = $stmt->execute(array($id));
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($res == null){
                $sHex = dechex($id);
                $aDec = str_split($sHex, 2);
                if(!isset($aDec[1])){
                    return "false";
                }
                $sDec = "%".$aDec[3].$aDec[2].$aDec[1].$aDec[0]."%";
                if($sDec == "%0%"){
                    return "false";
                }
                
                $stmt = database::$conn->prepare("SELECT * FROM ecc_characters WHERE card_id LIKE ?");
                $res = $stmt->execute(array($sDec));
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        $stmt = database::$conn->prepare("SELECT * FROM ecc_characters where $needle = ?");
		$res = $stmt->execute(array($id));
        $res = $stmt->fetch(PDO::FETCH_ASSOC); 
        return $res;
    }

}
?>
