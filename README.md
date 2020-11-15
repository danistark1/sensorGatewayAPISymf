# weatherStationApiSymfony

# Installation

- composer install
- bin/console doctrine:database:create
- bin/console doctrine:migrations:migrate

Symfony REST APIs for the weatherStation project https://github.com/danistark1/weatherStation


# REST API Calls

- POST weatherstationapi/

Everytime a record is posted, a call to 

- DELETE weatherstationapi/{interval}

that deletes all weather data older interval (default is 1 day).

ex.
{
    "room": "outside",
    "temperature": 3,
    "humidity": 45,
    "station_id": 6126
}

- GET weatherstationapi/{station_ID} by_station_id
- GET weatherstationapi/{name} by_room_name


