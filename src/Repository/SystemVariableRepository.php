<?php

namespace App\Repository;

use App\Entity\SystemVariable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SystemVariable|null find($id, $lockMode = null, $lockVersion = null)
 * @method SystemVariable|null findOneBy(array $criteria, array $orderBy = null)
 * @method SystemVariable[]    findAll()
 * @method SystemVariable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SystemVariableRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SystemVariable::class);
    }
}
