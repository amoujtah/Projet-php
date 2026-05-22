<?php
// src/Repository/UserRepository.php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // Obtenir les statistiques des utilisateurs par type
    public function getUserStats(): array
    {
        $qb = $this->createQueryBuilder('u');
        
        $stats = $qb
            ->select([
                "SUM(CASE WHEN u.type = 'admin' THEN 1 ELSE 0 END) as admin_count",
                "SUM(CASE WHEN u.type = 'serveur' THEN 1 ELSE 0 END) as serveur_count",
                "SUM(CASE WHEN u.type = 'client' THEN 1 ELSE 0 END) as client_count",
                "COUNT(u.id) as total_count"
            ])
            ->getQuery()
            ->getSingleResult();

        return [
            'admin_count' => (int) $stats['admin_count'],
            'serveur_count' => (int) $stats['serveur_count'],
            'client_count' => (int) $stats['client_count'],
            'total_count' => (int) $stats['total_count'],
        ];
    }

    // Compter les utilisateurs par type
    public function countByType(string $type): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Trouver tous les utilisateurs d'un type spécifique
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.type = :type')
            ->setParameter('type', $type)
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}