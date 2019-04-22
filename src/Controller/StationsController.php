<?php

namespace App\Controller;

use App\Client\GdzieJestMevoClient;
use App\Command\FetchCommand;
use App\Entity\Bike;
use App\Entity\BikeEvent;
use App\Entity\BikeStatus;
use App\Entity\Station;
use App\Entity\SystemVariable;
use App\Repository\BikeEventRepository;
use App\Repository\BikeRepository;
use App\Repository\BikeStatusRepository;
use App\Repository\StationRepository;
use App\Repository\SystemVariableRepository;
use App\Request\OSRMPathRequest;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StationsController extends BaseController
{
    /** @var GdzieJestMevoClient */
    protected $gmjClient;

    /**
     * BikeController constructor.
     * @param GdzieJestMevoClient $gmjClient
     */
    public function __construct(GdzieJestMevoClient $gmjClient)
    {
        $this->gmjClient = $gmjClient;
    }

    /**
     * @Route("/stacje", name="stations_map_view")
     */
    public function stations(Request $request)
    {
        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->getDoctrine()->getRepository(Bike::class);

        /** @var StationRepository $stationRepo */
        $stationRepo = $this->getDoctrine()->getRepository(Station::class);

        $timespan = $request->query->get("h", 6);
        $city = $request->query->get("c", null);

        if ($redirect = $this->getRedirect($request)) {
            return $redirect;
        }

        /** @var SystemVariableRepository $sysVarRepo */
        $sysVarRepo = $this->getDoctrine()->getRepository(SystemVariable::class);
        $lastUpdateTimestamp = $sysVarRepo->findOneBy(['name' => FetchCommand::UPDATE_TIMESTAMP_NAME]);

        $context = [
            "topStations" => $stationRepo->getMostActiveStations($timespan, $city, 10),
            "timespan" => $timespan,
            "city" => $city,
            "knownCities" => $bikeRepo->getCities(),
            "lastUpdate" => $lastUpdateTimestamp->getValue(),
        ];

        $response = $this->render('stations.html.twig', $context);
        $response->setSharedMaxAge(60);

        return $response;
    }

    /**
     * @Route("/stations_map_data.json", name="stations_map_data")
     */
    public function stationMapPoints(Request $request)
    {
        /** @var StationRepository $stationRepo */
        $stationRepo = $this->getDoctrine()->getRepository(Station::class);

        $timespan = $request->query->get("h", 6);
        $city = $request->query->get("c", null);

        /** @var SystemVariableRepository $sysVarRepo */
        $sysVarRepo = $this->getDoctrine()->getRepository(SystemVariable::class);
        $lastUpdateTimestamp = $sysVarRepo->findOneBy(['name' => FetchCommand::UPDATE_TIMESTAMP_NAME]);
        $expireDatetime = new DateTime('@' . (intval($lastUpdateTimestamp->getValue()) + 60));

        $context = [
            'points' => $stationRepo->getStationPoints($timespan, $city),
        ];

        $response = new JsonResponse($context);
        $response->setExpires($expireDatetime);
        $response->setSharedMaxAge(60);
        $response->setVary(["Accept-Encoding"]);

        return $response;
    }

    /**
     * @Route("/stacja/{code}", name="station_view")
     */
    public function station(Request $request, $code)
    {
        /** @var BikeStatusRepository $statusRepo */
        $statusRepo = $this->getDoctrine()->getRepository(BikeStatus::class);

        /** @var StationRepository $stationRepo */
        $stationRepo = $this->getDoctrine()->getRepository(Station::class);

        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $timespan = $request->query->get("h", 24);

        if ($redirect = $this->getRedirect($request)) {
            return $redirect;
        }

        /** @var Station $station */
        $station = $stationRepo->findOneBy(["code" => $code]);

        /** @var SystemVariableRepository $sysVarRepo */
        $sysVarRepo = $this->getDoctrine()->getRepository(SystemVariable::class);
        $lastUpdateTimestamp = $sysVarRepo->findOneBy(['name' => FetchCommand::UPDATE_TIMESTAMP_NAME]);

        $context = [
            "station" => $station,
            "bikes" => $statusRepo->getBikesByLocation($timespan, $station->getLocation()),
            "events" => $eventRepo->getLocationEvents($station->getLocation(), $timespan),
            "lastUpdate" => $lastUpdateTimestamp->getValue(),
            "timespan" => $timespan
        ];

        $response = $this->render('station.html.twig', $context);
        $response->setSharedMaxAge(60);

        return $response;
    }

    /**
     * @Route("/stacja/{code}/data.json", name="station_data_view")
     */
    public function stationData(Request $request, $code)
    {
        /** @var BikeStatusRepository $statusRepo */
        $statusRepo = $this->getDoctrine()->getRepository(BikeStatus::class);

        /** @var StationRepository $stationRepo */
        $stationRepo = $this->getDoctrine()->getRepository(Station::class);

        $timespan = $request->query->get("h", 24);

        /** @var Station $station */
        $station = $stationRepo->findOneBy(["code" => $code]);

        /** @var SystemVariableRepository $sysVarRepo */
        $sysVarRepo = $this->getDoctrine()->getRepository(SystemVariable::class);
        $lastUpdateTimestamp = $sysVarRepo->findOneBy(['name' => FetchCommand::UPDATE_TIMESTAMP_NAME]);
        $expireDatetime = new DateTime('@' . (intval($lastUpdateTimestamp->getValue()) + 60));

        $connections = $statusRepo->getLocationConnections($station->getLocation(), $timespan);

        $context = [
            "bikesSummary" => $statusRepo->getLocationSummary($station->getLocation(), $timespan),
            "connections" => $this->addPaths($station, $connections),
            "timespan" => $timespan
        ];

        $response = new JsonResponse($context);
        $response->setExpires($expireDatetime);
        $response->setSharedMaxAge(60);
        $response->setVary(["Accept-Encoding"]);

        return $response;
    }

    /**
     * @param Station $station
     * @param array $connections
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function addPaths(Station $station, array $connections)
    {
        $points = [];

        foreach($connections as $point) {
            $pathRequest = new OSRMPathRequest($station->getLoc(), $point['loc']);

            $point['route'] = $this->gmjClient->fetchPath($pathRequest);
            $points[] = $point;
        }

        return $points;
    }
}