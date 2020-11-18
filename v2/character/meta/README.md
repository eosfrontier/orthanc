
  

# GET api/orthanc/v2/character/meta
Returns the metadata of specified character

## Resource URL
api/orthanc/v2/character/meta

## Resource Information

|Response formats |JSON|
|---|---|
|Requires authentication?| Yes |
|Rate limited? | No |

  

## Parameters
| Name | Required | Description | Example
|---|---|---|---|
token | yes | Provides authentication | q1w2e3r4t5y6
id | yes | id of the character whose metadata you want to lookup. | 42
meta | no | Comma-delimited list of metadata(s) you want to lookup, add, or update. </br> If not specified, all metadata will be returned. |  [{"name":"test", "value":"eerste test"}, {"name":"test1", "value":"nog een test123"}]

## Methods/Response

| METHOD | Description
| --- | --- 
| GET | Returns the metadata(s) of specified character
| POST | Adds new meta to a character.
| DELETE | Removes meta from a character
| PUT | Replaces contents of meta on a character

