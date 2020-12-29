# weatherStationApiSymfony

Symfony REST APIs for the weatherStation project https://github.com/danistark1/weatherStation

# Setup

- composer install
- bin/console doctrine:database:create
- bin/console doctrine:migrations:migrate

.env

DATABASE_URL=mysql://root:@database:3306/weatherStation

# Email Configuration (For weather report)

- MAILER_DSN=gmail+smtp://yoursendfromemail:yourpassword
- FROM_EMAIL=
- TO_EMAIL=
- EMAIL_TITLE="Your weather report title"

# Sample weather report
![Weather Report](https://github.com/danistark1/weatherStationApiSymfony/blob/main/sampleEmail.png)

# Usage / REST API Calls

**POST**

- POST weatherstationapi/

Everytime a record is posted, a call to 

**DELETE**

- DELETE weatherstationapi/{interval}

that deletes all weather data older interval (default is 1 day).

ex.
```json
{
    "room": "outside",
    "temperature": 3,
    "humidity": 45,
    "station_id": 6126
}
```

**GET**

- GET weatherstationapi/{station_ID} by_station_id
- GET weatherstationapi/{name} by_room_name


