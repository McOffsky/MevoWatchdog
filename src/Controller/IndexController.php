<?php

namespace App\Controller;

use App\Entity\Bike;
use App\Entity\BikeEvent;
use App\Entity\BikeStatus;
use App\Repository\BikeEventRepository;
use App\Repository\BikeRepository;
use App\Repository\BikeStatusRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
    * @Route("/")
    */
    public function index(Request $request)
    {
        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->getDoctrine()->getRepository(Bike::class);

        /** @var BikeStatusRepository $statusRepo */
        $statusRepo = $this->getDoctrine()->getRepository(BikeStatus::class);

        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $timespan = $request->query->get("hours", 24);
        $city = $request->query->get("city", null);

        $context = [
            "activeSummary" => $statusRepo->getActiveSummary($timespan, $city),
            "availableSummary" => $statusRepo->getAvailableSummary($timespan, $city),
            "lastSeenActive" => $bikeRepo->getLastSeenActive($timespan, $city),
            "countAvailable2h" => $bikeRepo->getActiveCount(2, $city),
            "batteryStatus" => $bikeRepo->getBatteryStatus($timespan, $city),
            "locationChangeCount" => $statusRepo->getLocationChangeTimespanCount($timespan, null, $city),
            "locationChangeSummary" => $statusRepo->getLocationChangeSummary(7, $city),

            "lowBatteryCount" => $eventRepo->countByType(BikeEvent::LOW_BATTERY, $timespan, $city),
            "depletedBatteryCount" => $eventRepo->countByType(BikeEvent::DEPLETED_BATTERY, $timespan, $city),
            "replacedBatteryCount" => $eventRepo->countByType(BikeEvent::NEW_BATTERY, $timespan, $city),

            "knownBikesCount" => $bikeRepo->getKnownBikesCount($city),
            "events2h" => $eventRepo->getEvents(2, $city),

            "bikeDeclaration" => 1224,
            "timespan" => $timespan,
            "city" => $city,
            "knownCities" => $bikeRepo->getCities(),
        ];

        return $this->render('charts.html.twig', $context);
    }
}