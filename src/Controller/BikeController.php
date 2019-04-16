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

class BikeController extends AbstractController
{
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

        if ($bikeCode = $request->query->get("bike", false)) {
            return $this->redirectToRoute("bike_view", ['code' => $bikeCode]);
        }

        $context = [
            'bike' => $bikeRepo->findBike($code),
            'events' => $eventRepo->getBikeEvents($code, $timespan),
            'mapPoints' => $this->compileMapPoints($timespan, $code),
            'locationHistory' => $statusRepo->getBikeLocationHistory($code, $timespan),

            'batteryHistory' => $statusRepo->getBikeBatteryHistory($code, $timespan),
            "locationChangeCount" => $statusRepo->getLocationChangeTimespanCount($timespan, $code),
            "timespan" => $timespan,
        ];

        $response = $this->render('bike.html.twig', $context);
        $response->setSharedMaxAge(60);
        return $response;
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