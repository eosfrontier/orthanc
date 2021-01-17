# api/orthanc/v2/character/meta

| Method | Description                         |
| ------ | ----------------------------------- |
| GET    | Gets meta for **id**                |
| POST   | Adds **meta** for **id**            |
| DELETE | Removes **meta** from **id**        |
| PATCH | Updates value of **meta** on **id** |

## Resource URL
api/orthanc/v2/character/meta

## Resource Information
|                          |      |
| ------------------------ | ---- |
| Response formats         | JSON |
| Requires authentication? | Yes  |
| Rate limited?            | No   |

## Parameters
| Name  | Required | Description                         | Required For         | Optional For | Example                                                                                             |
| ----- | -------- | ----------------------------------- | -------------------- | ------------ | --------------------------------------------------------------------------------------------------- |
| token | yes      | provides authentication             | All Methods          |
| id    | yes      | Character ID to add/update meta for | All Methods          |
| meta  | yes      | sets the character details          | POST, DELETE, PATCH | GET          | [</br>{"name":"test", "value":"eerste test"},</br>{"name":"test1", "value":"nog een test123"}</br>] |

