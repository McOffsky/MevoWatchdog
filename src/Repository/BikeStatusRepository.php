<?php

namespace App\Repository;

use App\Entity\BikeStatus;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;
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
    private function getBikeStatusList($code, $timespan)
    {
        $from = $this->getTime("-".$timespan." hours");

        $qb = $this->createQueryBuilder('bs')
            ->where('bs.timestamp >= :from')
            ->setParameter('from', $from)
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
        $statusQB = $this->createQueryBuilder('bs')
            ->groupBy("bs.location")
            ->andWhere('bs.timestamp > :from')
            ->andWhere('bs.timestamp < :to')
            ->setParameter('from', $this->getTime("-".$timespan."hours"))
            ->setParameter('to', $this->getTime("now"))
            ->andWhere('bs.bikeCode = :code')
            ->setParameter('code', $code)
            ->addOrderBy("bs.timestamp", "DESC")
        ;

        $locations = [];

        /** @var BikeStatus $status */
        foreach($statusQB->getQuery()->getResult() as $status) {
            $locations[] = $status->getLoc();
        }

        return $locations;
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
     * @param null $code
     * @param string $city
     * @return array
     */
    public function getLocationChangeTimespanCount($timespan, $code = null, $city = null)
    {
        return $this->getLocationChangeCount("-".$timespan."hours", "now", $code, $city);
    }

    public function getLocationChangeSummary($days = 7, $city = null)
    {
        $summary = [];

        for ($i = 0; $i < $days; $i++) {
            $weekday = new DateTime("-".$i."days");
            $from = $weekday->format("00:00:00 d-m-Y");
            $to = $weekday->format("23:59:59 d-m-Y");

            $summary[$weekday->format("d-m-Y")] = $this->getLocationChangeCount($from, $to, null, $city);
        }

        return $summary;
    }

    /**
     * @param string $form
     * @param string $to
     * @param string $code
     * @param string $city
     * @return array
     */
    public function getLocationChangeCount($form, $to, $code = null, $city = null)
    {
        $statusQB = $this->createQueryBuilder('bs')
            ->groupBy("bs.bikeCode,bs.location")
            ->andWhere('bs.timestamp > :from')
            ->andWhere('bs.timestamp < :to')
            ->setParameter('from', $this->getTime($form))
            ->setParameter('to', $this->getTime($to))
        ;

        if (!empty($city)) {
            $statusQB->andWhere('bs.city = :city')
                ->setParameter('city', $city);
        }

        if (!empty($code)) {
            $statusQB->andWhere('bs.bikeCode = :code')
                ->setParameter('code', $code);
        }

        $locations = $statusQB->getQuery()->getResult();

        $bikesQB = $this->createQueryBuilder('bs')
            ->groupBy("bs.bikeCode")
            ->andWhere('bs.timestamp > :from')
            ->andWhere('bs.timestamp < :to')
            ->setParameter('from', $this->getTime($form))
            ->setParameter('to', $this->getTime($to))
        ;

        if (!empty($city)) {
            $bikesQB->andWhere('bs.city = :city')
                ->setParameter('city', $city);
        }

        if (!empty($code)) {
            $bikesQB->andWhere('bs.bikeCode = :code')
                ->setParameter('code', $code);
        }

        $bikes = $bikesQB->getQuery()->getResult();

        return count($locations) - count($bikes);
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
