<?php

namespace App\Entity;

use App\Repository\SensorMoistureRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SensorMoistureRepository::class)
 * @ORM\Table(name="sensorMoisture")
 */
class SensorMoistureEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $name;

    /**
     * @ORM\Column(type="string", name="sensorID", length=25)
     */
    private $sensorID;

    /**
     * @ORM\Column(type="string", name="sensorReading", length=20)
     */
    private $sensorReading;

    /**
     * @ORM\Column(type="datetime", name="insertDateTime")
     */
    private $insertDateTime;

    /**
     * @ORM\Column(type="string", length=10, name="batteryStatus", nullable=true)
     */
    private $batteryStatus;

    /**
     * @ORM\Column(type="string", length=255, name="sensorLocation", nullable=true)
     */
    private $sensorLocation;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSensorID(): ?string
    {
        return $this->sensorID;
    }

    public function setSensorID(string $sensorID): self
    {
        $this->sensorID = $sensorID;

        return $this;
    }

    public function getSensorReading(): ?string
    {
        return $this->sensorReading;
    }

    public function setSensorReading(string $sensorReading): self
    {
        $this->sensorReading = $sensorReading;

        return $this;
    }

    public function getInsertDateTime(): ?\DateTimeInterface
    {
        return $this->insertDateTime;
    }

    public function setInsertDateTime(\DateTimeInterface $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }

    public function getBatteryStatus(): ?string
    {
        return $this->batteryStatus;
    }

    public function setBatteryStatus(?string $batteryStatus): self
    {
        $this->batteryStatus = $batteryStatus;

        return $this;
    }

    public function getSensorLocation(): ?string
    {
        return $this->sensorLocation;
    }

    public function setSensorLocation(?string $sensorLocation): self
    {
        $this->sensorLocation = $sensorLocation;

        return $this;
    }

    /**
     * Get valid field names.
     *
     * @return string[]
     */
    public static function getValidSensorIDs(): array {
        return ['00e122', '00df09', '00e232'];
    }
}
