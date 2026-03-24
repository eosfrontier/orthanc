<?php

class Shuttlebay
{

    public function runQuery($sql)
    {
        $stmt = Database::$conn->prepare("$sql");
        $res = $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $res;
    }

    public function getAllShuttles()
    {
        $sql = <<<SQL
                SELECT s.id, s.name, s.serial_number, c.name AS class, c.id as class_id, c.`type` as type, c.capacity, b.name AS base, 
        cond.name AS state, cond.operable AS operable, stat.`status` AS status_name, s.status AS status, chars.character_name AS assigned_to_name, chars.characterID AS assigned_to_id, skill.label AS required_skill, c.required_skill AS required_skill_id
        FROM esb_shuttles s
        JOIN esb_shuttle_classes c ON s.class = c.id
        JOIN esb_shuttle_bases b ON b.id = s.base_location
        JOIN esb_shuttle_conditions cond ON cond.id = s.condition
        JOIN esb_shuttle_status stat ON stat.id = s.status
        LEFT JOIN ecc_characters chars ON chars.characterID = s.assigned_to
        JOIN ecc_skills_allskills skill ON ((skill.skill_id = c.required_skill) AND (c.id = s.class))
        ORDER by s.name;
        SQL;

        $res = $this->runQuery($sql);
        return $res;
    }

    public function getShuttle($id)
    {
        $sql = <<<SQL
        SELECT s.id, s.name, s.serial_number, c.name AS class, c.id as class_id, c.`type` as type, c.capacity, b.name AS base, 
        cond.name AS state, cond.operable AS operable, stat.`status` AS status_name, s.status AS status, chars.character_name AS assigned_to_name, chars.characterID AS assigned_to_id, skill.label AS required_skill, c.required_skill AS required_skill_id
        FROM esb_shuttles s
        JOIN esb_shuttle_classes c ON s.class = c.id
        JOIN esb_shuttle_bases b ON b.id = s.base_location
        JOIN esb_shuttle_conditions cond ON cond.id = s.condition
        JOIN esb_shuttle_status stat ON stat.id = s.status
        LEFT JOIN ecc_characters chars ON chars.characterID = s.assigned_to
        JOIN ecc_skills_allskills skill ON ((skill.skill_id = c.required_skill) AND (c.id = s.class))
        WHERE s.id = $id
        ORDER by s.name;
        SQL;

        $res = $this->runQuery($sql);
        return $res;
    }
}
