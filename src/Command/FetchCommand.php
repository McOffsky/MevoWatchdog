<?php
namespace App\Command;

use App\API\MevoClient;
use App\Entity\Bike;
use App\Entity\BikeEvent;
use App\Entity\BikeRawStatus;
use App\Entity\BikeStatus;
use App\Repository\BikeRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

class FetchCommand extends Command
{
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
     * @throws \Exception
     */
    private function getNow()
    {
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('UTC'));

        return $now;
    }

    /**
     * Get age limit for status log
     *
     * @throws \Exception
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
     * @throws \Doctrine\ORM\ORMException
     */
    private function readEvents(Bike $bike, BikeRawStatus $status)
    {
        $now = $this->getNow();

        if ($bike->getBattery() <= 30 && $bike->getBattery() > 0 && $status->getBattery() > 85) {
            $bikeReloadedEvent = new BikeEvent();
            $bikeReloadedEvent->setType(BikeEvent::NEW_BATTERY);
            $bikeReloadedEvent->setTimestamp($now->getTimestamp());
            $bikeReloadedEvent->setBikeCode($bike->getCode());
            $bikeReloadedEvent->setCity($status->getCity());
            $this->em->persist($bikeReloadedEvent);
        }

        if ($bike->getBattery() > 20 && $status->getBattery() <= 20 && $status->getBattery() > 0) {
            $bikeDepletedEvent = new BikeEvent();
            $bikeDepletedEvent->setType(BikeEvent::DEPLETED_BATTERY);
            $bikeDepletedEvent->setTimestamp($now->getTimestamp());
            $bikeDepletedEvent->setBikeCode($bike->getCode());
            $bikeDepletedEvent->setCity($status->getCity());
            $this->em->persist($bikeDepletedEvent);
            return;
        }

        if ($bike->getBattery() > 30 && $status->getBattery() <= 30 && $status->getBattery() > 0) {
            $bikeLowEvent = new BikeEvent();
            $bikeLowEvent->setType(BikeEvent::LOW_BATTERY);
            $bikeLowEvent->setTimestamp($now->getTimestamp());
            $bikeLowEvent->setBikeCode($bike->getCode());
            $bikeLowEvent->setCity($status->getCity());
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
        /** @var BikeStatus $statusRepo */
        $lastStatus = $this->em->getRepository(BikeStatus::class)->findLastStatusForBike($bike->getCode());
        $now = $this->getNow();

        if ((empty($lastStatus)
            || ($lastStatus->getBattery() + 5) <= $status->getBattery()
            || ($lastStatus->getBattery() - 5) >= $status->getBattery()
            || $lastStatus->getLocation() != $status->getLocation()
            || $lastStatus->getTimestamp() < $this->getStatusAgeLimit()->getTimestamp())
            && $status->getBattery() > 0
        ) {
            $bikeStatus = new BikeStatus();
            $bikeStatus->setBikeCode($bike->getCode());
            $bikeStatus->setBattery($status->getBattery());
            $bikeStatus->setTimestamp($now->getTimestamp());
            $bikeStatus->setLocation($status->getLocation());
            $bikeStatus->setCity($status->getCity());
            $this->em->persist($bikeStatus);
        }
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
                $this->readEvents($bike,$status);
            }

            $this->readStatus($bike,$status);

            $bike->setLastSeenTimestamp($now->getTimestamp());
            $bike->setLastSeenCity($status->getCity());
            $bike->setBattery($status->getBattery());
            $bike->setLocation($status->getLocation());
            $this->em->persist($bike);
        }

        $this->em->flush();

        echo count($bikesStatuses)."\n";
    }
}