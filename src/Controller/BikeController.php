<?php

namespace App\Controller;

use App\Client\GdzieJestMevoClient;
use App\Command\FetchCommand;
use App\Entity\Bike;
use App\Entity\BikeEvent;
use App\Entity\BikeStatus;
use App\Entity\SystemVariable;
use App\Repository\BikeEventRepository;
use App\Repository\BikeRepository;
use App\Repository\BikeStatusRepository;
use App\Repository\SystemVariableRepository;
use App\Request\OSRMPathRequest;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BikeController extends BaseController
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
     * @Route("/rower/{code}", name="bike_view")
     */
    public function bike(Request $request, $code)
    {
        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->getDoctrine()->getRepository(Bike::class);

        /** @var BikeStatusRepository $statusRepo */
        $statusRepo = $this->getDoctrine()->getRepository(BikeStatus::class);

        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $timespan = $request->query->get("h", 24);

        if ($redirect = $this->getRedirect($request)) {
            return $redirect;
        }

        $bike = $bikeRepo->findBike($code);

        if (empty($bike)) {
            $referer = $request->headers->get('referer', '/');
            $request->getSession()->getFlashBag()->add('error', "Nie znaleziono roweru o numerze: ".$code);
            return $this->redirect($referer);
        }

        $context = [
            'bike' => $bike,
            'events' => $eventRepo->getBikeEvents($code, $timespan),

            "locationChangeCount" => $statusRepo->getLocationChangeTimespanCount($timespan, $code),
            "timespan" => $timespan,
        ];

        $response = $this->render('bike.html.twig', $context);
        $response->setSharedMaxAge(60);
        return $response;
    }

    /**
     * @Route("/rower/{code}/data.json", name="bike_data_view")
     */
    public function bikeData(Request $request, $code)
    {
        /** @var BikeStatusRepository $statusRepo */
        $statusRepo = $this->getDoctrine()->getRepository(BikeStatus::class);

        /** @var SystemVariableRepository $sysVarRepo */
        $sysVarRepo = $this->getDoctrine()->getRepository(SystemVariable::class);
        $lastUpdateTimestamp = $sysVarRepo->findOneBy(['name' => FetchCommand::UPDATE_TIMESTAMP_NAME]);
        $expireDatetime = new DateTime('@' . (intval($lastUpdateTimestamp->getValue()) + 60));

        $timespan = $request->query->get("h", 24);

        $mapPoints = $this->compileMapPoints($timespan, $code);
        $paths = $this->compilePaths($mapPoints);

        $context = [
            'points' => $mapPoints,
            'paths' => $paths,
            'batteryHistory' => $statusRepo->getBikeBatteryHistory($code, $timespan),
            "timespan" => $timespan,
        ];

        $response = new JsonResponse($context);
        $response->setExpires($expireDatetime);
        $response->setSharedMaxAge(60);
        $response->setVary(["Accept-Encoding"]);

        return $response;
    }

    /**
     * @param array $mapPoints
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function compilePaths(array $mapPoints)
    {
        $paths = [];

        foreach($mapPoints as $key => $point) {
            if (!empty($mapPoints[$key+1])) {
                $nextPoint = $mapPoints[$key+1];
                $pathRequest = new OSRMPathRequest($point['loc'], $nextPoint['loc']);

                $paths[] = $this->gmjClient->fetchPath($pathRequest);
            }
        }

        return $paths;
    }

    /**
     * @param integer $timespan
     * @param string $code
     * @return array
     */
    private function compileMapPoints($timespan, $code)
    {
        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->getDoctrine()->getRepository(Bike::class);

        /** @var BikeStatusRepository $statusRepo */
        $statusRepo = $this->getDoctrine()->getRepository(BikeStatus::class);

        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $points = $statusRepo->getBikePointsHistory($code, $timespan);


        $eventPoints = $eventRepo->getEventPoints($timespan, null, null, $code);
        foreach ($eventPoints as $loc => $eventPoint) {
            if(!empty($points[$loc])) {
                $points[$loc] = array_merge($points[$loc], $eventPoint);
            } else {
                $points[$loc] = $eventPoint;
            }
        }

        $bike = $bikeRepo->findBike($code);
        $bikePoint = [
            'loc' => $bike->getLoc(),
            'battery' => $bike->getBattery(),
            'time' => date("H:i / d-m-Y", $bike->getLastSeenTimestamp()),
            'current' => true,
        ];

        if(!empty($points[$bike->getLocation()])) {
            $points[$bike->getLocation()] = array_merge($points[$bike->getLocation()], $bikePoint);
        } else {
            $points[$bike->getLocation()] = $bikePoint;
        }

        return array_values($points);
    }
}