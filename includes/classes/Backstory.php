<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
class Backstory
{
    public function get_backstory($id, $type)
    {
        if ($type == 'concept') {
            $query = "SELECT ecc_backstory.characterID, characters.accountID as accountID, characters.character_name as name, 
            characters.faction as faction, update_user.name AS concept_updated_by, approved_user.name AS concept_approved_by, 
            ecc_backstory.concept_updated_date, change_requester.name as concept_changes_requested_by, 
            ecc_backstory.concept_changes_requested_date, ecc_backstory.concept_approval_date, FROM_BASE64(concept_content) as content,
            FROM_BASE64(concept_changes) as concept_changes,  FROM_BASE64(ecc_backstory.concept_comment) as concept_comment, FROM_BASE64(backstory_changes) as backstory_changes, status.status_name, 
            status.status_description, last_reminder_sent, timestamp
                FROM ecc_backstory
                LEFT join ecc_backstory_status status on (ecc_backstory.concept_status = status.id AND status.status_type = 'concept')
                LEFT join ecc_characters characters on (ecc_backstory.characterID = characters.characterID)
                LEFT JOIN jml_users update_user ON (update_user.id = ecc_backstory.concept_updated_by)
                LEFT JOIN jml_users approved_user ON (approved_user.id = ecc_backstory.concept_approved_by)
                LEFT JOIN jml_users change_requester ON (change_requester.id = ecc_backstory.concept_changes_requested_by)
                WHERE ecc_backstory.characterID = $id";
        }
        if ($type == 'backstory') {
            $query = "SELECT ecc_backstory.characterID, characters.accountID as accountID, characters.character_name as name, 
            characters.faction as faction, update_user.name AS backstory_updated_by, ecc_backstory.backstory_updated_date, 
            change_requester.name as backstory_changes_requested_by, ecc_backstory.backstory_changes_requested_date, 
            approved_user.name AS backstory_approved_by, ecc_backstory.backstory_approval_date, FROM_BASE64(backstory_content) as content,
            FROM_BASE64(concept_changes) as concept_changes, FROM_BASE64(ecc_backstory.concept_comment) as concept_comment, FROM_BASE64(backstory_changes) as backstory_changes, status.status_name,
            status.status_description, last_reminder_sent, timestamp
                    FROM ecc_backstory
                    LEFT join ecc_backstory_status status on (ecc_backstory.backstory_status = status.id AND status.status_type = 'backstory')
                    LEFT join ecc_characters characters on (ecc_backstory.characterID = characters.characterID)
                    LEFT JOIN jml_users update_user ON (update_user.id = ecc_backstory.backstory_updated_by)
                    LEFT JOIN jml_users approved_user ON (approved_user.id = ecc_backstory.backstory_approved_by)
                    LEFT JOIN jml_users change_requester ON (change_requester.id = ecc_backstory.backstory_changes_requested_by)
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
            $query = "SELECT ecc_backstory.characterID, characters.accountID as accountID, characters.character_name as name, 
            characters.faction as faction, update_user.name AS concept_updated_by, approved_user.name AS concept_approved_by, 
            ecc_backstory.concept_updated_date, change_requester.name as concept_changes_requested_by, ecc_backstory.concept_changes_requested_date, 
            ecc_backstory.concept_approval_date, FROM_BASE64(concept_content) as content, FROM_BASE64(concept_changes) as concept_changes, 
            FROM_BASE64(ecc_backstory.concept_comment) as concept_comment, FROM_BASE64(backstory_changes) as backstory_changes, status.status_name, status.status_description, last_reminder_sent, timestamp
                FROM ecc_backstory
                LEFT join ecc_backstory_status status on (ecc_backstory.concept_status = status.id AND status.status_type = 'concept')
                LEFT join ecc_characters characters on (ecc_backstory.characterID = characters.characterID)
                LEFT JOIN jml_users update_user ON (update_user.id = ecc_backstory.concept_updated_by)
                LEFT JOIN jml_users approved_user ON (approved_user.id = ecc_backstory.concept_approved_by)
                LEFT JOIN jml_users change_requester ON (change_requester.id = ecc_backstory.concept_changes_requested_by)
		        ORDER by characters.faction ASC, characters.character_name ASC";
        }

        if ($type == 'backstory') {
            $query = "SELECT ecc_backstory.characterID, characters.accountID as accountID, characters.character_name as name, 
            characters.faction as faction, update_user.name AS backstory_updated_by, ecc_backstory.backstory_updated_date, 
            change_requester.name as backstory_changes_requested_by, ecc_backstory.backstory_changes_requested_date, 
            approved_user.name AS backstory_approved_by, ecc_backstory.backstory_approval_date, FROM_BASE64(backstory_content) as content, 
            FROM_BASE64(concept_content) as concept_content, FROM_BASE64(concept_changes) as concept_changes,  FROM_BASE64(ecc_backstory.concept_comment) as concept_comment, 
            FROM_BASE64(backstory_changes) as backstory_changes, status.status_name as backstory_status, status.status_description, last_reminder_sent, timestamp
                FROM ecc_backstory
                LEFT join ecc_backstory_status status on (ecc_backstory.backstory_status = status.id AND status.status_type = 'backstory')
                LEFT join ecc_characters characters on (ecc_backstory.characterID = characters.characterID)
                LEFT JOIN jml_users update_user ON (update_user.id = ecc_backstory.backstory_updated_by)
                LEFT JOIN jml_users approved_user ON (approved_user.id = ecc_backstory.backstory_approved_by)
                LEFT JOIN jml_users change_requester ON (change_requester.id = ecc_backstory.backstory_changes_requested_by)
                ORDER by characters.faction ASC, characters.character_name ASC";
        }
        $stmt = Database::$conn->prepare($query);
        $res = $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }

