# orthanc/v2/options/

| Method | Description                                                                                           |
| ------ | ----------------------------------------------------------------------------------------------------- |
| GET    | Gets option for **id**                                                                                  |
| POST   | Adds **option name** for **id**                                                                         |
| PUT    | Replaces value of **option name** on **id**                                                             |
| PATCH  | Updates value of **option name** on **id** after validating that value in DB matches **option old_value** |
| DELETE | Removes **option** from **id**                                                                          |

## Resource URL
orthanc/v2/options/

## Resource Information
|                          |      |
| ------------------------ | ---- |
| Response formats         | JSON |
| Requires authentication? | Yes  |
| Rate limited?            | No   |

## Parameters
| Name      | Description                                                                                                                  | Required For      | Optional For | Example                                                                                                                                                       |
| --------- | ---------------------------------------------------------------------------------------------------------------------------- | ----------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| token     | provides authentication                                                                                                      | All Methods       |              |                                                                                                                                                               |
| id        | Character ID to add/update option for                                                                                          | All Methods       |              |                                                                                                                                                               |
| option      | option payload to create new, modify existing option (overwrite/no validation) or delete option.                                   | POST, PUT, DELETE |              | [</br>{"name":"test", "value":"eerste test"},</br>{"name":"test1", "value":"nog een test123"}</br>]                                                           |
| option      | option payload to modify existing option. Put starting value into old_value to validate that the value in the DB hasn't changed. | PATCH             |              | [</br>{"name":"test", "old_value":"eerste test", "value":"tweede test"},</br>{"name":"test1", "old_value":"nog een test123", "value":"nog een test456"}</br>] |
| option_name | name of option to return                                                                                                       |                   | GET          | test                                                                                                                                                          |
| wildcard  | set this header to enable wildcards in option_name                                                                             |                   | GET          | set this variable. value not needed. Once it's set, you can include wildcard `%` in option_name                                                                 |
