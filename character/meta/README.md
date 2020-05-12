
  

# GET api/orthanc/character/meta
Returns the metadata of specified character

  

## Resource URL
api/orthanc/character/meta

## Resource Information

|||
|--|--|
|Response formats | JSON |
|Requires authentication?| Yes |
|Rate limited? | No |

  

## Parameters
| Name | Required | Description | Default | Value | Example
|--|--|--|--|--|--
token | yes | Provides authentication |
id | yes | id of the character whose metadata you want to lookup. |
meta | no | Comma-delimited list of metadata(s) you want to lookup. If not specified, all metadata will be returned. | | meta1,meta2,meta3

  
  

# PUT api/orthanc/character/meta/update.php
Returns the metadata(s) of specified character

## Resource URL
api/orthanc/character/meta/update.php

## Resource Information
|||
|--|--|
|Response formats | JSON |
|Requires authentication?| Yes |
|Rate limited? | No |

## Parameters
| Name | Required | Description | Default | Value | Example
|--|--|--|--|--|--
token | yes | Provides authentication |
id | yes | id of the character whose metadata you want to lookup. |
meta | yes | Comma-delimited list of metadata(s) you want to add/update. If not specified, all metadata will be returned. | | | [{"name":"test", "value":"eerste test"}, {"name":"test1", "value":"nog een test123"}]