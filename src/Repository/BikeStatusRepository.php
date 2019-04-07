<?php

namespace App\Repository;

use App\Entity\Bike;
use App\Entity\BikeStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class BikeStatusRepository extends ServiceEntityRepository
{
    const BATTERY_CUTOFF_LEVEL = 20;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BikeStatus::class);
    }

    /**
     * Get current DateTime
     */
    private function getTime($time)
    {
        $now = new \DateTime($time);
        $now->setTimezone(new \DateTimeZone('UTC'));

        return $now->getTimestamp();
    }

    /**
     * @param string $code
     * @param int $timespan
     * @return array
     */
    private function getBikeStatusList($code, $timespan)
    {
        $from = $this->getTime("-".$timespan." hours");

        $qb = $this->createQueryBuilder('bs')
            ->where('bs.timestamp >= :from')
            ->setParameter('from', $from)
            ->setMaxResults(10000)
            ->andWhere('bs.bikeCode = :code')
            ->setParameter('code', $code)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $code
     * @param int $timespan
     * @return array
     */
    public function getBikeBatteryHistory($code, $timespan)
    {
        $summary = [];

        /** @var BikeStatus $status */
        foreach($this->getBikeStatusList($code, $timespan) as $status) {
            $time = date('H:i / d-m-Y',$status->getTimestamp());
            $summary[$time] = $status->getBattery();
        }

        return $summary;
    }

    /**
     * @param string $code
     * @param int $timespan
     * @return array
     */
    public function getBikeLocationHistory($code, $timespan)
    {
        $summary = [];

        /** @var BikeStatus $status */
        foreach($this->getBikeStatusList($code, $timespan) as $status) {

            $summary[$status->getLocation()] = $status->getLoc();
        }

        return array_values($summary);
    }

    /**
     * @param $code
     * @return null|BikeStatus
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastStatusForBike($code)
    {
        return $this->createQueryBuilder('bs')
                    ->select('bs')
                    ->orderBy('bs.id', 'DESC')
                    ->setMaxResults(1)
                    ->where('bs.bikeCode = :code')
                    ->setParameter('code', $code)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $city
     * @param int $batteryCutoff
     * @return mixed
     */
    private function getActive($from="-1 hour", $to = "now", $city = null, $batteryCutoff = 0)
    {
        $qb = $this->createQueryBuilder('bs')
            ->select('b')
            ->distinct('b.code')
            ->leftJoin('App:Bike', 'b', 'WITH', 'bs.bikeCode = b.code')
            ->where("bs.battery >= :battery")
            ->setParameter("battery", $batteryCutoff)

            ->andWhere('bs.timestamp > :from')
            ->andWhere('bs.timestamp < :to')
            ->setParameter('from', $this->getTime($from))
            ->setParameter('to', $this->getTime($to))
        ;

        if (!empty($city)) {
            $qb->andWhere('bs.city = :city')
                ->setParameter('city', $city);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $timespan
     * @param string $city
     * @return array
     */
    public function getActiveSummary($timespan = 12, $city = null)
    {
        $summary = [];

        $summary["Do 1h"] = count($this->getActive("-1hour", "now", $city));

        for($i = 2; $i <= $timespan; $i++) {
            $summary[($i-1)."h - ".$i."h"] = count($this->getActive("-".$i."hours", "-".($i-1)."hours", $city));
        }

        return $summary;
    }

    /**
     * @param int $timespan
     * @param string $city
     * @return array
     */
    public function getAvailableSummary($timespan = 12, $city = null)
    {
        $summary = [];

        $summary["Do 1h"] = count($this->getActive("-1hour", "now", $city, self::BATTERY_CUTOFF_LEVEL));

        for($i = 2; $i <= $timespan; $i++) {
            $summary[($i-1)."h - ".$i."h"] = count($this->getActive("-".$i."hours", "-".($i-1)."hours", $city, self::BATTERY_CUTOFF_LEVEL));
        }


        return $summary;
    }
}
