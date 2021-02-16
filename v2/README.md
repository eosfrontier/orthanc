# Orthanc v2
This version of orthanc follows the RESTful API methodology.
Descriptions of the methods are [here](https://www.restapitutorial.com/lessons/httpmethods.html)

## Testing Status
| Endpoint Type | Endpoint                                                        | GET                | POST               | PUT                | PATCH              | DELETE             |
| ------------- | --------------------------------------------------------------- | ------------------ | ------------------ | ------------------ | ------------------ | ------------------ |
| Characters    | [/orthanc/v2/character/](/v2/character/README.md)               | -[] | :heavy_check_mark: |                    |                    | :heavy_check_mark: |
| Characters    | [/orthanc/v2/character/meta/](/v2/character/meta/README.md)     | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: |
| Characters    | [/orthanc/v2/character/skills/](/v2/character/skills/README.md) | :heavy_check_mark: |                    |                    |                    | :heavy_check_mark: |
| Figurant      | [/orthanc/v2/figurant/](/v2/figurant/README.md)                 | :heavy_check_mark: | :heavy_check_mark: |                    |                    | :heavy_check_mark: |
| Figurant      | [/orthanc/v2/figurant/meta/](/v2/figurant/meta/README.md)       | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: |
| Figurant      | [/orthanc/v2/figurant/skills/](/v2/figurant/skills/README.md)   | :heavy_check_mark: |                    |                    |                    | :heavy_check_mark: |


## Methods
| Method | Description                                              |
| ------ | -------------------------------------------------------- |
| GET    | Retrieves Data                                           |
| POST   | Creates a New Record                                     |
| PUT    | Updates Existing Record without validating existing data |
| PATCH  | Updates Existing Record after validating the old data    |
| DELETE | Deletes Record                                           |
