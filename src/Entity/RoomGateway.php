<?php

namespace App\Entity;

use App\Repository\RoomGatewayRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RoomGatewayRepository::class)
 */
class RoomGateway
{
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
     * @ORM\Column(type="integer")
     */
    private $temperature;

    /**
     * @ORM\Column(type="integer")
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
     * @return int|null
     */
    public function getTemperature(): ?int {
        return $this->temperature;
    }

    /**
     * @return int|null
     */
    public function getHumidity(): ?int {
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
     * @param int $humidity
     * @return $this
     */
    public function setHumidity(int $humidity): self {
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
}
