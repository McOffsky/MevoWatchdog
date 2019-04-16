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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
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

        if ($bikeCode = $request->query->get("bike", false)) {
            return $this->redirectToRoute("bike_view", ['code' => $bikeCode]);
        }

        /** @var SystemVariableRepository $sysVarRepo */
        $sysVarRepo = $this->getDoctrine()->getRepository(SystemVariable::class);
        $lastUpdateTimestamp = $sysVarRepo->findOneBy(['name' => FetchCommand::UPDATE_TIMESTAMP_NAME]);

        $context = [
            "availableSummary" => $statusRepo->getAvailableSummary($timespan, $city),
            "lastSeenActive" => $bikeRepo->getLastSeenActive($timespan, $city),
            "countAvailable2h" => $bikeRepo->getActiveCount(2, $city),
            "batteryStatus" => $bikeRepo->getBatteryStatus($timespan, $city),
            "locationChangeCount" => $statusRepo->getLocationChangeTimespanCount($timespan, null, $city),
            "locationChangeSummary" => $statusRepo->getLocationChangeSummary($timespan, $city),
            "locationChangeDailySummary" => $statusRepo->getLocationChangeDailySummary(7, $city),

            "lowBatteryCount" => $eventRepo->countByType(BikeEvent::LOW_BATTERY, $timespan, $city),
            "depletedBatteryCount" => $eventRepo->countByType(BikeEvent::DEPLETED_BATTERY, $timespan, $city),
            "replacedBatteryCount" => $eventRepo->countByType(BikeEvent::NEW_BATTERY, $timespan, $city),
            "replacedBatterySummary" => $eventRepo->summaryByType(BikeEvent::NEW_BATTERY, 7, $city),

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
}