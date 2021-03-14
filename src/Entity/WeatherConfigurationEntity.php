<?php

namespace App\Entity;

use App\Repository\WeatherConfigurationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WeatherConfigurationRepository::class)
 * @ORM\Table(name="weatherConfiguration")
 */
class WeatherConfigurationEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=200)
     */
    private $configKey;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $configValue;

    /**
     * @ORM\Column(type="date")
     */
    private $configDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfigKey(): ?string
    {
        return $this->configKey;
    }

    public function setConfigKey(string $configKey): self
    {
        $this->configKey = $configKey;

        return $this;
    }

    public function getConfigValue(): ?string
    {
        return $this->configValue;
    }

    public function setConfigValue(?string $configValue): self
    {
        $this->configValue = $configValue;

        return $this;
    }

    public function getConfigDate(): ?\DateTimeInterface
    {
        return $this->configDate;
    }

    public function setConfigDate(\DateTimeInterface $configDate): self
    {
        $this->configDate = $configDate;

        return $this;
    }
}
