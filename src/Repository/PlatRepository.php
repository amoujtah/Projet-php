<?php

namespace App\Repository;

use App\Entity\Plat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plat>
 */
class PlatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plat::class);
    }

    /**
     * Trouver les plats par catégorie
     */
    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.categorie = :categorie')
            ->andWhere('p.disponible = true')
            ->setParameter('categorie', $categorie)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver toutes les catégories distinctes
     */
    public function findAllCategories(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.categorie')
            ->where('p.disponible = true')
            ->orderBy('p.categorie', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Trouver les plats disponibles
     */
    public function findDisponibles(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.disponible = true')
            ->orderBy('p.categorie', 'ASC')
            ->addOrderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * Recherche de plats par nom ou description
    //  */
    // public function search(string $query): array
    // {
    //     return $this->createQueryBuilder('p')
    //         ->where('p.nom LIKE :query')
    //         ->orWhere('p.description LIKE :query')
    //         ->andWhere('p.disponible = true')
    //         ->setParameter('query', '%' . $query . '%')
    //         ->orderBy('p.nom', 'ASC')
    //         ->getQuery()
    //         ->getResult();
    // }
}