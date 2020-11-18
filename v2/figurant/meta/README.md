# api/orthanc/v2/figurant/meta

|Method | Description |
| --- | ---
| GET | Gets meta for **id**
| POST | Adds **meta** for **id**
| DELETE | Removes **meta** from **id**
| UPDATE | Updates value of **meta** on **id**

## Resource URL
api/orthanc/v2/figurant/meta

## Resource Information
|||
|--|--|
|Response formats | JSON |
|Requires authentication?| Yes |
|Rate limited? | No |

## Parameters
| Name | Required | Description | Required For | Optional For | Example
|--|--|--|--|--|--
token | yes | provides authentication | All Methods
id | yes | figurant ID to add/update meta for | All Methods
meta | yes | sets the figurant details | POST, DELETE, UPDATE | GET | [</br>{"name":"test", "value":"eerste test"},</br>{"name":"test1", "value":"nog een test123"}</br>]

