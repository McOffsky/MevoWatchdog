<?php

namespace App\Entity;

class RawStation
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $city;
    /**
     * @var float
     */
    private $lat;
    /**
     * @var float
     */
    private $lng;
    /**
     * @var integer
     */
    private $racks;
    /**
     * @var integer
     */
    private $freeRacks;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getRacks(): int
    {
        return $this->racks;
    }

    /**
     * @param int $racks
     */
    public function setRacks(int $racks): void
    {
        $this->racks = $racks;
    }

    /**
     * @return int
     */
    public function getFreeRacks(): int
    {
        return $this->freeRacks;
    }

    /**
     * @param int $freeRacks
     */
    public function setFreeRacks(int $freeRacks): void
    {
        $this->freeRacks = $freeRacks;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }


    /**
     * @return float
     */
    public function getLat(): float
    {
        return $this->lat;
    }

    /**
     * @param float $lat
     */
    public function setLat(float $lat): void
    {
        $this->lat = $lat;
    }

    /**
     * @return float
     */
    public function getLng(): float
    {
        return $this->lng;
    }

    /**
     * @param float $lng
     */
    public function setLng(float $lng): void
    {
        $this->lng = $lng;
    }

    public function getLocation(): string
    {
        return "".$this->lat."|".$this->lng;
    }
}