<p align="center">
    <b> 🌧 weatherStationApiSymfony 🌧</b>
</p>
<p align="center">
    <img src="https://github.com/danistark1/weatherStationApiSymfony/blob/main/animatedCloud.gif" />
</p>


Symfony REST APIs for the weatherStation project https://github.com/danistark1/weatherStation

### UnitTests

| Test  | Tests | Result |
| ------------- | ------------- |------------- |
| ![testValidSensorControllerGetByID](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L27) | ![SensorController::getByID()](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L49)  | 200|
| ![testInvalidSensorControllerGetByID](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L39)  | ensorController::getByID()  | 400|
| ![testvalidSensorControllerGetByName](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L56)  | ![SensorController::getByName()](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L82)  | 200|
| ![testInvalidSensorControllerGetByName](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L82)  | SensorController::getByName()  | 400|

TODO (SensorController::Delete(), SensorController::Post()



# Setup

- composer install
- bin/console doctrine:database:create
- bin/console doctrine:migrations:migrate

**.env**

DATABASE_URL=mysql://yourdbusername:yourdbpassword@youdbip:yourdbport(default 3306)/weatherStation

 **Email Configuration (For sensor readings report)**

- MAILER_DSN=gmail+smtp://yoursendfromemail:yourpassword
- FROM_EMAIL=
- TO_EMAIL=
- EMAIL_TITLE="Your sensor readings report title"
- TIMEZONE="Default is America/Toronto"
- FIRST_REPORT_TIME="Default 07:00:00" (Sensor readings report first send time)
- SECOND_REPORT_TIME="Default 20:00:00"(Sensor readings report second send time)
- KEEP_RECORDS_FOR=1 (Everytime a new record is added to the db, readings older than the defined interval will be deleted)

![Weather Report](https://github.com/danistark1/weatherStationApiSymfony/blob/main/sampleEmail.png)

Readings from all configured sensors is sent in an email, twice a day (by default 07:00 AM and 08:00 PM, and can be configred using FIRST_REPORT_TIME & SECOND_REPORT_TIME).

**Sensor names/IDs**

Sensors should be configured using the format SENSOR_SENSORNAME = SENSORID

ex. SENSOR_ABC = 123 (sensor name ABC, ID 123)
Sensors names/IDs are then constructed as an array to be used in the application in
https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L276

My ex.

- SENSOR_BEDROOM=6126
- SENSOR_BASEMENT=3026
- SENSOR_GARAGE=8166
- SENSOR_LIVING_ROOM=15043
- SENSOR_OUTSIDE=12154

# Usage / REST API Calls

**GET**

**Get readings by Station ID**

- GET weatherstationapi/{station_ID} by_station_id

Defined IDs in

[SensorController.php#L36](https://github.com/danistark1/weatherStationApiSymfony/blob/3264b8a09dfdf1c64fabc59e2ba96a0eaaafcffa/src/Controller/SensorController.php#L36)

```php
    // Room IDs
    const STATION_ID_BEDROOM = 6126;
    const STATION_ID_BASEMENT = 3026;
    const STATION_ID_LIVING_ROOM = 15043;
    const STATION_ID_OUTSIDE = 12154;
    const STATION_ID_GARAGE = 8166;
```

**Get readings by Station Name**

- GET weatherstationapi/{name}

Defined names in

[SensorController.php#L29](https://github.com/danistark1/weatherStationApiSymfony/blob/3264b8a09dfdf1c64fabc59e2ba96a0eaaafcffa/src/Controller/SensorController.php#L29)

```php
    // Room Names
    const ROOM_BEDROOM = 'bedroom';
    const ROOM_GARAGE = 'garage';
    const ROOM_LIVING_ROOM = 'living-room';
    const ROOM_BASEMENT = 'basement';
    const ROOM_OUTSIDE = 'outside';
```

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

**DELETE**

- DELETE weatherstationapi/{interval}

Deletes all weather data older than interval (default is 1 day).

[![forthebadge](https://forthebadge.com/images/badges/open-source.svg)](https://forthebadge.com)
