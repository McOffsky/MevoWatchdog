<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StationRepository")
 * @ORM\Table(indexes={@ORM\Index(name="location_idx", columns={"location"}), @ORM\Index(name="name_idx", columns={"name"}), @ORM\Index(name="city_idx", columns={"city"})})
 */
class Station
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
    private $city;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $location;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $racks;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $freeRacks;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRacks(): ?int
    {
        return $this->racks;
    }

    public function setRacks(int $racks): self
    {
        $this->racks = $racks;

        return $this;
    }

    public function getFreeRacks(): ?int
    {
        return $this->freeRacks;
    }

    public function setFreeRacks(?int $freeRacks): self
    {
        $this->freeRacks = $freeRacks;

        return $this;
    }
}
