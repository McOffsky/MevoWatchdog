<?php

namespace App\Client;

use App\Entity\Path;
use App\Request\OSRMPathRequest;
use Doctrine\ORM\EntityManager;

class GdzieJestMevoClient
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $stationNamesEndpoint;

    /**
     * @var string
     */
    protected $osrmEndpoint;

    /**
     * GdzieJestMevoClient constructor.
     * @param EntityManager $em
     * @param string $stationNamesEndpoint
     * @param string $osrmEndpoint
     */
    public function __construct(EntityManager $em, string $stationNamesEndpoint, string $osrmEndpoint)
    {
        $this->em = $em;
        $this->stationNamesEndpoint = $stationNamesEndpoint;
        $this->osrmEndpoint = $osrmEndpoint;
    }

    /**
     * @return array
     */
    public function fetchStationNames()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->stationNamesEndpoint
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

    /**
     * @param OSRMPathRequest $request
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function fetchPath(OSRMPathRequest $request)
    {
        $pathEntity = $this->em->getRepository(Path::class)->findOneBy(["identifier" => $request->getPathIdentifier()]);

        if (!empty($pathEntity)) {
            return json_decode($pathEntity->getPath());
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->osrmEndpoint.'/'.$request->getRequestParameters()
        ]);

        $path = curl_exec($curl);

        curl_close($curl);

        $pathEntity = new Path();
        $pathEntity->setIdentifier($request->getPathIdentifier());
        $pathEntity->setPath($path);

        $this->em->persist($pathEntity);
        $this->em->flush();

        return json_decode($pathEntity->getPath());
    }
}