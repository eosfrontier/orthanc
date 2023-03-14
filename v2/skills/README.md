
# orthanc/v2/skills/
Performs a simple health check to ensure the API is available and has a healthy database connection

## Resource URL
orthanc/v2/skills/


## Resource Information
|                          |      |
| ------------------------ | ---- |
| Response formats         | JSON |
| Requires authentication? | Yes  |
| Rate limited?            | No   |

| Method | Description               |
| ------ | ------------------------- |
| GET    | Retrieve skills lists |


## Parameters
| Name                        | Description                                | Required For                   | Optional For | Example                                                                                                                                                                                                                                                                                                                               |
| --------------------------- | ------------------------------------------ | ------------------------------ | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| token                       | provides authentication                    | All Methods                    |              |
| category                    | used to GET skills by category.            | GET (unless skill_id specified)|              | Can be set to 'all' or a valid skill category. If you set it to an invalid skill category, a message will give you a list of valid options. |
| skill_id                    | used to GET a skill by its id.             | GET (unless category_specified)|              | 31022 |
| include_disabled            | will unhide disabled skills in GET results |                                | GET          |value doesn't need to be set|

> NOTE: for GET, you must specific category **OR** skill_id. Never both.