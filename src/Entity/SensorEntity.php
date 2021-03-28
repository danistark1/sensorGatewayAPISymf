<?php
/**
 * @author Dani Stark.
 */

namespace App\Entity;

use App\Repository\SensorRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SensorRepository::class)
 * @ORM\Table(name="sensorEntity")
 */
class SensorEntity {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $room;

    /**
     * @ORM\Column(type="float")
     */
    private $temperature;

    /**
     * @ORM\Column(type="float")
     */
    private $humidity;

    /**
     * @ORM\Column(type="integer")
     */
    private $station_id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $insert_date_time;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $battery_status;

    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getRoom(): ?string {
        return $this->room;
    }

    /**
     * @return float|null
     */
    public function getTemperature(): ?float {
        return $this->temperature;
    }

    /**
     * @return float|null
     */
    public function getHumidity(): ?float {
        return $this->humidity;
    }

    /**
     * @return int|null
     */
    public function getStationId(): ?int {
        return $this->station_id;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getInsertDateTime(): ?\DateTimeInterface {
        return $this->insert_date_time;
    }

    /**
     * @param string $room
     * @return $this
     */
    public function setRoom(string $room): self {
        $this->room = $room;
        return $this;
    }

    /**
     * @param float $temperature
     * @return $this
     */
    public function setTemperature(float $temperature): self {
        $this->temperature = $temperature;
        return $this;
    }

    /**
     * @param float $humidity
     * @return $this
     */
    public function setHumidity(float $humidity): self {
        $this->humidity = $humidity;
        return $this;
    }

    /**
     * @param int $station_id
     * @return $this
     */
    public function setStationId(int $station_id): self {
        $this->station_id = $station_id;
        return $this;
    }

    /**
     * @param \DateTimeInterface $insert_date_time
     * @return $this
     */
    public function setInsertDateTime(\DateTimeInterface $insert_date_time): self {
        $this->insert_date_time = $insert_date_time;
        return $this;
    }

    /**
     * Get valid field names.
     *
     * @return string[]
     */
    public static function getValidFieldNames(): array {
        return ['temperature', 'humidity', 'insert_date_time', 'room','station_id'];
    }

    public function getBatteryStatus(): ?int
    {
        return $this->battery_status;
    }

    public function setBatteryStatus(?int $battery_status): self
    {
        $this->battery_status = $battery_status;

        return $this;
    }
}
