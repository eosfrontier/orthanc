<?php

class meta{
    function getAllMetaById($id){
        $stmt = database::$conn->prepare("SELECT id, name, value FROM ecc_meta_character WHERE character_id = ?");
        $res = $stmt->execute(array($id));
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $res;
    }

    function getByMeta($id, $meta){
        $stmt = database::$conn->prepare("SELECT id, name, value FROM ecc_meta_character WHERE character_id = ? AND name in ($meta)");
        $res = $stmt->execute(array($id));
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    function updateMeta($id, $metas){
        foreach($metas as $meta){
            $stmt = database::$conn->prepare("SELECT id, name, value FROM ecc_meta_character WHERE character_id = ? AND name = ?");
            $res = $stmt->execute(array($id, $meta["name"]));
            $count = $stmt->rowCount();
            if($count > 0){

                $stmt = database::$conn->prepare("UPDATE ecc_meta_character SET value=? WHERE character_id = ? AND name = ?");
                $res = $stmt->execute(array($meta["value"], $id, $meta["name"]));

            }else{
                $stmt = database::$conn->prepare("INSERT into ecc_meta_character (value, character_id, name) VALUES (?, ?, ?)");
                $res = $stmt->execute(array($meta["value"], $id, $meta["name"]));
            }
        }

        return "success";
    }
}

?>