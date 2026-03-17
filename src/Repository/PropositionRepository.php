<?php

namespace App\Repository;

use App\Entity\Proposition;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Proposition>
 */
class PropositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proposition::class);
    }

    /**
     * @return Proposition[]
     */
    public function findByAuteur(User $user): array
    {
        return $this->findBy(['auteur' => $user], ['dateCreation' => 'DESC']);
    }

    /**
     * @return Proposition[]
     */
    public function findAllOrderedByDate(): array
    {
        return $this->findBy([], ['dateCreation' => 'DESC']);
    }
}
