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

class EventsController extends AbstractController
{
    /**
     * @Route("/events", name="events_view")
     */
    public function bike(Request $request)
    {
        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->getDoctrine()->getRepository(Bike::class);

        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $timespan = $request->query->get("hours", 24);
        $city = $request->query->get("city", null);
        $type = $request->query->get("type", null);

        $context = [
            "events" => $eventRepo->getEvents($timespan, $city, $type),
            "points" => $eventRepo->getEventPoints($timespan, $city, $type),
            "timespan" => $timespan,
            "city" => $city,
            "type" => $type,
            "knownCities" => $bikeRepo->getCities()
        ];

        return $this->render('events.html.twig', $context);
    }
}