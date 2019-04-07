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
     * @Route("/bike/{code}", name="bike_view")
     */
    public function bike(Request $request, $code)
    {
        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->getDoctrine()->getRepository(Bike::class);

        /** @var BikeStatusRepository $statusRepo */
        $statusRepo = $this->getDoctrine()->getRepository(BikeStatus::class);

        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $timespan = $request->query->get("hours", 24);

        $context = [
            'bike' => $bikeRepo->findBike($code),
            'events' => $eventRepo->getBikeEvents($code, $timespan),
            'batteryHistory' => $statusRepo->getBikeBatteryHistory($code, $timespan),
            'locationHistory' => $statusRepo->getBikeLocationHistory($code, $timespan),
            "timespan" => $timespan,
        ];

        return $this->render('bike.html.twig', $context);
    }
}