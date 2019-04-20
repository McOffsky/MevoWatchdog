<?php

namespace App\API;

class GdzieJestMevoClient
{
    const STATION_NAMES_ENDPOINT = "https://gdziejestmevo.pl/stationNames.json";

    /**
     * @return array
     */
    public function fetchStationNames()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => self::STATION_NAMES_ENDPOINT
        ]);

        $resp = curl_exec($curl);

        curl_close($curl);

        $data = json_decode($resp);
        $stationNames = [];

        foreach ($data as $val) {
            $stationNames[intval($val->id)] = $val->name;
        }

        return $stationNames;
    }
}