<?php

namespace App\Entity;

use App\Repository\SensorHistoricReadingsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SensorHistoricReadingsRepository::class)
 * @ORM\Table(name="sensorHistoricReadings")
 */
class SensorHistoricReadings
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lowestReading;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $highestReading;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $insertDateTime;

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

    public function getLowestReading(): ?int
    {
        return $this->lowestReading;
    }

    public function setLowestReading(?int $lowestReading): self
    {
        $this->lowestReading = $lowestReading;

        return $this;
    }

    public function getHighestReading(): ?int
    {
        return $this->highestReading;
    }

    public function setHighestReading(?int $highestReading): self
    {
        $this->highestReading = $highestReading;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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
}
