<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BikeRepository")
 * @ORM\Table(indexes={@ORM\Index(name="code_idx", columns={"code"}), @ORM\Index(name="city_idx", columns={"lastSeenCity"}), @ORM\Index(name="seen_idx", columns={"lastSeenTimestamp"})})
 */
class Bike
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $lastSeenCity;

    /**
     * @ORM\Column(type="string", unique=true, length=10)
     */
    private $code;

    /**
     * @ORM\Column(type="integer")
     */
    private $battery;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $location;

    /**
     * @ORM\Column(type="integer")
     */
    private $lastSeenTimestamp;

    public function __construct()
    {
        $this->log = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getBattery(): ?int
    {
        return $this->battery;
    }

    public function setBattery(int $battery): self
    {
        $this->battery = $battery;

        return $this;
    }

    public function getLastSeenCity(): ?string
    {
        return $this->lastSeenCity;
    }

    public function setLastSeenCity(string $lastSeenCity): self
    {
        $this->lastSeenCity = $lastSeenCity;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getLastSeenTimestamp(): ?int
    {
        return $this->lastSeenTimestamp;
    }

    public function setLastSeenTimestamp(int $lastSeenTimestamp): self
    {
        $this->lastSeenTimestamp = $lastSeenTimestamp;

        return $this;
    }

    public function getLoc(): ?array
    {
        $data = explode("|", $this->location);

        return [floatval($data[1]), floatval($data[0])];
    }
}
