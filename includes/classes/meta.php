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

    function getAllByMeta($meta){
        $stmt = database::$conn->prepare("SELECT id, name, value, character_id FROM ecc_meta_character WHERE name in ($meta)");
        $res = $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    function updateMeta($id, $metas){
        foreach($metas as $meta){
            if (array_key_exists("oldvalue", $meta)) {
                $stmt = database::$conn->prepare("UPDATE ecc_meta_character SET value=? WHERE character_id = ? AND name = ? AND value = ?");
                $res = $stmt->execute(array($meta["value"], $id, $meta["name"], $meta["oldvalue"]));
            } else {
                $stmt = database::$conn->prepare("UPDATE ecc_meta_character SET value=? WHERE character_id = ? AND name = ?");
                $res = $stmt->execute(array($meta["value"], $id, $meta["name"]));
            }
            if ($stmt->rowCount() == 0) {
                $stmt = database::$conn->prepare("INSERT into ecc_meta_character (value, character_id, name) VALUES (?, ?, ?)");
                $res = $stmt->execute(array($meta["value"], $id, $meta["name"]));
            }
        }

        return "success";
    }

    function deleteMeta($id, $metas){
        $totcount = 0;
        foreach($metas as $meta){
            if (array_key_exists("value", $meta)) {
                $stmt = database::$conn->prepare("DELETE FROM ecc_meta_character WHERE character_id = ? AND name = ? AND value = ?");
                $res = $stmt->execute(array($id, $meta["name"], $meta["value"]));
            } else {
                $stmt = database::$conn->prepare("DELETE FROM ecc_meta_character WHERE character_id = ? AND name = ?");
                $res = $stmt->execute(array($id, $meta["name"]));
            }
            $count = $stmt->rowCount();
            if($count > 0){
                $totcount += $count;
            }
        }

        if ($totcount > 0) {
            return "success";
        }
    }
}

?>
