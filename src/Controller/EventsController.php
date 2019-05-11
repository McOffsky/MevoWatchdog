<?php

namespace App\Controller;

use App\Command\FetchCommand;
use App\Entity\Bike;
use App\Entity\BikeEvent;
use App\Entity\SystemVariable;
use App\Repository\BikeEventRepository;
use App\Repository\BikeRepository;
use App\Repository\SystemVariableRepository;
use DateTime;
use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EventsController extends BaseController
{
    /** @var MobileDetector */
    protected $mobileDetector;

    /**
     * BikeController constructor.
     * @param MobileDetector $mobileDetector
     */
    public function __construct(MobileDetector $mobileDetector)
    {
        $this->mobileDetector = $mobileDetector;
    }

    /**
     * @Route("/dziennik-zdarzen", name="events_view")
     */
    public function events(Request $request)
    {
        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->getDoctrine()->getRepository(Bike::class);

        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $timelimit = $this->mobileDetector->isMobile() ? 12 : 24;

        $timespan = $request->query->get("h", $timelimit);
        $city = $request->query->get("c", null);
        $type = $request->query->get("t", null);

        if ($redirect = $this->getRedirect($request)) {
            return $redirect;
        }

        $context = [
            "events" => $eventRepo->getEvents($timespan, $city, $type),
            "timespan" => $timespan,
            "city" => $city,
            "type" => $type,
            "knownCities" => $bikeRepo->getCities()
        ];

        $response = $this->render('events.html.twig', $context);
        $response->setSharedMaxAge(60);

        return $response;
    }

    /**
     * @Route("/event_map_data.json", name="event_map_data")
     */
    public function eventPoints(Request $request)
    {
        /** @var BikeEventRepository $eventRepo */
        $eventRepo = $this->getDoctrine()->getRepository(BikeEvent::class);

        $timespan = $request->query->get("h", 24);
        $city = $request->query->get("c", null);
        $type = $request->query->get("t", null);

        /** @var SystemVariableRepository $sysVarRepo */
        $sysVarRepo = $this->getDoctrine()->getRepository(SystemVariable::class);
        $lastUpdateTimestamp = $sysVarRepo->findOneBy(['name' => FetchCommand::UPDATE_TIMESTAMP_NAME]);
        $expireDatetime = new DateTime('@' . (intval($lastUpdateTimestamp->getValue()) + 60));

        $context = [
            "points" => $eventRepo->getEventPoints($timespan, $city, $type),
        ];

        $response = new JsonResponse($context);
        $response->setExpires($expireDatetime);
        $response->setSharedMaxAge(60);
        $response->setVary(["Accept-Encoding"]);

        return $response;
    }
}