# Orthanc v2
This version of orthanc follows the RESTful API methodology.
Descriptions of the methods are [here](https://www.restapitutorial.com/lessons/httpmethods.html)
Swagger examples on how to use the API are [here](https://api.eosfrontier.space/orthanc/swagger/)

## Testing Status
| Endpoint Type     | Endpoint                                                              | GET                | POST               | PUT                | PATCH              | DELETE             |
| ----------------- | --------------------------------------------------------------------- | ------------------ | ------------------ | ------------------ | ------------------ | ------------------ |
| All Characters    | [/orthanc/v2/chars_all/](/v2/chars_all/README.md)                     | :heavy_check_mark: | N/A                | N/A                | N/A                | N/A                |
| All Characters    | [/orthanc/v2/chars_all/meta/](/v2/chars_all/meta/README.md)           | :heavy_check_mark: | N/A                | N/A                | N/A                | N/A                |
| All Characters    | [/orthanc/v2/chars_all/skills/](/v2/chars_all/skills/README.md)       | :heavy_check_mark: | N/A                | N/A                | N/A                | N/A                |
| Player Characters | [/orthanc/v2/chars_player/](/v2/chars_player/README.md)               | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: |                    | :heavy_check_mark: |
| Player Characters | [/orthanc/v2/chars_player/meta/](/v2/chars_player/meta/README.md)     | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: |
| Player Characters | [/orthanc/v2/chars_player/skills/](/v2/chars_player/skills/README.md) | :heavy_check_mark: |                    |                    |                    | :heavy_check_mark: |
| Figurant          | [/orthanc/v2/chars_figu/](/v2/chars_figu/README.md)                   | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: |                    | :heavy_check_mark: |
| Figurant          | [/orthanc/v2/chars_figu/meta/](/v2/chars_figu/meta/README.md)         | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: |
| Figurant          | [/orthanc/v2/chars_figu/skills/](/v2/chars_figu/skills/README.md)     | :heavy_check_mark: |                    |                    |                    | :heavy_check_mark: |
| Joomla            | [/orthanc/v2/joomla/](/v2/joomla/README.md)                         | :heavy_check_mark: | :no_entry_sign:    | :no_entry_sign:    | :no_entry_sign:    | :no_entry_sign:    |
| event             | [/orthanc/v2/event/](/v2/event/README.md)                           | :heavy_check_mark: | :no_entry_sign:    | :no_entry_sign:    | :no_entry_sign:    | :no_entry_sign:    |


## Methods
| Method | Description                                              |
| ------ | -------------------------------------------------------- |
| GET    | Retrieves Data                                           |
| POST   | Creates a New Record                                     |
| PUT    | Updates Existing Record without validating existing data |
| PATCH  | Updates Existing Record after validating the old data    |
| DELETE | Deletes Record                                           |
