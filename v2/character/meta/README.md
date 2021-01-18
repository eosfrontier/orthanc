# api/orthanc/v2/character/meta

| Method | Description                          |
| ------ | ------------------------------------ |
| GET    | Gets meta for **id**                 |
| POST   | Adds **meta** for **id**             |
| PUT    | Replaces value of **meta** on **id** |
| DELETE | Removes **meta** from **id**         |

## Resource URL
api/orthanc/v2/character/meta

## Resource Information
|                          |      |
| ------------------------ | ---- |
| Response formats         | JSON |
| Requires authentication? | Yes  |
| Rate limited?            | No   |

## Parameters
| Name      | Description                                      | Required For | Optional For | Example                                                                                             |
| --------- | ------------------------------------------------ | ------------ | ------------ | --------------------------------------------------------------------------------------------------- |
| token     | provides authentication                          | All Methods  |              |                                                                                                     |
| id        | Character ID to add/update meta for              | All Methods  |              |                                                                                                     |
| meta      | sets the character details                       | POST, DELETE |              | [</br>{"name":"test", "value":"eerste test"},</br>{"name":"test1", "value":"nog een test123"}</br>] |
| meta      | sets the character details                       | PATCH        |              | [</br>{"name":"test", "old_value":"eerste test", "value":"tweede test"},</br>{"name":"test1", "old_value":"nog een test123", "value":"nog een test456"}</br>] |
| meta_name | name of meta to return                           |              | GET          | test                                                                                                |
| wildcard  | set this header to enable wildcards in meta_name |              | GET          | set this variable. value not needed. Once it's set, you can include wildcard `%` in meta_name       |
