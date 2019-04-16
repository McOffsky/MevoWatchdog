<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BikeEventRepository")
 * @ORM\Table(indexes={@ORM\Index(name="code_idx", columns={"bikeCode"}), @ORM\Index(name="type_idx", columns={"type"}), @ORM\Index(name="timestamp_idx", columns={"timestamp"}), @ORM\Index(name="location_idx", columns={"location"}), @ORM\Index(name="city_idx", columns={"city"})})
 */
class BikeEvent
{
    const NEW_BATTERY = "new_battery";
    const LOW_BATTERY = "low_battery";
    const DEPLETED_BATTERY = "depleted_battery";

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $bikeCode;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $location;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity($city): self
    {
        $this->city = $city;

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
        if (empty($this->location)) {
            return null;
        }

        $data = explode("|", $this->location);

        return [floatval($data[1]), floatval($data[0])];
    }
}
