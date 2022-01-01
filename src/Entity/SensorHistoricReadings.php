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
    private $insertDateLowest;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $insertDateHighest;

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

    public function getLowestReading(): ?float
    {
        return $this->lowestReading;
    }

    public function setLowestReading( $lowestReading): self
    {
        $this->lowestReading = $lowestReading;

        return $this;
    }

    public function getHighestReading(): ?float
    {
        return $this->highestReading;
    }

    public function setHighestReading($highestReading): self
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

    public function getInsertDateLowest(): ?\DateTimeInterface
    {
        return $this->insertDateLowest;
    }

    public function setInsertDateLowest(\DateTimeInterface $insertDateLowest): self
    {
        $this->insertDateLowest = $insertDateLowest;

        return $this;
    }

    public function getInsertDateHighest(): ?\DateTimeInterface
    {
        return $this->insertDateHighest;
    }

    public function setInsertDateHighest(?\DateTimeInterface $insertDateHighest): self
    {
        $this->insertDateHighest = $insertDateHighest;

        return $this;
    }
}
