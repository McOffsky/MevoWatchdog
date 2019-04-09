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
        $from = $this->getTime("-".$timespan." hours");

        $qb = $this->createQueryBuilder('be')
            ->where('be.timestamp >= :from')
            ->andWhere('be.bikeCode = :code')
            ->setParameter('from', $from)
            ->setParameter('code', $code)
            ->orderBy('be.id', 'DESC')
            ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $timespan
     * @param string $city
     * @return array
     */
    public function getEvents($timespan, $city = null)
    {
        $from = $this->getTime("-".$timespan." hours");

        $qb = $this->createQueryBuilder('be')
            ->where('be.timestamp >= :from')
            ->setParameter('from', $from)
            ->orderBy('be.timestamp', 'DESC')
            ;

        if (!empty($city)) {
            $qb->andWhere('be.city = :city')
                ->setParameter('city', $city);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $type
     * @param integer $timespan
     * @param string $city
     * @return integer
     */
    public function countByType($type, $timespan, $city = null)
    {
        $from = $this->getTime("-".$timespan." hours");

        $qb = $this->createQueryBuilder('be')
            ->select("COUNT(DISTINCT be.id)")
            ->where('be.timestamp >= :from')
            ->setParameter('from', $from)
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
