<?php
namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }
    
    public function findDisponiblesParDate(\DateTime $date): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.date = :date')
            ->andWhere('r.status != :annulee')
            ->setParameter('date', $date)
            ->setParameter('annulee', 'annulée')
            ->orderBy('r.date', 'ASC') // CORRECTION: Utilisez 'date' au lieu de 'heure'
            ->getQuery()
            ->getResult();
    }
    
    public function countReservationsClient(int $clientId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.client = :clientId')
            ->andWhere('r.status = :honoree')
            ->setParameter('clientId', $clientId)
            ->setParameter('honoree', 'honorée')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    // Méthode pour compter les réservations d'aujourd'hui
    public function countTodayReservations(): int
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.date >= :today')
            ->andWhere('r.date < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    // Méthode pour trouver les réservations récentes
    public function findRecentReservations(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC')
            // SUPPRIMEZ: ->addOrderBy('r.heure', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    // Méthode pour trouver les prochaines réservations
    public function findUpcomingReservations(int $limit = 5): array
    {
        $today = new \DateTime('today');
        
        return $this->createQueryBuilder('r')
            ->where('r.date >= :today')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('today', $today)
            ->setParameter('statuses', ['confirmée', 'en_attente'])
            ->orderBy('r.date', 'ASC')
            // SUPPRIMEZ: ->addOrderBy('r.heure', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    // Méthode pour compter les réservations par statut
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    // Méthode pour trouver les réservations par période
    public function findByPeriod(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.date >= :startDate')
            ->andWhere('r.date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.date', 'ASC')
            // SUPPRIMEZ: ->addOrderBy('r.heure', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    // Méthode pour trouver les réservations d'un client spécifique
    public function findByClient(int $clientId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('r.date', 'DESC')
            // SUPPRIMEZ: ->addOrderBy('r.heure', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    // Méthode pour trouver les réservations par table
    public function findByTable(int $tableId): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.restaurantTable', 't') // CORRECTION: 'restaurantTable' au lieu de 'table'
            ->where('t.id = :tableId')
            ->setParameter('tableId', $tableId)
            ->orderBy('r.date', 'DESC')
            // SUPPRIMEZ: ->addOrderBy('r.heure', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    // Méthode pour les statistiques mensuelles
    public function getMonthlyStats(int $year, int $month): array
    {
        $startDate = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        
        return $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->where('r.date >= :startDate')
            ->andWhere('r.date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('r.status')
            ->getQuery()
            ->getResult();
    }
    
    // Méthode pour trouver les créneaux occupés pour une table à une date donnée
    public function findOccupiedSlotsForTable(int $tableId, \DateTime $date): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.date') // CORRECTION: Utilisez 'date' au lieu de 'heure'
            ->join('r.restaurantTable', 't') // CORRECTION: 'restaurantTable' au lieu de 'table'
            ->where('t.id = :tableId')
            ->andWhere('DATE(r.date) = :date') // Utilisez DATE() pour extraire seulement la partie date
            ->andWhere('r.status IN (:validStatuses)')
            ->setParameter('tableId', $tableId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('validStatuses', ['en_attente', 'confirmée'])
            ->orderBy('r.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    // NOUVELLE MÉTHODE UTILE : Extraire l'heure de la date
    public function findReservationsByDateAndTime(\DateTime $dateTime): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.date = :dateTime')
            ->setParameter('dateTime', $dateTime)
            ->getQuery()
            ->getResult();
    }
}