    public function set_backstory($id, $type, $content, $user)
    {
        $date = str_replace(': ', ':', date("Y-m-d H:i:s"));
        if ($type == 'concept') {
            $query = "INSERT INTO ecc_backstory (characterID, concept_content, concept_updated_by, concept_updated_date)
                VALUES (:id, :content, :user, :update_date)
                ON DUPLICATE KEY UPDATE
                concept_content = :content, concept_updated_by = :user, concept_updated_date = :update_date";
        }
        if ($type == 'backstory') {
            $query = "INSERT INTO ecc_backstory (characterID, backstory_content, backstory_updated_by, backstory_updated_date)
                VALUES (:id, :content, :user, :update_date)
                ON DUPLICATE KEY UPDATE
                backstory_content = :content, backstory_updated_by = :user, backstory_updated_date = :update_date";
        }
        if ($type == 'concept_changes') {
            $query = "INSERT INTO ecc_backstory (characterID, concept_changes, concept_changes_requested_by, concept_changes_requested_date)
                VALUES (:id, :content, :user, :update_date)
                ON DUPLICATE KEY UPDATE
                concept_changes = :content, 
                concept_changes_requested_by = :user, 
                concept_changes_requested_date = :update_date";
        }
        if ($type == 'concept_comment') {
            $query = "INSERT INTO ecc_backstory (characterID, concept_comment)
                VALUES (:id, :content, :user, :update_date)
                ON DUPLICATE KEY UPDATE
                concept_changes = :content";
        }
        if ($type == 'backstory_changes') {
            $query = "INSERT INTO ecc_backstory (characterID, backstory_changes, backstory_changes_requested_by, backstory_changes_requested_date)
                VALUES (:id, :content, :user, :update_date)
                ON DUPLICATE KEY UPDATE
                backstory_changes = :content, backstory_changes_requested_by = :user, backstory_changes_requested_date = :update_date";
        }
        $stmt = Database::$conn->prepare($query);
        #bindParam takes arguments var, replacement, type
        $base64_content = base64_encode($content);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':content', $base64_content, PDO::PARAM_STR);
        $stmt->bindParam(':user', $user, PDO::PARAM_INT);
        $stmt->bindParam(':update_date', $date, PDO::PARAM_STR);
        $res = $stmt->execute();
        return $stmt->rowCount();
    }

    public function set_approval_comment($id, $type, $content)
    {
        if ($type == 'concept_comment') {
            $query = "INSERT INTO ecc_backstory (characterID, concept_comment)
                VALUES (:id, :content)
                ON DUPLICATE KEY UPDATE
                concept_comment = :content";
        }
        $stmt = Database::$conn->prepare($query);
        #bindParam takes arguments var, replacement, type
        $base64_content = base64_encode($content);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':content', $base64_content, PDO::PARAM_STR);
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
        $date = str_replace(': ', ':', date("Y-m-d H:i:s"));
        if ($type == 'concept') {
            $query = "UPDATE ecc_backstory SET concept_status = (SELECT id from ecc_backstory_status WHERE status_name = '$status' AND status_type = '$type') WHERE characterID = $id;";
            if ($status == "approved") {
                $query = $query . " UPDATE ecc_backstory SET
                concept_approved_by = $user ,
                concept_approval_date = '$date'
                WHERE characterID = $id;";
            }
        }
        if ($type == 'backstory') {
            $query = "UPDATE ecc_backstory SET backstory_status = (SELECT id from ecc_backstory_status WHERE status_name = '$status' AND status_type = '$type') WHERE characterID = $id;";
            if ($status == "approved") {
                $query = $query . " UPDATE ecc_backstory SET
                backstory_approved_by = $user ,
                backstory_approval_date = '$date'
                WHERE characterID = $id;";
            }
        }
        $stmt = Database::$conn->prepare($query);
        $res = $stmt->execute();
        return $stmt->rowCount();
    }
    public function set_reminder_date($id)
    {
        $date = str_replace(': ', ':', date("Y-m-d H:i:s")); 
        $query = "UPDATE ecc_backstory SET last_reminder_sent = '$date' WHERE characterID = $id;";
        $stmt = Database::$conn->prepare($query);
        $res = $stmt->execute();
        return $stmt->rowCount();
    }
}

