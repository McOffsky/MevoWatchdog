<?php

namespace App\API;

use App\Entity\BikeRawStatus;
use App\Entity\RawStation;
use App\Entity\SystemVariable;
use Doctrine\ORM\EntityManager;

class MevoClient
{
    const MEVO_MAP_PAGE_URL = "https://rowermevo.pl/mapa-stacji/";
    const MEVO_LOCATIONS_URL = "https://rowermevo.pl/locations.js";
    const MEVO_KEY_VAR = "mevo_key_var";

    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0',
        'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:52.0) Gecko/20100101 Firefox/52.0',
        'Mozilla/5.0 (Linux; U; Android 2.2) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
        'Mozilla/5.0 (Linux; U; Android 4.1.2; de-de; GT-I8190 Build/JZO54K) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30',
        'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; SM-T110 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30',
        'Mozilla/5.0 (Linux; U; Android 4.2.2;pl-pl; Lenovo S5000-F/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.2.2 Mobile Safari/534.300',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 12_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 12_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; en-en) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0.1 Safari/605.1.15',
    ];

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * MevoClient constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Read bikes from Mevo response. Returns array, bike ID is an array key.
     *
     * @param $locations
     * @return array
     */
    public function fetchData()
    {
        $locations = $this->fetchLocations();

        $matches = [];

        preg_match_all("/'(.*)'/imU", $locations, $matches);

        $stations = json_decode($matches[1][0]);

        $bikesInSystem = [];
        $stationsInSystem = [];

        foreach ($stations[0]->places as $station) {
            if (!$station->bike && $station->spot) {
                $stationsInSystem[$station->name] = $this->readStation($station);
            }

            if (!empty($station->bike_list)) {
                foreach ($station->bike_list as $bike) {
                    $bikeStatus = new BikeRawStatus();
                    $bikeStatus->setCode($bike->number);
                    $bikeStatus->setBattery($bike->pedelec_battery);
                    $bikeStatus->setCity($station->city);
                    $bikeStatus->setLat($station->lat);
                    $bikeStatus->setLng($station->lng);

                    $bikesInSystem[$bike->number] = $bikeStatus;
                }
            }
        }

        return ["bikes" => $bikesInSystem, "stations" => $stationsInSystem];
    }

    /**
     * Read bikes from Mevo response. Returns array, bike ID is an array key.
     *
     * @param $locations
     * @return array
     */
    public function fetchStations()
    {
        $locations = $this->fetchLocations();

        $matches = [];

        preg_match_all("/'(.*)'/imU", $locations, $matches);

        $stations = json_decode($matches[1][0]);

        $stationsInSystem = [];

        foreach ($stations[0]->places as $station) {
            if (!$station->bike && $station->spot) {
                $stationsInSystem[$station->name] = $this->readStation($station);
            }
        }

        return $stationsInSystem;
    }

    /**
     * @param $station
     * @return RawStation
     */
    private function readStation($station)
    {
        $stationStatus = new RawStation();
        $stationStatus->setUid($station->uid);
        $stationStatus->setCode($station->name);
        $stationStatus->setLat($station->lat);
        $stationStatus->setLng($station->lng);
        $stationStatus->setCity($station->city);
        $stationStatus->setBikes($station->bikes);
        $stationStatus->setBookedBikes($station->booked_bikes);
        $stationStatus->setRacks($station->bike_racks);

        return $stationStatus;
    }

    /**
     * Get locations.js file form mevo page
     *
     * @return string
     */
    private function fetchLocations()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => self::MEVO_LOCATIONS_URL . "?" . $this->getKey(),
            CURLOPT_USERAGENT => array_rand($this->userAgents)
        ]);

        $resp = curl_exec($curl);
        $respCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($respCode == 403) {
            $this->fetchKeyFromMevo();
            return $this->fetchLocations();
        }

        return $resp;
    }

    /**
     *  Get key form the database, if none found, fetch key form Mevo website
     *
     *  @return string
     */
    private function getKey()
    {
        /** @var SystemVariable $mevoKey */
        $mevoKey = $this->em->getRepository(SystemVariable::class)->findOneBy(['name' => self::MEVO_KEY_VAR]);

        if (empty($mevoKey)) {
            $this->fetchKeyFromMevo();
            return $this->getKey();
        }

        return $mevoKey->getValue();
    }

    /**
     * Parse map page for access code, and store it in database
     */
    private function fetchKeyFromMevo()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => self::MEVO_MAP_PAGE_URL,
            CURLOPT_USERAGENT => array_rand($this->userAgents)
        ]);

        $resp = curl_exec($curl);
        curl_close($curl);

        $match = [];
        preg_match('/locations.js\?(.*)\"><\/script>/', $resp, $match);

        $code = $match[1];
        $mevoKey = $this->em->getRepository(SystemVariable::class)->findOneBy(['name' => self::MEVO_KEY_VAR]);

        if (empty($mevoKey)) {
            $mevoKey = new SystemVariable();
            $mevoKey->setName(self::MEVO_KEY_VAR);
        }

        $mevoKey->setValue($code);
        $this->em->persist($mevoKey);
        $this->em->flush();

        return;
    }
}