# orthanc/v2/chars_player/skills/

| Method | Description                            |
| ------ | -------------------------------------- |
| GET    | Gets skills for **id**                 |
<!-- | POST   | Adds **skills** for **id**             | -->
| DELETE | Removes **skills** from **id**         |
<!-- | PUT    | Replaces value of **skills** on **id** | -->

## Resource URL
orthanc/v2/chars_player/skills/

## Resource Information
|                          |      |
| ------------------------ | ---- |
| Response formats         | JSON |
| Requires authentication? | Yes  |
| Rate limited?            | No   |

## Parameters
| Name     | Required | Description                           | Required For | Optional For | Example  |
| -------- | -------- | ------------------------------------- | ------------ | ------------ | -------- |
| token    | yes      | provides authentication               | All Methods  |              | q1w2e3r4 |
| id       | yes      | Character ID to add/update skills for | All Methods  |              | 42       |
| skill_id | yes      | skill_id to maniplate for character   | DELETE       | 31014        |          |


