<?php
/**
 * @author Dani Stark(danistark1.ca@gmail.com).
 */
namespace App\DataFixtures;

use App\Entity\RoomGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use DateTimeZone;
use App\Controller\SensorController;

/**
 * Class SensorFixtures
 *
 * @package App\DataFixtures
 */
class SensorFixtures extends Fixture {
    public function load(ObjectManager $manager) {
        foreach ( self::getSensorFixtureData() as $sensorName => $sensorID) {
            $roomGateway =  new RoomGateway();
            $roomGateway->setRoom($sensorName);
            $roomGateway->setStationId($sensorID);
            $randTemp = rand(-40, 40);
            $randHumidity = rand(10, 99);
            $roomGateway->setHumidity($randHumidity);
            $roomGateway->setTemperature($randTemp);
            $dt = new \DateTime();
            $dt->format('Y-m-d H:i:s');
            $dt->setTimezone(new DateTimeZone('America/Toronto'));
            $roomGateway->setInsertDateTime($dt);
            $manager->persist($roomGateway);
            $manager->flush();
        }
    }

    /**
     * Get configurable sensor data.
     *
     * @return int[]
     */
    public static function getSensorFixtureData(): array {
        return SensorController::constructSensorData();
    }
}
