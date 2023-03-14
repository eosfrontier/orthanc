<?php
    class Background_Check {
        public function get_background_check( $id, $needle ) {
            $stmt = Database::$conn->prepare( "
            SELECT id,
            bg.characterID,
            chargen.character_name as chargen_name,
            first_name,
            family_name,
            residence,
            chargen.homeplanet,
            chargen.birthplanet,
            chargen.ic_birthday as birthdate,
            (SELECT fieldvalue from med_fieldvalues WHERE characterID = $id AND fieldtypeID = 9) as name_father,
            (SELECT fieldvalue from med_fieldvalues WHERE characterID = $id AND fieldtypeID = 10) as name_mother,
            birthplace,
            chargen.faction,
            education,
            chargen.rank as current_position,
            chargen.bloodtype,
            religion,
            company_ownership,
            court_accusations,
            court_sentences,
            special_medical_circumstances,
            memberships,
            life_achievements,
            little_secrets,
            big_secrets,
            political_preference,
            (SELECT fieldvalue from med_fieldvalues WHERE characterID = $id AND fieldtypeID = 3) as other_family,
            notable_friends,
            miscellany
            FROM ecc_background_check bg 
            LEFT JOIN ecc_characters chargen ON (chargen.characterID = bg.characterID)
            where bg.$needle = ?
                " );
			$res  = $stmt->execute( [ $id ] );
			$res  = $stmt->fetch( PDO::FETCH_ASSOC );
            return $res;
            }
    }

