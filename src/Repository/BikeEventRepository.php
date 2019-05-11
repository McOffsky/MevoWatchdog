<?php

namespace App\Repository;

use App\Entity\BikeEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * @method BikeEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method BikeEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method BikeEvent[]    findAll()
 * @method BikeEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BikeEventRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BikeEvent::class);
    }

    /**
     * Get current DateTime
     * @param $time
     * @return int|null
     */
    private function getTime($time)
    {
        try {
            $now = new DateTime($time);
            $now->setTimezone(new DateTimeZone('UTC'));
            return $now->getTimestamp();
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * @param string $code
     * @param int $timespan
     * @return array
     */
    public function getBikeEvents($code, $timespan)
    {
        return $this->getEvents($timespan, null, null, $code);
    }

    /**
     * @param int $timespan
     * @param string $city
     * @param string $type
     * @param string $bikeCode
     * @param string $order
     * @return array
     */
    public function getEvents($timespan, $city = null, $type = null, $bikeCode = null)
    {
        $from = $this->getTime("-" . $timespan . " hours");

        $qb = $this->createQueryBuilder('be')
            ->where('be.timestamp >= :from')
            ->setParameter('from', $from)
            ->orderBy('be.timestamp', "DESC");

        if (!empty($city)) {
            $qb->andWhere('be.city = :city')
                ->setParameter('city', $city);
        }

        if (!empty($type)) {
            $qb->andWhere('be.type = :type')
                ->setParameter('type', $type);
        }

        if (!empty($bikeCode)) {
            $qb->andWhere('be.bikeCode = :code')
                ->setParameter('code', $bikeCode);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $location
     * @param int $timespan
     * @return array
     */
    public function getLocationEvents($location, $timespan)
    {
        $from = $this->getTime("-" . $timespan . " hours");

        $qb = $this->createQueryBuilder('be')
            ->where('be.timestamp >= :from')
            ->andWhere('be.location = :location')
            ->setParameter('location', $location)
            ->setParameter('from', $from)
            ->orderBy('be.timestamp', "DESC");

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $timespan
     * @param string $city
     * @param string $type
     * @param string $bikeCode
     * @return array
     */
    public function getEventPoints($timespan, $city = null, $type = null, $bikeCode = null)
    {
        $events = $this->getEvents($timespan, $city, $type, $bikeCode);

        $points = [];

        /** @var BikeEvent $event */
        foreach ($events as $event) {
            $location = $event->getLocation();

            if (!empty($location)) {
                if (!array_key_exists($location,$points)) {
                    $points[$location] = [
                        'loc' => $event->getLoc(),
                        'location' => $event->getLocation(),
                        'events' => [],
                    ];
                }

                $points[$event->getLocation()]['events'][] = [
                    'type' => $event->getType(),
                    'bike' => $event->getBikeCode(),
                    'time' => date("H:i / d-m-Y", $event->getTimestamp()),
                ];
            }
        }

        return $points;
    }

    /**
     * @param string $type
     * @param integer $timespan
     * @param string $city
     * @return integer
     */
    public function countByType($type, $timespan, $city = null)
    {
        return $this->countByTypeFromTo($type, "-" . $timespan . " hours", "now", $city);
    }

    /**
     * @param string $type
     * @param integer $days
     * @param string $city
     * @return array
     */
    public function summaryByType($type, $days, $city = null)
    {
        $summary = [];

        for ($i = 0; $i < $days; $i++) {
            $weekday = new DateTime("-".$i."days");
            $from = $weekday->format("00:00:00 d-m-Y");
            $to = $weekday->format("23:59:59 d-m-Y");

            $summary[$weekday->format("d-m-Y")] = $this->countByTypeFromTo($type, $from, $to, $city);
        }

        return array_reverse($summary, true);
    }

    /**
     * @param string $type
     * @param string $from
     * @param string $to
     * @param string $city
     * @return integer
     */
    private function countByTypeFromTo($type, $from, $to, $city = null)
    {
        $from = $this->getTime($from);
        $to = $this->getTime($to);

        $qb = $this->createQueryBuilder('be')
            ->select("COUNT(DISTINCT be.id)")
            ->andWhere('be.timestamp >= :from')
            ->andWhere('be.timestamp <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->andWhere('be.type = :type')
            ->setParameter('type', $type)
        ;

        if (!empty($city)) {
            $qb->andWhere('be.city = :city')
                ->setParameter('city', $city);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
