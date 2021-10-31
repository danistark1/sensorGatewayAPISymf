<?php
/**
 * @author Dani Stark(danistark1.ca@gmail.com).
 */
namespace App\DataFixtures;

use App\Entity\SensorEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use DateTimeZone;
use App\Controller\SensorController;

/**
 * Class SensorFixtures
 * @deprecated Fields used here are outdated.
 * @package App\DataFixtures
 */
class SensorFixtures extends Fixture {
    public function load(ObjectManager $manager) {
        foreach ( self::getSensorFixtureData() as $sensorName => $sensorID) {
            $sensorEntity =  new SensorEntity();
            $sensorEntity->setRoom($sensorName);
            $sensorEntity->setStationId($sensorID);
            $randTemp = rand(-40, 40);
            $randHumidity = rand(10, 99);
            $sensorEntity->setHumidity($randHumidity);
            $sensorEntity->setTemperature($randTemp);
            $dt = new \DateTime();
            $dt->format('Y-m-d H:i:s');
            $dt->setTimezone(new DateTimeZone('America/Toronto'));
            $sensorEntity->setInsertDateTime($dt);
            $manager->persist($sensorEntity);
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
