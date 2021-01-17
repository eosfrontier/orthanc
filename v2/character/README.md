
# api/orthanc/v2/character/
places a character in the database with provided information, with the status 'character', or 'character-recurring'

## Resource URL
api/orthanc/v2/character/
### Sub-URLs
[/orthanc/v2/character/meta/](/v2/character/meta/README.md)  
[/orthanc/v2/character/skills/](/v2/character/skills/README.md)

## Resource Information
|                          |      |
| ------------------------ | ---- |
| Response formats         | JSON |
| Requires authentication? | Yes  |
| Rate limited?            | No   |

| Method | Description                                        |
| ------ | -------------------------------------------------- |
| GET    | Gets character with ID **id**                      |
| POST   | Adds new character                                 |
| DELETE | Sets character **id** to deleted status            |
| PATCH | Updates character record for character with **id** |

## Parameters
| Name           | Description                                                                  | Required For        | Optional For | Example                                                                                                                                                                                                                                                                                                                               |
| -------------- | ---------------------------------------------------------------------------- | ------------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| token          | provides authentication                                                      | All Methods         |              |
| id             | Character ID to GET or PATCH                                                | GET, DELETE, PATCH |              | 42                                                                                                                                                                                                                                                                                                                                    |
| all_characters | Declare this header to retrieve all active characters. No value needed.      |                     | GET          |
| accountID      | Use this header to retrieve all characters associated with a joomla user ID. | POST                | GET          | 747                                                                                                                                                                                                                                                                                                                                   |
| card_id        | Use this header to lookup character by RFID Card ID.                         |                     | GET          | 043A859A665A80                                                                                                                                                                                                                                                                                                                        |
| icc_number     | Use this header to lookup character by ICC ID Number.                        |                     | GET          | 5941 49609 7331                                                                                                                                                                                                                                                                                                                       |
| character      | sets the character details                                                   |                     |              | {"card_id":"1234", "character_name": "name",</br>"faction": "faction","rank": "rank","threat_assessment" : "0",</br>"douane_disposition" : "ICC VETTED","douane_notes" : "notes",</br>"bastion_clearance" : "0","icc_number" : "1234","bloodtype" : "A",</br>"ic_birthday": "24 oct 231","homeplanet": "Dzar",</br>"recurring" : "1"} |
| restore        | When defined, will restore a deleted character (no value required).          |                     | DELETE       | 1                                                                                                                                                                                                                                                                                                                                     |

## Parameters character
| Name               | Required | Description                                                                                                                                                                     | Default Value                                                                                     | Example                                                                    |
| ------------------ | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| card_id            | yes      | links the sent character to the card, if another character already has this card linked, it will be removed from them and given to this new character                           |                                                                                                   |                                                                            | AJDY234602JF    |
| character_name     | yes      | sets characters name                                                                                                                                                            |                                                                                                   |                                                                            | Swoopsy Whoopsy |
| faction            | yes      | sets faction                                                                                                                                                                    | must be all lower case                                                                            | aquila, dugo, ekanesh, pendzal, sona                                       | aquila          |
| rank               | yes      | sets the job / rank field                                                                                                                                                       |                                                                                                   |                                                                            | Lt.             |
| threat_assesment   | yes      | an aquila legacy ranking system                                                                                                                                                 |                                                                                                   | 0 to 5                                                                     | 1               |
| douane_disposition | yes      | the status the douane see on screen when scanning the id                                                                                                                        | must be all caps                                                                                  | ACCESS PENDING, ACCESS GRANTED, DECEASED, DETAIN, ICC VETTED               | ACCESS GRANTED  |
| douane_notes       | yes      | this field holds notes that can be filled by douane, leave this empty, as this should be touched only by players                                                                |                                                                                                   |                                                                            |
| bastion_clearance  | yes      | the clearance pips on the cards                                                                                                                                                 |                                                                                                   | 0 to 3                                                                     | 1               |
| icc_number         | yes      | this 15chr. long id in format "1234 12345 1234" must be an unique ICC number that does not occur elsewhere in the database. You can use the get character API to test for this. | each faction has their own first number, aquila = 7, dugo = 3, ekanesh = 8, pendzal = 9, sona = 5 |                                                                            | 7345 34768 3457 |
| bloodtype          | yes      | sets the blood type of the character                                                                                                                                            |                                                                                                   | O, A, B, AB                                                                | A               |
| ic_birthday        | yes      | its the characters birthdate, can be set in anyway they like                                                                                                                    |                                                                                                   |                                                                            | 24-10-231nt     |
| homeplanet         | yes      | its the characters homeplanet / planet they traveled from                                                                                                                       | does not check if the planet exists                                                               | current planets in game at time of creating documentation are listed below | Accipiter       |

### List of all known planets sorted by faction ownership

| faction |     | planets   |             |          |           |        |         |          |         |        |        |           |        |
| ------- | --- | --------- | ----------- | -------- | --------- | ------ | ------- | -------- | ------- | ------ | ------ | --------- | ------ |
| aquila  |     | Accipiter | Alcyon      | Alietum  | Ferox II  | Merula | Noctua  | Sturnus  | Viridis | Fastus | Ignis  | Ithaginis | Tigris |
| dugo    |     | Kaito     | Batongbayal | Cabatu   | Hideyoshi | Hiroto | Katsuro | Minoru   | Shinobu | Tarou  | Haruka | Noburu    |
| ekanesh |     | Dzar      |
| pendzal |     | Dodamu    | Zvir        | Ziamlia  | Zorki     | Vady   | Cionma  | Vtotoroy | Nadz    | Ruda   | Zyccio |
| sona    |     | Andhera   | Ghara       | Prakhasa |
  
