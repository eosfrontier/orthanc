<?php 

class figurant{

    function addFigurant($character){
        $check = $this->checkCardId($character["card_id"]);

        $character_name     = $character["character_name"];
        $card_id            = $character["card_id"];
        $faction            = $character["faction"];
        $rank               = $character["rank"];
        $threat_assessment  = $character["threat_assessment"];
        $douane_disposition = $character["douane_disposition"];
        $douane_notes       = $character["douane_notes"];
        $bastion_clearance  = $character["bastion_clearance"];
        $ICC_number         = $character["ICC_number"];
        $bloodtype          = $character["bloodtype"];
        $ic_birthday        = $character["ic_birthday"];
        $homeplanet         = $character["homeplanet"];

        $figustatus = "figurant";
        if(isset($character["recurring"])){
            $figustatus = "figurant-recurring";
        }

        if(!$check){

            $stmt = database::$conn->prepare(
                "INSERT into ecc_characters 
                    (accountID, character_name, card_id, faction, status, rank, threat_assessment, douane_disposition, douane_notes, bastion_clearance, ICC_number, bloodtype, ic_birthday, homeplanet) 
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
            $res = $stmt->execute(array(
                0, $character_name, $card_id, $faction, $figustatus, $rank, $threat_assessment, $douane_disposition, $douane_notes, $bastion_clearance, $ICC_number, $bloodtype, $ic_birthday, $homeplanet
            ));

            return "success";

        }else{
            if($check["status"] == "figurant-recurring"){
                
                $stmt = database::$conn->prepare("UPDATE ecc_characters SET card_id=? WHERE characterID = ?");
                $res = $stmt->execute(array(NULL, $check["characterID"]));

                $stmt = database::$conn->prepare(
                    "INSERT into ecc_characters 
                        (accountID, character_name, card_id, faction, status, rank, threat_assessment, douane_disposition, douane_notes, bastion_clearance, ICC_number, bloodtype, ic_birthday, homeplanet) 
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                $res = $stmt->execute(array(
                    0, $character_name, $card_id, $faction, $figustatus, $rank, $threat_assessment, $douane_disposition, $douane_notes, $bastion_clearance, $ICC_number, $bloodtype, $ic_birthday, $homeplanet
                ));

                return "success";

            }else{

                $stmt = database::$conn->prepare(
                    "UPDATE ecc_characters SET 
                        character_name=?,
                        faction=?,
                        status=?,
                        rank=?,
                        threat_assessment=?,
                        douane_disposition=?,
                        douane_notes=?,
                        bastion_clearance=?,
                        ICC_number=?,
                        bloodtype=?,
                        ic_birthday=?,
                        homeplanet=?
                    WHERE characterID = ?");
                $res = $stmt->execute(array($character_name, $faction, $figustatus, $rank, $threat_assessment, $douane_disposition, $douane_notes, $bastion_clearance, $ICC_number, $bloodtype, $ic_birthday, $homeplanet, $check["characterID"]));

                return "success";
            }
        }

    }

    private function checkCardId($cardId){
        $stmt = database::$conn->prepare("SELECT * FROM ecc_characters WHERE card_id = ?");
        $res = $stmt->execute(array($cardId));
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return $res;
    }
}

?>