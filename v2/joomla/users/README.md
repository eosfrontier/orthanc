
# orthanc/v2/joomla/users
returns of list of joomla users belonging to group_id

## Resource URL
orthanc/v2/joomla/users

## Resource Information
|                          |      |
| ------------------------ | ---- |
| Response formats         | JSON |
| Requires authentication? | Yes  |
| Rate limited?            | No   |

| Method | Description                        |
| ------ | ---------------------------------- |
| GET    | Returns Joomla session information |

## Parameters
| Name     | Description                                                       | Required For | Optional For | Example |
| -------- | ----------------------------------------------------------------- | ------------ | ------------ | ------- |
| token    | provides authentication                                           | All Methods  |              |         |
| group_id | id of [group](../groups/README.md) whose users you want a list of | GET          | 2            |
