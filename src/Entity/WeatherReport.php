<?php

namespace App\Entity;

use App\Repository\WeatherReportRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WeatherReportRepository::class)
 */
class WeatherReport {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $emailBody;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $lastSentDate;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $lastSentTime;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lastSentCounter;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmailBody(): ?string
    {
        return $this->emailBody;
    }

    public function setEmailBody(?string $emailBody): self
    {
        $this->emailBody = $emailBody;

        return $this;
    }

    public function getLastSentDate(): ?\DateTimeInterface
    {
        return $this->lastSentDate;
    }

    public function setLastSentDate(?\DateTimeInterface $lastSentDate): self
    {
        $this->lastSentDate = $lastSentDate;

        return $this;
    }

    public function getLastSentTime(): ?\DateTimeInterface
    {
        return $this->lastSentTime;
    }

    public function setLastSentTime(?\DateTimeInterface $lastSentTime): self
    {
        $this->lastSentTime = $lastSentTime;

        return $this;
    }

    public function getLastSentCounter(): ?int
    {
        return $this->lastSentCounter;
    }

    public function setLastSentCounter(?int $lastSentCounter): self
    {
        $this->lastSentCounter = $lastSentCounter;

        return $this;
    }
}
