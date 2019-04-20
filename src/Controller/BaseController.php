<?php

namespace App\Controller;

use App\Entity\Station;
use App\Repository\StationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class BaseController extends AbstractController
{
    public function getRedirect(Request $request)
    {
        if ($bikeCode = $request->query->get("bike", false)) {
            return $this->redirectToRoute("bike_view", ['code' => $bikeCode]);
        }

        if ($location = $request->query->get("location", false)) {
            /** @var StationRepository $stationRepo */
            $stationRepo = $this->getDoctrine()->getRepository(Station::class);

            if ($station = $stationRepo->findOneBy(["location" => $location])) {
                return $this->redirectToRoute("station_view", ['code' => $station->getCode()]);
            }

            $referer = $request->headers->get('referer', '/');
            $request->getSession()->getFlashBag()->add('error', "Nie znaleziono stacji o koordynatach: ".$location);

            return new RedirectResponse($referer);
        }

        return false;
    }
}