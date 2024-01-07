<?php
class Backstory
{
    public function get_backstory($id, $type)
    {
        if ($type == 'concept') {
            $query = "SELECT ecc_backstory.characterID, characters.accountID as accountID, characters.character_name as name, characters.faction as faction,
		FROM_BASE64(concept_content) as content, FROM_BASE64(concept_changes) as concept_changes,  FROM_BASE64(backstory_changes) as backstory_changes, status.status_name,
  		status.status_description, timestamp
                FROM ecc_backstory
                LEFT join ecc_backstory_status status on (ecc_backstory.concept_status = status.id AND status.status_type = 'concept')
                LEFT join ecc_characters characters on (ecc_backstory.characterID = characters.characterID)
                WHERE ecc_backstory.characterID = $id";
        }
        if ($type == 'backstory') {
            $query = "SELECT ecc_backstory.characterID, characters.accountID as accountID, characters.character_name as name, characters.faction as faction,
		FROM_BASE64(backstory_content) as content, FROM_BASE64(concept_changes) as concept_changes,  FROM_BASE64(backstory_changes) as backstory_changes,
                status.status_name, status.status_description, timestamp
                FROM ecc_backstory
                LEFT join ecc_backstory_status status on (ecc_backstory.backstory_status = status.id AND status.status_type = 'backstory')
                LEFT join ecc_characters characters on (ecc_backstory.characterID = characters.characterID)
                 WHERE ecc_backstory.characterID = $id";
        }
        $stmt = Database::$conn->prepare($query);
        $res = $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res;
    }

    public function get_all_backstories($type)
    {
        if ($type == 'concept') {
            $query = "SELECT ecc_backstory.characterID, characters.accountID as accountID, characters.character_name as name, characters.faction as faction,
		FROM_BASE64(concept_content) as content, FROM_BASE64(concept_changes) as concept_changes,  FROM_BASE64(backstory_changes) as backstory_changes, status.status_name,
  		status.status_description, timestamp
                FROM ecc_backstory
                LEFT join ecc_backstory_status status on (ecc_backstory.concept_status = status.id AND status.status_type = 'concept')
                LEFT join ecc_characters characters on (ecc_backstory.characterID = characters.characterID)
		ORDER by characters.faction ASC, characters.character_name ASC";
        }
        if ($type == 'backstory') {
            $query = "SELECT ecc_backstory.characterID, characters.accountID as accountID, characters.character_name as name, characters.faction as faction,
		FROM_BASE64(backstory_content) as content, FROM_BASE64(concept_changes) as concept_changes,  FROM_BASE64(backstory_changes) as backstory_changes, status.status_name as backstory_status, ecc_backstory.concept_status as concept_status,
  		status.status_description, timestamp
                FROM ecc_backstory
                LEFT join ecc_backstory_status status on (ecc_backstory.backstory_status = status.id AND status.status_type = 'backstory')
                LEFT join ecc_characters characters on (ecc_backstory.characterID = characters.characterID)
		ORDER by characters.faction ASC, characters.character_name ASC";
        }
        $stmt = Database::$conn->prepare($query);
        $res = $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    public function set_backstory($id, $type, $content, $user)
    {
        if ($type == 'concept') {
            $query = "INSERT INTO ecc_backstory (characterID, concept_content, concept_updated_by)
                VALUES (:id, :content, :user)
                ON DUPLICATE KEY UPDATE
                concept_content = :content, concept_updated_by = :user";
        }
        if ($type == 'backstory') {
            $query = "INSERT INTO ecc_backstory (characterID, backstory_content, backstory_updated_by)
                VALUES (:id, :content, :user)
                ON DUPLICATE KEY UPDATE
                backstory_content = :content, backstory_updated_by = :user";
        }
        if ($type == 'concept_changes') {
            $query = "INSERT INTO ecc_backstory (characterID, concept_changes, concept_changes_requested_by)
                VALUES (:id, :content, :user)
                ON DUPLICATE KEY UPDATE
                concept_changes = :content, concept_changes_requested_by = :user";
        }
        if ($type == 'backstory_changes') {
            $query = "INSERT INTO ecc_backstory (characterID, backstory_changes, backstory_changes_requested_by)
                VALUES (:id, :content, :user)
                ON DUPLICATE KEY UPDATE
                backstory_changes = :content, backstory_changes_requested_by = :user";
        }
        $stmt = Database::$conn->prepare($query);
        #bindParam takes arguments var, replacement, type
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user', $user, PDO::PARAM_INT);
        $stmt->bindParam(':content', base64_encode($content), PDO::PARAM_STR);
        $res = $stmt->execute();
        return $stmt->rowCount();
    }

    public function get_statuses($type)
    {
        $stmt = Database::$conn->prepare("SELECT id, status_name, status_description FROM ecc_backstory_status WHERE status_type = '$type'");
        $res = $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $new_array = [];

        foreach ($res as $array) {
            $new_array[$array['status_name']] = $array;
        }

        return $new_array;
    }

    public function update_status($id, $type, $status, $user)
    {
        if ($type == 'concept') {
            $query = "UPDATE ecc_backstory SET concept_status = (SELECT id from ecc_backstory_status WHERE status_name = '$status' AND status_type = '$type') WHERE characterID = $id;";
            if ($status == "approved") {
                $query = $query . " UPDATE ecc_backstory SET concept_approved_by = $user WHERE characterID = $id;";
            }
        }
        if ($type == 'backstory') {
            $query = "UPDATE ecc_backstory SET backstory_status = (SELECT id from ecc_backstory_status WHERE status_name = '$status' AND status_type = '$type') WHERE characterID = $id;";
            if ($status == "approved") {
                $query = $query . " UPDATE ecc_backstory SET backstory_approved_by = $user WHERE characterID = $id;";
            }
        }
        $stmt = Database::$conn->prepare($query);
        $res = $stmt->execute();
        return $stmt->rowCount();
    }
}