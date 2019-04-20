<?php

namespace App\Repository;

use App\Entity\BikeStatus;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Station|null find($id, $lockMode = null, $lockVersion = null)
 * @method Station|null findOneBy(array $criteria, array $orderBy = null)
 * @method Station[]    findAll()
 * @method Station[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Station::class);
    }

    /**
     * @param integer $timespan
     * @param string $city
     * @return array
     */
    public function getStations($timespan, $city = null)
    {
        $statusRepo = $this->getEntityManager()->getRepository(BikeStatus::class);

        $qb = $this->createQueryBuilder('s');

        if (!empty($city)) {
            $qb->andWhere('s.city = :city')
                ->setParameter('city', $city);
        }

        $stations = $qb->getQuery()->getResult();
        $bikeCount = $statusRepo->getBikeCountByLocation("-".$timespan."hours", "now", $city);

        /** @var Station $station */
        foreach ($stations as $station) {
            $station->setBikeCount(0);
            if (!empty($bikeCount[$station->getLocation()])) {
                $station->setBikeCount($bikeCount[$station->getLocation()]);
            }
        }

        return $stations;
    }

    public function getStationPoints($timespan, $city = null)
    {
        return array_map(function($station) {
                return [
                    'name' => $station->getName(),
                    'code' => $station->getCode(),
                    'racks' => $station->getRacks(),
                    'bikes' => $station->getBikes(),
                    'booked' => $station->getBookedBikes(),
                    'bikeCount' => $station->getBikeCount(),
                    'location' => $station->getLocation(),
                    'loc' => $station->getLoc(),
                ];
            },
            $this->getStations($timespan,$city)
        );
    }

    /**
     * @param int $timespan
     * @param string $city
     * @param int $limit
     * @return Station[]
     */
    public function getMostActiveStations($timespan, $city = null, $limit = 10)
    {
        $statusRepo = $this->getEntityManager()->getRepository(BikeStatus::class);

        $bikeCount = $statusRepo->getBikeCountByLocation("-".$timespan."hours", "now", $city, $limit);

        $stations = [];

        /** @var Station $station */
        foreach ($bikeCount as $location => $count) {
            $station = $this->findOneBy(["location" => $location]);

            if (!empty($station)) {
                $station->setBikeCount($count);
                $stations[] = $station;
            }
        }

        return $stations;
    }
}
