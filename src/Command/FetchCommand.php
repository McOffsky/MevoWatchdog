<?php
namespace App\Command;

use App\API\MevoClient;
use App\Entity\Bike;
use App\Entity\BikeEvent;
use App\Entity\BikeRawStatus;
use App\Entity\BikeStatus;
use App\Entity\SystemVariable;
use App\Repository\BikeRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use DateTimeZone;
use DateTime;
use Exception;

class FetchCommand extends Command
{
    const LOCATION_CHANGE_TRESHOLD = 20; //m
    const UPDATE_TIMESTAMP_NAME = "update_timestamp";

    protected static $defaultName = 'mevo:fetch';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var MevoClient
     */
    protected $mevoClient;

    /**
     * FetchCommand constructor.
     * @param EntityManager $em
     * @param MevoClient $mevoClient
     */
    public function __construct(EntityManager $em, MevoClient $mevoClient)
    {
        $this->em = $em;
        $this->mevoClient = $mevoClient;

        parent::__construct();
    }

    /**
     * Get current DateTime
     *
     * @throws Exception
     */
    private function getNow()
    {
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone('UTC'));

        return $now;
    }

    /**
     * Get age limit for status log
     *
     * @throws Exception
     */
    private function getStatusAgeLimit()
    {
        $now = $this->getNow();
        $now->modify("-14 minutes");
        return $now;
    }

    /**
     * Store bike events based on diffrences between old and new bike status
     *
     * @param Bike $bike
     * @param BikeRawStatus $status
     * @throws ORMException
     */
    private function readEvents(Bike $bike, BikeRawStatus $status)
    {
        $now = $this->getNow();

        if ($bike->getBattery() <= 65 && $status->getBattery() > 85) {
            $bikeReloadedEvent = new BikeEvent();
            $bikeReloadedEvent->setType(BikeEvent::NEW_BATTERY);
            $bikeReloadedEvent->setTimestamp($now->getTimestamp());
            $bikeReloadedEvent->setBikeCode($bike->getCode());
            $bikeReloadedEvent->setCity($status->getCity());
            $bikeReloadedEvent->setLocation($status->getLocation());
            $this->em->persist($bikeReloadedEvent);
        }

        if ($bike->getBattery() > 20 && $status->getBattery() <= 20) {
            $bikeDepletedEvent = new BikeEvent();
            $bikeDepletedEvent->setType(BikeEvent::DEPLETED_BATTERY);
            $bikeDepletedEvent->setTimestamp($now->getTimestamp());
            $bikeDepletedEvent->setBikeCode($bike->getCode());
            $bikeDepletedEvent->setCity($status->getCity());
            $bikeDepletedEvent->setLocation($status->getLocation());
            $this->em->persist($bikeDepletedEvent);
            return;
        }

        if ($bike->getBattery() > 30 && $status->getBattery() <= 30) {
            $bikeLowEvent = new BikeEvent();
            $bikeLowEvent->setType(BikeEvent::LOW_BATTERY);
            $bikeLowEvent->setTimestamp($now->getTimestamp());
            $bikeLowEvent->setBikeCode($bike->getCode());
            $bikeLowEvent->setCity($status->getCity());
            $bikeLowEvent->setLocation($status->getLocation());
            $this->em->persist($bikeLowEvent);
            return;
        }
    }

    /**
     * Store bike status log
     *
     * @param Bike $bike
     * @param BikeRawStatus $status
     * @throws \Doctrine\ORM\ORMException
     */
    private function readStatus(Bike $bike, BikeRawStatus $status)
    {
        /** @var BikeStatus $lastStatus */
        $lastStatus = $this->em->getRepository(BikeStatus::class)->findLastStatusForBike($bike->getCode());
        $now = $this->getNow();

        if ((empty($lastStatus)
            || ($lastStatus->getBattery() + 5) <= $status->getBattery()
            || ($lastStatus->getBattery() - 5) >= $status->getBattery()
            || $lastStatus->getLocation() != $status->getLocation()
            || $lastStatus->getTimestamp() < $this->getStatusAgeLimit()->getTimestamp())
        ) {
            $bikeStatus = new BikeStatus();
            $bikeStatus->setBikeCode($bike->getCode());
            $bikeStatus->setTimestamp($now->getTimestamp());
            $bikeStatus->setBattery($status->getBattery());
            $bikeStatus->setLocation($status->getLocation());
            $bikeStatus->setCity($status->getCity());

            if (!empty($lastStatus)) {
                if ($status->getLocation() != $lastStatus->getLocation()
                 && $this->calcuateDistance($lastStatus->getLocation(), $status->getLocation()) > self::LOCATION_CHANGE_TRESHOLD
                ) {
                    $bikeStatus->setLocationChange(true);
                }
            }

            $this->em->persist($bikeStatus);
        }
    }

    /**
     * @param string $from
     * @param string $to
     * @param int $earthRadius (default value correct for lat 54')
     * @return float|int
     */
    private function calcuateDistance($from, $to, $earthRadius = 6364181)
    {
        $from = explode("|", $from);
        $to = explode("|", $to);

        // convert from degrees to radians
        $latFrom = deg2rad(floatval($from[0]));
        $lonFrom = deg2rad(floatval($from[1]));
        $latTo = deg2rad(floatval($to[0]));
        $lonTo = deg2rad(floatval($to[1]));

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);

        return $angle * $earthRadius;
    }

    /**
     * @throws ORMException
     */
    private function generateUpdateTimestamp()
    {
        $lastFetchTimestamp = $this->em->getRepository(SystemVariable::class)->findOneBy(['name' => self::UPDATE_TIMESTAMP_NAME]);

        if (empty($lastFetchTimestamp)) {
            $lastFetchTimestamp = new SystemVariable();
            $lastFetchTimestamp->setName(self::UPDATE_TIMESTAMP_NAME);
        }

        $lastFetchTimestamp->setValue($this->getNow()->getTimestamp());
        $this->em->persist($lastFetchTimestamp);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bikesStatuses = $this->mevoClient->fetchBikes();

        /** @var BikeRepository $bikeRepo */
        $bikeRepo = $this->em->getRepository(Bike::class);

        /** @var BikeRawStatus $status */
        foreach($bikesStatuses as $code => $status) {
            $bike = $bikeRepo->findBike($code);
            $now = $this->getNow();

            if (empty($bike)) {
                $bike = new Bike();
                $bike->setCode($code);
            } else {
                // Bikes sometimes report 0 battery for no reason. If that occurs, use last known battery level instead.
                if ($status->getBattery() == 0 && !empty($bike->getBattery())) {
                    $status->setBattery($bike->getBattery());
                }

                $this->readEvents($bike,$status);
            }

            $this->readStatus($bike,$status);

            $bike->setLastSeenTimestamp($now->getTimestamp());
            $bike->setLastSeenCity($status->getCity());
            $bike->setBattery($status->getBattery());
            $bike->setLocation($status->getLocation());
            $this->em->persist($bike);
        }

        $this->generateUpdateTimestamp();

        $this->em->flush();

        echo count($bikesStatuses)."\n";
    }
}