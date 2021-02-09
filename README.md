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

- MAILER_DSN=gmail+smtp://yoursendfromemail:yourpassword
- FROM_EMAIL=
- TO_EMAIL=
- EMAIL_TITLE_DAILY_REPORT="Weather Station Report"
- TIMEZONE="Default is America/Toronto"
- FIRST_REPORT_TIME="Default 07:00:00" (Sensor readings report first send time)
- SECOND_REPORT_TIME="Default 20:00:00"(Sensor readings report second send time)
- READING_REPORT_ENABLED=1 To enable/disable reading report.

![Weather Report](https://github.com/danistark1/weatherStationApiSymfony/blob/main/sampleEmail.png)

Readings from all configured sensors is sent in an email, twice a day (by default 07:00 AM and 08:00 PM, and can be configred using FIRST_REPORT_TIME & SECOND_REPORT_TIME).

 **Configuration (For sensor notifications report)**

Every configured sensor can have an upper, lower threshold. Config should start with 

- SENSOR_SENSORNAME_LOWER_TEMPERATURE=
- SENSOR_SENSORNAME_LOWER_HUMIDITY=
- SENSOR_SENSORNAME_UPPER_TEMPERATURE=
- SENSOR_SENSORNAME_UPPER_HUMIDITY=

- EMAIL_TITLE_NOTIFICATIONS="Weather Station Thresholds"

![Notification Report](https://github.com/danistark1/weatherStationApiSymfony/blob/main/notificationReport.png)

If lower or upper threshold is reached, an email is sent twice a day based on the configuration

- FIRST_NOTIFICATION_TIME="06:00:00"
- SECOND_NOTIFICATION_TIME="12:00:00"

**Debugging**

- DEBUG=1 To enable Monlog debugging.

**Sensor names/IDs**

Sensors should be configured using the format SENSOR_CONFIG_SENSORNAME = SENSORID

ex. SENSOR_CONFIG_ABC = 123 (sensor name ABC, ID 123)
Sensors names/IDs are then constructed as an array to be used in the application in
https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L276

My ex.

- SENSOR_CONFIG_BEDROOM=6126
- SENSOR_CONFIG_BASEMENT=3026
- SENSOR_CONFIG_GARAGE=8166
- SENSOR_CONFIG_LIVING_ROOM=15043
- SENSOR_CONFIG_OUTSIDE=12154

**Pruning**

Everytime a new record is added, report, logging & sensor readings data will be pruned based on the below configured intervals.

- SENSORS_RECORDS_INTERVAL=1
- READINGS_REPORT_INTERVAL=2
- LOGGER_DELETE_INTERVAL=1

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

### UnitTests

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
