<p align="center">
    <b> 🌧 weatherStationApiSymfony 🌧</b>
</p>
<p align="center">
    <img src="https://github.com/danistark1/weatherStationApiSymfony/blob/main/animatedCloud.gif" />
</p>


Symfony REST APIs for the weatherStation project https://github.com/danistark1/weatherStation

# Setup

- composer install
- bin/console doctrine:database:create
- bin/console doctrine:migrations:migrate

**.env**

DATABASE_URL=mysql://yourdbusername:yourdbpassword@youdbip:yourdbport(default 3306)/weatherStation

 **Configuration (For sensor readings report)**

```
(env file)
MAILER_DSN=gmail+smtp://yoursendfromemail:yourpassword
```
(weatherConfiguration table

| Config Key  | Value |
| ------------- | ------------- |
| weatherReport-readingReportEnabled  | 1  |
| weatherReport-notificationsReportEnabled  | 1  |
| weatherReport-fromEmail  |   |
| weatherReport-toEmail |   |
| weatherReport-emailTitleDailyReport  | Weather Station Report |
| weatherReport-emailTitleNotifications | Weather Station notifications |
| weatherReport-firstReportTime  | 07:00:00  |
| weatherReport-secondReportTime  | 18:00:00  |
| weatherReport-firstNotificationTime  | 06:00:00  |
| weatherReport-secondNotificationTime  | 17:00:00  |
| weatherReport-thirdNotificationTime  | 18:00:00 |
| weatherReport-disableEmails  | 0  |



![Weather Report](https://github.com/danistark1/weatherStationApiSymfony/blob/main/sampleEmail.png)

Readings from all configured sensors is sent in an email, twice a day (by default 07:00 AM and 08:00 PM, and can be configred using firstNotificationTime & secondReportTime).

 **Configuration (For sensor notifications report)**

Every configured sensor can have an upper, lower threshold.

| Config Key  | Value |
| ------------- | ------------- |
| sensor-bedroom-upper-temperature  | 25  |
| sensor-bedroom-upper-humidity  | 45  |
| sensor-bedroom-lower-temperature  | 17  |
| sensor-bedroom-lower-humidity  | 29  |


![Notification Report](https://github.com/danistark1/weatherStationApiSymfony/blob/main/notificationReport.png)

If lower or upper temp/humidity threshold is reached, an email is sent twice a day based on the configuration


| Config Key  | Value |
| ------------- | ------------- |
| weatherReport-firstNotificationTime  | 06:00:00 |
| weatherReport-secondNotificationTime  | 17:00:00  |


**Sensor names/IDs**


My ex.


| Config Key  | Value |
| ------------- | ------------- |
| sensor-config-bedroom | 6126|
| sensor-config-garage  | 8166  |
| sensor-config-living_room  | 15043  |
| sensor-config-basement  | 3026  |
| sensor-config-outside  | 12154  |

# Debugging


| Config Key  | Value |
| ------------- | ------------- |
| application-debug | 1|


# Pruning

Everytime a new record is added, report, logging & sensor readings data will be pruned based on the below configured intervals.

| Config Key  | Value |
| ------------- | ------------- |
| pruning-report-interval | 1|
| pruning-records-interval | 1|
| pruning-logs-interval | 1|

# Caching

Configration GETs are all cached. If a new config is added, cache gets cleated, if an already existing config is updated, cache key of that config is deleted.

# Usage / REST API Calls

**GET**

**Get readings by Station ID**

- GET weatherstationapi/{station_ID} by_station_id

**Get readings by Station Name**

- GET weatherstationapi/{name}

**POST**

**Post sensor readings**

- POST weatherstationapi/

**Expected post**

```json
{
    "room": "garage",
    "temperature": 3,
    "humidity": 67,
    "station_id": 6126
}
```

Everytime a record is posted:

- Checks if an old record needs to be deleted (records older than the defined interval are deleted) [SensorController.php#L214](https://github.com/danistark1/weatherStationApiSymfony/blob/3264b8a09dfdf1c64fabc59e2ba96a0eaaafcffa/src/Controller/SensorController.php#L214)
- Checks if a weather report needs to be sent [PostListener.php#L54](https://github.com/danistark1/weatherStationApiSymfony/blob/5b274a2fa9e151e37a3793e3eb838863ccc673bd/src/Listeners/PostListener.php#L54)
- Deletes previous sensor readings reports
https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L209

**DELETE**

- DELETE weatherstationapi/{interval}

Deletes all weather data older than interval (default is 1 day).

### UnitTests(needs updating)

| Test  | Tests | Result |
| ------------- | ------------- |------------- |
| ![testValidSensorControllerGetByID](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L27) | ![SensorController::getByID()](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L49)  | 200|
| ![testInvalidSensorControllerGetByID](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L39)  | ensorController::getByID()  | 400|
| ![testvalidSensorControllerGetByName](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L56)  | ![SensorController::getByName()](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L82)  | 200|
| ![testInvalidSensorControllerGetByName](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L69)  | SensorController::getByName()  | 400|

**TODO Tests**

- SensorController::Delete()
- SensorController::Post()
- SensorController::ValidateStationID()
- SensorController::ValidateRoom()
- SensorController::ValidatePost()
- SensorController::NormalizeData()
- PostListener sensor readings report

[![forthebadge](https://forthebadge.com/images/badges/open-source.svg)](https://forthebadge.com)
