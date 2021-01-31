<?php
/**
 * @author Dani Stark(danistark1.ca@gmail.com).
 */
namespace App\Tests;
use App\Controller\SensorController;
use App\DataFixtures\SensorFixtures;
use App\Entity\SensorEntity;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SensorControllerTests
 *
 * @package App\Tests
 */
class SensorControllerTests extends AbstractControllerTest {

    /**
     * Test SensorController::getByID()
     *
     * @dataProvider provideSensorControllerData
     * @param int $stationID
     * @param string $expectedSensorName
     */
    public function testValidSensorControllerGetByID(int $stationID, string $expectedSensorName): void {
        $this->loadFixture(new SensorFixtures());
        self::$client->request('GET', '/weatherstation/api/id/'.$stationID);
        $response = self::$client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseContent = json_decode($response->getContent())[0];
        $this->assertEquals($expectedSensorName, $responseContent->room);
    }

    /**
     * Test SensorController::getByID() with Invalid sensor ID.
     */
    public function testInvalidSensorControllerGetByID(): void {
        $randomID = 1234567;
        self::$client->request('GET', '/weatherstation/api/id/'.$randomID);
        $response = self::$client->getResponse();
        $responseCode = $response->getStatusCode();
        $responseMsg = $response->getContent();
        $this->assertEquals(SensorController::STATUS_VALIDATION_FAILED, $responseCode);
        $this->assertEquals(SensorController::VALIDATION_STATION_ID, $responseMsg);
    }

    /**
     * Test SensorController::getByName()
     *
     * @dataProvider provideSensorControllerData
     * @param  int $stationID
     * @param string $expectedSensorName
     */
    public function testValidSensorControllerGetByName(int $stationID, string $expectedSensorName): void {
        $this->loadFixture(new SensorFixtures());
        self::$client->request('GET', '/weatherstation/api/name/'.$expectedSensorName);
        $response = self::$client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseContent = json_decode($response->getContent())[0];
        $this->assertEquals($stationID, $responseContent->stationId);
    }


    /**
     * Test SensorController::getByName() with Invalid room name.
     */
    public function testInvalidSensorControllerGetByName(): void {
        $randomName = 'abcdefg';
        self::$client->request('GET', '/weatherstation/api/name/'.$randomName);
        $response = self::$client->getResponse();
        $responseCode = $response->getStatusCode();
        $responseMsg = $response->getContent();
        $this->assertEquals(SensorController::STATUS_VALIDATION_FAILED, $responseCode);
        $this->assertEquals(SensorController::VALIDATION_STATION_NAME, $responseMsg);
    }

    /**
     * Sensor Data Provider
     *
     * @return array[]
     */
    public function provideSensorControllerData(): array {
        // construct sensor data array using configured sensor IDs.
        $configuredSensorData = SensorFixtures::getSensorFixtureData();
        $testReadyData = [];
        $counter = sizeof($configuredSensorData);
        foreach($configuredSensorData as $name => $sensorID) {
            $testReadyData += [
                'sensor'.$counter =>[(int)$sensorID, $name]
            ];
            $counter--;
        }
        return $testReadyData;
    }
}
