# Orthanc v2
This version of orthanc follows the RESTful API methodology.
Descriptions of the methods are [here](https://www.restapitutorial.com/lessons/httpmethods.html)

## Testing Status
| Endpoint Type | Endpoint                      | GET                | POST           | DELETE             | PUT    | UPDATE         |
| ------------- | ----------------------------- | ------------------ | -------------- | ------------------ | ------ | -------------- |
| Characters    | /orthanc/v2/character/        | :heavy_check_mark: | :construction: | :heavy_check_mark: | Needed |
| Characters    | /orthanc/v2/character/meta/   | :heavy_check_mark: |                | :construction:     |        | :construction: |
| Characters    | /orthanc/v2/character/skills/ | :heavy_check_mark: |                |                    |        |
| Figurant      | /orthanc/v2/figurant/         | :heavy_check_mark: | :construction: | :heavy_check_mark: |
| Figurant      | /orthanc/v2/figurant/meta/    | :heavy_check_mark: |                | :construction:     |        | :construction: |
| Figurant      | /orthanc/v2/figurant/skills/  | :heavy_check_mark: | :construction: | :heavy_check_mark: |        |
