<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * @return Document[]
     */
    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type], ['uploadedAt' => 'DESC']);
    }

    public function findLatestPlanning(): ?Document
    {
        return $this->findOneBy(['type' => Document::TYPE_PLANNING], ['uploadedAt' => 'DESC']);
    }
}
