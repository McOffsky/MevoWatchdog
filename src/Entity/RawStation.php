<?php

namespace App\Entity;

class RawStation
{
    /**
     * @var integer
     */
    private $uid;
    /**
     * @var string
     */
    private $code;
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
    private $bikes;
    /**
     * @var integer
     */
    private $bookedBikes;

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

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     */
    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * @return int
     */
    public function getBikes(): int
    {
        return $this->bikes;
    }

    /**
     * @param int $bikes
     */
    public function setBikes(int $bikes): void
    {
        $this->bikes = $bikes;
    }

    /**
     * @return int
     */
    public function getBookedBikes(): int
    {
        return $this->bookedBikes;
    }

    /**
     * @param int $bookedBikes
     */
    public function setBookedBikes(int $bookedBikes): void
    {
        $this->bookedBikes = $bookedBikes;
    }
}
