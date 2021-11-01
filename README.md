[![forthebadge](https://forthebadge.com/images/badges/open-source.svg)](https://forthebadge.com)

<p align="center">
    <b> 🌧 WirelessSensorGatewayAPI 🌧</b>
</p>
<p align="center">
    <img src="https://github.com/danistark1/weatherStationApiSymfony/blob/main/animatedCloud.gif" />
</p>

## 📔 What is it? ##

APIs for WirelessSensorGateway project https://github.com/danistark1/wirelessSensorGateway

## 💢 Table of Contents ##
 - [Setup](#setup "Setup")
 - [Debugging](#debugging "Debugging")
 - [Pruning](#pruning "Pruning")
 - [Caching](#caching "Caching")
 - [API Endpoints](#api-endpoints "API Endpoints")
 - [UnitTests](#unittests "UnitTests")

# Setup

- composer install
- bin/console doctrine:database:create
- bin/console doctrine:migrations:migrate

**.env**

DATABASE_URL=mysql://yourdbusername:yourdbpassword@youdbip:yourdbport(default 3306)/sensorGateway

 **Configuration (For sensor readings report)**
 
 MySQL Config in 
 https://github.com/danistark1/wirelessSensorGatewayAPI/blob/main/config

```
(env file)
MAILER_DSN=gmail+smtp://yoursendfromemail:yourpassword
```
admin-email
| Config Key  | Value | Function |
| ------------- | ------------- | ------------- |
| email-logging-enabled  | 1  | enable/disable email logging |
| email-logging-level  | ["info","critical","debug"..]  | event level to log |
| admin-email  | email address  | application's admin email address |
| weatherReport-notificationsReportEnabled  | 1  | Temp/Humidity notifications enabled/disabled  |
| weatherReport-fromEmail  |  fromEmail | Email to use for sending notifications|
| weatherReport-toEmail |  (configure this in the attributes field sensorConfiguration.attributes for multiple recipeints ["email1","email2","email3"..]) or just the config_value for single recipient | Email to send notifications to| 
| weatherReport-emailTitleDailyReport  | Weather Station Report | Title of report email that will be recieved |
| weatherReport-emailTitleNotifications | Weather Station notifications | Title of notifications email that will be recieved |
| report_type_report-firstEmailTime  | 07:00:00  | First report time |
| report_type_report-secondEmailTime  | 18:00:00  |  Second report time |
| report_type_notification-firstEmailTime  | 06:00:00  | First notification time |
| report_type_notification-secondEmailTime  | 17:00:00  | Second notifications time |
| weatherReport-disableEmails  | 0  | When set to 1, no email will be set, used when debugging|
| sensor-{sensorName}-upper-temperature | 34 | Used to set upper temp threshold for a certain sensor |
| sensor-{sensorName}-lower-temperature | in Celcius  | ------------- |
| sensor-{sensorName}-upper-humidity | 0  | ------------- |
| sensor-{sensorName}-lower-humidity | %  | ------------- |
| sensor-{sensorName}-lower-moisture | (0 to 100) | ------------- |
| sensor-{sensorName}-upper-moisture | 0  | ------------- |
|application-timezone|America/Toronto  | Application server timezone |
|application-debug| 0  | when set to one, log table will record application errors |
|application-version|2.0  | Sets the application version |
|pruning-records-interval| 1   | Sensors data pruning|
|pruning-logs-interval| 1  |  Log data pruning|
|pruning-moisture-interval| 1  |  Mositure data pruning|


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
| report_type_notification-firstEmailTime  | 06:00:00 |
| report_type_notification-secondEmailTime  | 17:00:00  |

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

When set to 1, any app error will be record in SensorLogger database table.

# Pruning

Everytime a new record is added, report, logging, sensor & moisture readings data will be pruned based on the below configured intervals.

| Config Key  | Value |
| ------------- | ------------- |
| pruning-report-interval | 1|
| pruning-records-interval | 1|
| pruning-logs-interval | 1|
| pruning-moisture-interval | 1|

ex. pruning-records-interval records are kept for 1 day, every new record after this will be deleted starting with the oldest.

# Caching

All configrations are cached. If a new config is added, cache gets cleared, if an already existing config is updated, cache key of that config is deleted.
You can however, reset all cache at once using the api 

DELETE /weatherstation/api/config/deletecache

# API Endpoints

**SensorController**

### Temp/Humidity

**GET**

**Get readings by Station ID**

- GET /weatherstation/api/id/{id}

**Get readings by Station Name**

- GET weatherstation/api/name/{name}

**Get by name ordered**

- GET /weatherstation/api/name/ordered

**Get by id ordered**

- GET /weatherstation/api/id/ordered/{id}

**POST**

**Post sensor readings**

- POST weatherstationapi/
- 
```json
    {
        "id": "",
        "temperature": ,
        "battery_status": ,
        "humidity": ,
        "room": "sensorName",
        "station_id": 6126
    }
```

**Expected post**

```json
{
    "room": "garage",
    "temperature": 3,
    "humidity": 67,
    "station_id": 6126
}
```

**DELETE**

- DELETE weatherstationapi/{interval}

Deletes all weather data older than interval (default is 1 day).

### Moisture

**POST**

 - POST /weatherstation/api

```json
    {
        "name": "sensorName",
        "batteryStatus": "",
        "sensorReading": "",
        "sensorID": "",
        "sensorLocation": "sensorLocation"
    }
```

### Cache

- DELETE /weatherstation/api/config/deletecache


**ConfigurationController**

**GET**

**Get config by key**

- GET /weatherstation/api/config/{key}

**UPDATE**

- PATCH /weatherstation/api/config/{key}/{value}

**DELETE**

- DELETE /weatherstation/api/config/deletecache

**POST**

- POST /weatherstation/api/config

Everytime a record is posted:

- Checks if an old record needs to be deleted (records older than the defined interval are deleted) [SensorController.php#L214](https://github.com/danistark1/weatherStationApiSymfony/blob/3264b8a09dfdf1c64fabc59e2ba96a0eaaafcffa/src/Controller/SensorController.php#L214)
- Checks if a weather report needs to be sent [PostListener.php#L54](https://github.com/danistark1/weatherStationApiSymfony/blob/5b274a2fa9e151e37a3793e3eb838863ccc673bd/src/Listeners/PostListener.php#L54)
- Deletes previous sensor readings reports
https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L209

### UnitTests (outdated)

| Test  | Tests | Result |
| ------------- | ------------- |------------- |
| ![testValidSensorControllerGetByID](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L27) | ![SensorController::getByID()](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L49)  | 200|
| ![testInvalidSensorControllerGetByID](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L39)  | ensorController::getByID()  | 400|
| ![testvalidSensorControllerGetByName](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L56)  | ![SensorController::getByName()](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/src/Controller/SensorController.php#L82)  | 200|
| ![testInvalidSensorControllerGetByName](https://github.com/danistark1/weatherStationApiSymfony/blob/156484b5324644c5e660769b4758c96557e65768/tests/SensorControllerTests.php#L69)  | SensorController::getByName()  | 400|

**☑️ ToDo Tests**

- SensorController::Delete()
- SensorController::Post()
- SensorController::ValidateStationID()
- SensorController::ValidateRoom()
- SensorController::ValidatePost()
- SensorController::NormalizeData()
- PostListener sensor readings report
