<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    /**
     * Trouver les menus disponibles
     */
    public function findDisponibles(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.disponible = true')
            ->orderBy('m.categorie', 'ASC')
            ->addOrderBy('m.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les menus par catégorie
     */
    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.categorie = :categorie')
            ->andWhere('m.disponible = true')
            ->setParameter('categorie', $categorie)
            ->orderBy('m.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}