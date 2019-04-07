<?php

namespace App\Repository;

use App\Entity\BikeEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

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
    public function getBikeEvents($code, $timespan)
    {
        $from = $this->getTime("-".$timespan." hours");

        $qb = $this->createQueryBuilder('be')
            ->where('be.timestamp >= :from')
            ->setParameter('from', $from)
            ->setMaxResults(10000)
            ->andWhere('be.bikeCode = :code')
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
            ->setMaxResults(10000);

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
            ->where('be.timestamp >= :from')
            ->setParameter('from', $from)
            ->andWhere('be.type = :type')
            ->setParameter('type', $type)
            ->setMaxResults(10000);

        if (!empty($city)) {
            $qb->andWhere('be.city = :city')
                ->setParameter('city', $city);
        }

        return count($qb->getQuery()->getResult());
    }
}
