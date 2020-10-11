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
     * @ORM\Column(type="date")
     */
    private $insert_date_time;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): ?string
    {
        return $this->room;
    }

    public function setRoom(string $room): self
    {
        $this->room = $room;

        return $this;
    }

    public function getTemperature(): ?int
    {
        return $this->temperature;
    }

    public function setTemperature(int $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getHumidity(): ?int
    {
        return $this->humidity;
    }

    public function setHumidity(int $humidity): self
    {
        $this->humidity = $humidity;

        return $this;
    }

    public function getStationId(): ?int
    {
        return $this->station_id;
    }

    public function setStationId(int $station_id): self
    {
        $this->station_id = $station_id;

        return $this;
    }

    public function getInsertDateTime(): ?\DateTimeInterface
    {
        return $this->insert_date_time;
    }

    public function setInsertDateTime(\DateTimeInterface $insert_date_time): self
    {
        $this->insert_date_time = $insert_date_time;

        return $this;
    }
}
