<?php

namespace App\Controller;

use App\Command\FetchCommand;
use App\Entity\Bike;
use App\Entity\BikeEvent;
use App\Entity\BikeStatus;
use App\Entity\SystemVariable;
use App\Repository\BikeEventRepository;
use App\Repository\BikeRepository;
use App\Repository\BikeStatusRepository;
use App\Repository\SystemVariableRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;

class IndexController extends BaseController
{
    /**
    * @Route("/", name="charts_view")
    */
    public function index(Request $request)
    {
        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->getDoctrine()->getRepository(Bike::class);

        /** @var BikeStatusRepository $statusRepo */
        $statusRepo = $this->getDoctrine()->getRepository(BikeStatus::class);

        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $timespan = $request->query->get("h", 24);
        $city = $request->query->get("c", null);

        if ($redirect = $this->getRedirect($request)) {
            return $redirect;
        }

        /** @var SystemVariableRepository $sysVarRepo */
        $sysVarRepo = $this->getDoctrine()->getRepository(SystemVariable::class);
        $lastUpdateTimestamp = $sysVarRepo->findOneBy(['name' => FetchCommand::UPDATE_TIMESTAMP_NAME]);

        $context = [
            "countAvailable2h" => $bikeRepo->getActiveCount(2, $city),
            "locationChangeCount" => $statusRepo->getLocationChangeTimespanCount($timespan, null, $city),

            "lowBatteryCount" => $eventRepo->countByType(BikeEvent::LOW_BATTERY, $timespan, $city),
            "depletedBatteryCount" => $eventRepo->countByType(BikeEvent::DEPLETED_BATTERY, $timespan, $city),
            "replacedBatteryCount" => $eventRepo->countByType(BikeEvent::NEW_BATTERY, $timespan, $city),

            "knownBikesCount" => $bikeRepo->getKnownBikesCount($city),
            "events2h" => $eventRepo->getEvents(2, $city),

            "bikeDeclaration" => 1224,
            "timespan" => $timespan,
            "lastUpdate" => $lastUpdateTimestamp->getValue(),
            "city" => $city,
            "knownCities" => $bikeRepo->getCities(),
        ];

        $response = $this->render('charts.html.twig', $context);
        $response->setSharedMaxAge(60);

        return $response;
    }

    /**
     * @Route("/chart_data.js", name="chart_data_view")
     */
    public function chartData(Request $request)
    {
        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->getDoctrine()->getRepository(Bike::class);

        /** @var BikeStatusRepository $statusRepo */
        $statusRepo = $this->getDoctrine()->getRepository(BikeStatus::class);

        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $timespan = $request->query->get("h", 24);
        $city = $request->query->get("c", null);

        /** @var SystemVariableRepository $sysVarRepo */
        $sysVarRepo = $this->getDoctrine()->getRepository(SystemVariable::class);
        $lastUpdateTimestamp = $sysVarRepo->findOneBy(['name' => FetchCommand::UPDATE_TIMESTAMP_NAME]);
        $expireDatetime = new DateTime('@' . (intval($lastUpdateTimestamp->getValue()) + 60));


        $context = [
            "availableSummary" => $statusRepo->getAvailableSummary($timespan, $city),
            "lastSeenActive" => $bikeRepo->getLastSeenActive($timespan, $city),
            "batteryStatus" => $bikeRepo->getBatteryStatus($timespan, $city),
            "locationChangeSummary" => $statusRepo->getLocationChangeSummary($timespan, $city),
            "locationChangeDailySummary" => $statusRepo->getLocationChangeDailySummary(7, $city),
            "replacedBatterySummary" => $eventRepo->summaryByType(BikeEvent::NEW_BATTERY, 7, $city),

            "bikeDeclaration" => 1224,
            "timespan" => $timespan,
        ];

        $response = new JsonResponse($context);

        $response->setExpires($expireDatetime);
        $response->setSharedMaxAge(60);
        $response->setVary(["Accept-Encoding"]);

        return $response;
    }
}