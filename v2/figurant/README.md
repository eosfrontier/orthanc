
# api/orthanc/v2/figurant/
places a figurant in the database with provided information, with the status 'figurant', or 'figurant-recurring'

## Resource URL
api/orthanc/v2/figurant/
### Sub-URLs
[/orthanc/v2/figurant/meta/](/v2/figurant/meta/README.md)  
[/orthanc/v2/figurant/skills/](/v2/figurant/skills/README.md)

## Resource Information
|||
|--|--|
|Response formats | JSON |
|Requires authentication?| Yes |
|Rate limited? | No |

|Method | Description |
| --- | ---
| GET | Gets figurant with ID **id**
| POST | Adds new figurant
| DELETE | Sets figurant **id** to deleted status
| UPDATE | Updates figurant record for figurant with **id**

## Parameters
| Name | Description | Required For | Optional For | Example
|--|--|--|--|--
token | provides authentication | All Methods | | 
id | figurant ID to GET or UPDATE | GET, DELETE, UPDATE | | 42
all_figurants | Declare this header to retrieve all active figurants. No value needed. | | GET | 
accountID | Use this header to retrieve all figurants associated with a joomla user ID. | | GET | 747
card_id | Use this header to lookup figurant by RFID Card ID. | | GET | 043A859A665A80
icc_number | Use this header to lookup figurant by ICC ID Number. | | GET | 5941 49609 7331
figurant |  sets the figurant details | | | {"card_id":"1234", "figurant_name": "name",</br>"faction": "faction","rank": "rank","threat_assessment" : "0",</br>"douane_disposition" : "ICC VETTED","douane_notes" : "notes",</br>"bastion_clearance" : "0","icc_number" : "1234","bloodtype" : "A",</br>"ic_birthday": "24 oct 231","homeplanet": "Dzar",</br>"recurring" : "1"}
restore | When set to 1, will restore a deleted figurant | | DELETE | 1


## Parameters figurant
| Name | Required | Description | Default Value | Example
|--|--|--|--|--
card_id | yes | links the sent figurant to the card, if another figurant already has this card linked, it will be removed from them and given to this new figurant | | | AJDY234602JF
figurant_name | yes | sets figurants name | | | Swoopsy Whoopsy
faction | yes | sets faction | must be all lower case | aquila, dugo, ekanesh, pendzal, sona | aquila
rank | yes | sets the job / rank field | | | Lt.
threat_assesment | yes | an aquila legacy ranking system | | 0 to 5 | 1 
douane_disposition | yes | the status the douane see on screen when scanning the id | must be all caps | ACCESS PENDING, ACCESS GRANTED, DECEASED, DETAIN, ICC VETTED | ACCESS GRANTED
douane_notes | yes | this field holds notes that can be filled by douane, leave this empty, as this should be touched only by players | | |  
bastion_clearance | yes | the clearance pips on the cards | | 0 to 3 | 1
icc_number | yes | this 15chr. long id in format "1234 12345 1234" must be an unique ICC number that does not occur elsewhere in the database. You can use the get figurant API to test for this. | each faction has their own first number, aquila = 7, dugo = 3, ekanesh = 8, pendzal = 9, sona = 5 | | 7345 34768 3457
bloodtype | yes | sets the blood type of the figurant | | O, A, B, AB | A
ic_birthday | yes | its the figurants birthdate, can be set in anyway they like | | | 24-10-231nt
homeplanet | yes | its the figurants homeplanet / planet they traveled from | does not check if the planet exists | current planets in game at time of creating documentation are listed below | Accipiter
recurring | no | changes status field from 'figurant' to 'figurant-recurring' | do not send along if not recurring | any value even empty | true

### List of all known planets sorted by faction ownership

| faction || planets ||||||||||||
|--|--|--|--|--|--|--|--|--|--|--|--|--|--
aquila || Accipiter | Alcyon | Alietum | Ferox II | Merula | Noctua | Sturnus | Viridis | Fastus | Ignis | Ithaginis | Tigris
dugo || Kaito | Batongbayal | Cabatu | Hideyoshi | Hiroto | Katsuro | Minoru | Shinobu | Tarou | Haruka | Noburu
ekanesh || Dzar
pendzal || Dodamu | Zvir | Ziamlia | Zorki | Vady | Cionma | Vtotoroy | Nadz | Ruda | Zyccio
sona || Andhera | Ghara | Prakhasa
  
