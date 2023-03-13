
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
| Name                        | Description                     | Required For       | Optional For | Example                                                                                                                                                                                                                                                                                                                               |
| --------------------------- | ------------------------------- | ------------------ | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| token                       | provides authentication         | All Methods        |              |
| category                    | used to select a skill category.| GET                |              | Can be set to 'all' or a valid skill category. If you set it to an invalid skill category, a message will give you a list of valid options. |