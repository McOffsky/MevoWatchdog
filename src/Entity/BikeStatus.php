<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BikeStatusRepository")
 * @ORM\Table(indexes={@ORM\Index(name="code_idx", columns={"bikeCode"}), @ORM\Index(name="timestamp_idx", columns={"timestamp"}), @ORM\Index(name="city_idx", columns={"city"})})
 */
class BikeStatus
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     */
    private $battery;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $bikeCode;

    /**
     * @ORM\Column(type="integer")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $location;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $city;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getBikeCode(): ?string
    {
        return $this->bikeCode;
    }

    public function setBikeCode(string $bikeCode): self
    {
        $this->bikeCode = $bikeCode;

        return $this;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(float $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLng(): ?float
    {
        return $this->lng;
    }

    public function setLng(float $lng): self
    {
        $this->lng = $lng;

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

    public function getLoc(): ?array
    {
        $data = explode("|", $this->location);

        return [floatval($data[1]), floatval($data[0])];
    }
}
