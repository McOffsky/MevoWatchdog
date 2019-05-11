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
            $summary[] = [
                "time" => date('H:i / d-m-Y',$status->getTimestamp()),
                "battery" => $status->getBattery(),
                "locationChange" => $status->getLocationChange()
            ];
        }

        return $summary;
    }

    /**
     * @param string $code
     * @param int $timespan
     * @return array
     */
    public function getBikePointsHistory($code, $timespan)
    {
        $statusQB = $this->createQueryBuilder('bs')
            ->andWhere('bs.locationChange = :true')
            ->andWhere('bs.timestamp > :from')
            ->andWhere('bs.timestamp < :to')
            ->andWhere('bs.bikeCode = :code')
            ->setParameter('from', $this->getTime("-".$timespan."hours"))
            ->setParameter('to', $this->getTime("now"))
            ->setParameter('code', $code)
            ->setParameter('true', true)
            ->addOrderBy("bs.timestamp", "DESC")
        ;

        $points = [];

        /** @var BikeStatus $status */
        foreach($statusQB->getQuery()->getResult() as $status) {
            $key = $status->getLocation();

            if(empty($points[$key])) {
                $points[$key] = [
                    "visit" => [],
                    "loc" => $status->getLoc(),
                    "location" => $status->getLocation(),
                ];
            }

            $points[$key]["visit"][] = date('H:i / d-m-Y', $status->getTimestamp()) . " (" . $status->getBattery(). "%)";
        }

        return $points;
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
     * @param string $location
     * @return integer
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getActiveCount($from="-1 hour", $to = "now", $city = null, $batteryCutoff = 0, $location = null)
    {
        $qb = $this->createQueryBuilder('bs')
            ->select("COUNT(DISTINCT bs.bikeCode)")
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

        if (!empty($location)) {
            $qb->andWhere('bs.location = :location')
                ->setParameter('location', $location);
        }

        return $qb
            ->getQuery()
            ->getSingleScalarResult();
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

    /**
     * @param int $days
     * @param string $city
     * @return array
     * @throws Exception
     */
    public function getLocationChangeDailySummary($days = 7, $city = null)
    {
        $summary = [];

        for ($i = 0; $i < $days; $i++) {
            $weekday = new DateTime("-".$i."days");
            $from = $weekday->format("00:00:00 d-m-Y");
            $to = $weekday->format("23:59:59 d-m-Y");

            $summary[$weekday->format("d-m-Y")] = $this->getLocationChangeCount($from, $to, null, $city);
        }

        return array_reverse($summary, true);
    }

    /**
     * @param int $days
     * @param string $city
     * @return array
     * @throws Exception
     */
    public function getLocationChangeSummary($timespan, $city = null)
    {
        $summary = [];

        for ($i = 0; $i < $timespan; $i++) {
            $time = new DateTime("-".$i."hours");
            $from = $time->format("H:00:00 d-m-Y");
            $to = $time->format("H:59:59 d-m-Y");

            $summary[$time->format("H:00-:59 / d-m")] = $this->getLocationChangeCount($from, $to, null, $city);
        }

        return array_reverse($summary, true);
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
            ->select('COUNT(bs.id)')
            ->andWhere('bs.locationChange = :true')
            ->andWhere('bs.timestamp > :from')
            ->andWhere('bs.timestamp < :to')
            ->setParameter('from', $this->getTime($form))
            ->setParameter('to', $this->getTime($to))
            ->setParameter('true', true)
        ;

        if (!empty($city)) {
            $statusQB->andWhere('bs.city = :city')
                ->setParameter('city', $city);
        }

        if (!empty($code)) {
            $statusQB->andWhere('bs.bikeCode = :code')
                ->setParameter('code', $code);
        }

        return $statusQB->getQuery()->getSingleScalarResult();
    }


    /**
     * @param string $form
     * @param string $to
     * @param string $city
     * @param integer $limit
     * @return array
     */
    public function getBikeCountByLocation($form, $to, $city = null, $limit = null)
    {
        $statusQB = $this->createQueryBuilder('bs')
            ->select('bs.location, COUNT(DISTINCT(bs.bikeCode)) AS cnt')
            ->groupBy("bs.location")
            ->andWhere('bs.timestamp > :from')
            ->andWhere('bs.timestamp < :to')
            ->setParameter('from', $this->getTime($form))
            ->setParameter('to', $this->getTime($to))
            ->orderBy('cnt', "DESC")
        ;

        if (!empty($city)) {
            $statusQB->andWhere('bs.city = :city')
                ->setParameter('city', $city);
        }

        if (!empty($limit)) {
            $statusQB->setMaxResults($limit);
        }

        $result = $statusQB->getQuery()->getArrayResult();

        $return = [];

        foreach ($result as $val) {
            $return[$val['location']] = intval($val['cnt']);
        }

        return $return;
    }

    /**
     * @param int $timespan
     * @param string $city
     * @return array
     */
    public function getActiveSummary($timespan = 12, $city = null)
    {
        $summary = [];

        $summary["< 1h"] = $this->getActiveCount("-1hour", "now", $city);

        for($i = 2; $i <= $timespan; $i++) {
            $summary[($i-1)."h - ".$i."h"] = $this->getActiveCount("-".$i."hours", "-".($i-1)."hours", $city);
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

        for ($i = 0; $i < $timespan; $i++) {
            $time = new DateTime("-" . $i . "hours");
            $from = $time->format("H:00:00 d-m-Y");
            $to = $time->format("H:59:59 d-m-Y");

            $summary[$time->format("H:00-:59 / d-m")] = $this->getActiveCount($from, $to, $city, self::BATTERY_CUTOFF_LEVEL);
        }

        return array_reverse($summary, true);
    }

    /**
     * @param string $location
     * @param int $timespan
     * @return array
     */
    public function getLocationSummary($location, $timespan = 12)
    {
        $summary = [];

        for ($i = 0; $i < $timespan; $i++) {
            $time = new DateTime("-" . $i . "hours");
            $from = $time->format("H:00:00 d-m-Y");
            $to = $time->format("H:59:59 d-m-Y");

            $summary[$time->format("H:00-:59 / d-m")] = $this->getActiveCount($from, $to, null, self::BATTERY_CUTOFF_LEVEL, $location);
        }


        return array_reverse($summary, true);
    }

    /**
     * @param string $location
     * @return integer
     */
    public function getBikesByLocation($timespan = 2, $location)
    {
        $qb = $this->createQueryBuilder('bs')
            ->groupBy('bs.bikeCode')
            ->andWhere('bs.location = :location')
            ->andWhere('bs.battery > :cutoff')
            ->andWhere('bs.timestamp > :from')
            ->setParameter('from', $this->getTime("-".$timespan."hours"))
            ->setParameter('location', $location)
            ->setParameter('cutoff', self::BATTERY_CUTOFF_LEVEL)
            ->orderBy('bs.timestamp', "DESC")
        ;

        /** @var BikeStatus $status */
        return array_map(function($status) {
                return $status->getBikeCode();
            },
            $qb->getQuery()->getResult()
        );
    }

    /**
     * @param string $location
     * @param int $timespan
     * @return array
     */
    public function getLocationConnections($location, $timespan = 12)
    {
        $statusQB = $this->createQueryBuilder('bs')
            ->andWhere('bs.locationChange = :true')
            ->andWhere('bs.location = :location')
            ->andWhere('bs.timestamp > :from')
            ->andWhere('bs.timestamp < :to')
            ->setParameter('from', $this->getTime("-".$timespan."hours"))
            ->setParameter('to', $this->getTime("now"))
            ->setParameter('true', true)
            ->setParameter('location', $location)
            ->orderBy('bs.timestamp', "DESC")
        ;

        $bikeArrivals = $statusQB->getQuery()->getResult();

        return $this->getBikeConnections($bikeArrivals);
    }

    /**
     * @param BikeStatus[] $bikeStatus
     * @return array
     */
    private function getBikeConnections(array $bikeStatusList)
    {
        $points = [];

        foreach ($bikeStatusList as $bikeStatus) {
            $statusID = $bikeStatus->getId();
            $bikeCode = $bikeStatus->getBikeCode();
            $location = $bikeStatus->getBikeCode();
            $timestamp = $bikeStatus->getTimestamp() - 43200; // -12h

            $rsm = $this->createResultSetMappingBuilder('bs');
            $selectClause = $rsm->generateSelectClause(['bs']);

            $sql = "
                (SELECT $selectClause FROM BikeStatus as bs WHERE bs.id > $statusID AND bs.locationChange = true AND bs.bikeCode = $bikeCode ORDER BY bs.id LIMIT 1)
                UNION
                (SELECT $selectClause FROM BikeStatus as bs WHERE bs.id < $statusID AND bs.location != '$location' AND bs.bikeCode = $bikeCode AND bs.timestamp > $timestamp ORDER BY bs.id DESC LIMIT 1)
            ";

            $query = $this->getEntityManager()->createNativeQuery(
                $sql,
                $rsm
            );

            /** @var BikeStatus[] $result */
            $result = $query->getResult();


            // NEXT LOCATION
            if (!empty($result[0]) && $bikeStatus->getLocation() != $result[0]->getLocation()) {
                $nextLocation = $result[0]->getLocation();

                if (empty($points[$nextLocation])) {
                    $points[$nextLocation] = [
                        "loc" => $result[0]->getLoc(),
                        "location" => $result[0]->getLocation(),
                        "bikes" => [],
                    ];
                }

                $points[$nextLocation]["bikes"][] = [
                    "type" => "dep",
                    "time" => date('H:i / d-m', $result[0]->getTimestamp()),
                    "bike" => $result[0]->getBikeCode(),
                ];
            }

            // PREVIOUS LOCATION
            if (!empty($result[1]) && $bikeStatus->getLocation() != $result[1]->getLocation()) {
                $prevLocation = $result[1]->getLocation();

                if (empty($points[$prevLocation])) {
                    $points[$prevLocation] = [
                        "loc" => $result[1]->getLoc(),
                        "location" => $result[1]->getLocation(),
                        "bikes" => [],
                    ];
                }

                $points[$prevLocation]["bikes"][] = [
                    "type" => "arr",
                    "time" => date('H:i / d-m', $result[1]->getTimestamp()),
                    "bike" => $result[1]->getBikeCode(),
                ];
            }

        }

        return array_values($points);
    }
}
