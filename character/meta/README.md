
# GET api/orthanc/character/meta
Returns the skills of specified character

## Resource URL
api/orthanc/character/meta
  
## Resource Information
|||
|--|--|
|Response formats  | JSON  |
|Requires authentication?| Yes |
|Rate limited?  | No  |

## Parameters
| Name |  Required | Description | Default | Value |  Example
|--|--|--|--|--|--
token | yes | Provides authentication |
id | yes | id of the character whose metadata you want to lookup. | 
meta | no | Comma-delimited list of metadata(s) you want to lookup. If not specified, all metadata will be returned.
