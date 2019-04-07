<?php

namespace App\Repository;

use App\Entity\Bike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Exception;
use DateTime;
use DateTimeZone;

class BikeRepository extends ServiceEntityRepository
{
    const BATTERY_LOW_LEVEL = 30;
    const BATTERY_CUTOFF_LEVEL = 20;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Bike::class);
    }

    /**
     * Get current DateTime
     * @param $time
     * @return int
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
     * @param string $city
     * @return Bike[]
     */
    public function getKnownBikes($city = null)
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.battery', 'DESC');

        if (!empty($city)) {
            $qb->andWhere('b.lastSeenCity = :city')
                ->setParameter('city', $city);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $city
     * @return Bike[]
     */
    public function getKnownBikesCount($city = null)
    {
        $qb = $this->createQueryBuilder('b')
            ->select("COUNT(b.id)")
            ->orderBy('b.battery', 'DESC');

        if (!empty($city)) {
            $qb->andWhere('b.lastSeenCity = :city')
                ->setParameter('city', $city);
        }

        $result = $qb->getQuery()->getResult();

        if(!empty($result[0]) && !empty($result[0][1])) {
            return $result[0][1];
        }

        return 0;
    }

    /**
     * @param $code
     * @return Bike|object
     */
    public function findBike($code)
    {
        return $this->findOneBy(["code" => $code]);
    }

    /**
     * @param $from
     * @param string $to
     * @param string $city
     * @return Bike[] Returns an array of Bike objects
     */
    private function findByActivity($from, $to = "now", $city = null)
    {
        $fromDatetime = $this->getTime($from);
        $toDatetime = $this->getTime($to);

        $qb = $this->createQueryBuilder('b')
            ->where('b.lastSeenTimestamp > :from')
            ->andWhere('b.lastSeenTimestamp < :to')
            ->setParameter('from', $fromDatetime)
            ->setParameter('to', $toDatetime)
            ->orderBy('b.battery', 'DESC')
            ;

        if (!empty($city)) {
            $qb->andWhere('b.lastSeenCity = :city')
                ->setParameter('city', $city);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $timespan
     * @param string $city
     * @return int
     */
    public function getActiveCount($timespan = 2, $city = null)
    {
        $fromDatetime = $this->getTime("-".$timespan." hours");

        $qb = $this->createQueryBuilder('b')
            ->where('b.lastSeenTimestamp > :from')
            ->andWhere('b.battery > :cutoff')
            ->setParameter('from', $fromDatetime)
            ->setParameter('cutoff', self::BATTERY_CUTOFF_LEVEL)
            ;

        if (!empty($city)) {
            $qb->andWhere('b.lastSeenCity = :city')
                ->setParameter('city', $city);
        }

        return count($qb->getQuery()->getResult());
    }

    /**
     * @param int $timespan
     * @param string $city
     * @return array
     */
    public function getLastSeenActive($timespan = 12, $city = null)
    {
        $summary = [];
        $summary["Mniej niż 1h"] = count($this->findByActivity("-1hour", "now", $city));

        for($i = 2; $i <= $timespan; $i++) {
            $summary[($i-1)."h - ".$i."h"] = count($this->findByActivity("-".$i."hours", "-".($i-1)."hours", $city));
        }

        $summary["Więcej niż ".($i-1)."h"] = count($this->findByActivity("-1week", "-".$i."hours", $city));

        return $summary;
    }

    /**
     * @param int $timespan
     * @param string $city
     * @return array
     */
    public function getBatteryStatus($timespan, $city)
    {
        $bikes = $this->findByActivity("-".$timespan." hours", "now", $city);
        $summary = [];

        $summary["10% - 0%"] = 0;
        for ($i = 1; $i < 10; $i++) {
            $summary[($i*10+10)."% - ".($i*10+1)."%"] = 0;
        }

        /** @var Bike $bike */
        foreach ($bikes as $bike) {
            if ($bike->getBattery() <= 10) {
                $summary["10% - 0%"]++;
                continue;
            }
            if ($bike->getBattery() <= 20) {
                $summary["20% - 11%"]++;
                continue;
            }
            if ($bike->getBattery() <= 30) {
                $summary["30% - 21%"]++;
                continue;
            }
            if ($bike->getBattery() <= 40) {
                $summary["40% - 31%"]++;
                continue;
            }
            if ($bike->getBattery() <= 50) {
                $summary["50% - 41%"]++;
                continue;
            }
            if ($bike->getBattery() <= 60) {
                $summary["60% - 51%"]++;
                continue;
            }
            if ($bike->getBattery() <= 70) {
                $summary["70% - 61%"]++;
                continue;
            }
            if ($bike->getBattery() <= 80) {
                $summary["80% - 71%"]++;
                continue;
            }
            if ($bike->getBattery() <= 90) {
                $summary["90% - 81%"]++;
                continue;
            }
            if ($bike->getBattery() <= 100) {
                $summary["100% - 91%"]++;
                continue;
            }
        }

        return array_reverse($summary,true);
    }

    public function getCities()
    {
        $qb = $this->createQueryBuilder('b')
            ->select('b.lastSeenCity')
            ->distinct('b.lastSeenCity');

        $result = $qb->getQuery()->getArrayResult();
        $cities = [];

        foreach ($result as $line) {
            $cities[] = $line["lastSeenCity"];
        }

        return $cities;
    }
}
