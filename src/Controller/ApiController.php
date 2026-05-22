<?php
namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\RestaurantTable;
use App\Repository\ReservationRepository;
use App\Repository\RestaurantTableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/tables-disponibles', name: 'api_tables_disponibles', methods: ['GET'])]
    public function tablesDisponibles(
        Request $request,
        RestaurantTableRepository $tableRepo,
        ReservationRepository $reservationRepo
    ): JsonResponse {
        $date = $request->query->get('date');
        $heure = $request->query->get('heure');
        $nbPersonnes = $request->query->get('nbPersonnes', 2);
        
        if (!$date || !$heure) {
            return new JsonResponse(['error' => 'Date et heure requises'], 400);
        }
        
        try {
            $dateObj = new \DateTime($date);
            $heureObj = new \DateTime($heure);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date/heure invalide'], 400);
        }
        
        // Toutes les tables
        $tables = $tableRepo->findBy(['status' => true]);
        
        // Réservations existantes pour cette date/heure
        $reservations = $reservationRepo->findByDateHeure($dateObj, $heureObj);
        
        // Tables déjà réservées
        $tablesReservees = [];
        foreach ($reservations as $reservation) {
            $tablesReservees[] = $reservation->getRestaurantTable()->getId();
        }
        
        // Tables disponibles filtrées par capacité
        $tablesDisponibles = [];
        foreach ($tables as $table) {
            if (!in_array($table->getId(), $tablesReservees) 
                && $table->getCapacite() >= $nbPersonnes) {
                $tablesDisponibles[] = [
                    'id' => $table->getId(),
                    'numero' => $table->getNumero(),
                    'capacite' => $table->getCapacite(),
                ];
            }
        }
        
        return new JsonResponse([
            'date' => $date,
            'heure' => $heure,
            'nbPersonnes' => $nbPersonnes,
            'tablesDisponibles' => $tablesDisponibles,
            'count' => count($tablesDisponibles),
        ]);
    }
    
    #[Route('/creneaux-disponibles/{tableId}', name: 'api_creneaux_disponibles', methods: ['GET'])]
    public function creneauxDisponibles(
        int $tableId,
        Request $request,
        RestaurantTableRepository $tableRepo,
        ReservationRepository $reservationRepo
    ): JsonResponse {
        $date = $request->query->get('date');
        
        if (!$date) {
            return new JsonResponse(['error' => 'Date requise'], 400);
        }
        
        $table = $tableRepo->find($tableId);
        if (!$table) {
            return new JsonResponse(['error' => 'Table non trouvée'], 404);
        }
        
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide'], 400);
        }
        
        // Créneaux horaires du restaurant (ex: 12h-14h, 19h-22h)
        $creneauxRestaurant = [
            ['debut' => '12:00', 'fin' => '14:00'],
            ['debut' => '19:00', 'fin' => '22:00'],
        ];
        
        // Réservations existantes pour cette table et cette date
        $reservations = $reservationRepo->findByTableAndDate($table, $dateObj);
        
        // Générer tous les créneaux de 30 minutes
        $creneauxDisponibles = [];
        foreach ($creneauxRestaurant as $plage) {
            $debut = new \DateTime($date . ' ' . $plage['debut']);
            $fin = new \DateTime($date . ' ' . $plage['fin']);
            
            $current = clone $debut;
            while ($current < $fin) {
                $heureStr = $current->format('H:i');
                
                // Vérifier si la table est réservée à cette heure
                $estReserve = false;
                foreach ($reservations as $reservation) {
                    $heureResa = $reservation->getHeure()->format('H:i');
                    if ($heureResa === $heureStr) {
                        $estReserve = true;
                        break;
                    }
                }
                
                if (!$estReserve) {
                    $creneauxDisponibles[] = $heureStr;
                }
                
                $current->modify('+30 minutes');
            }
        }
        
        return new JsonResponse([
            'table' => $table->getNumero(),
            'date' => $date,
            'creneauxDisponibles' => $creneauxDisponibles,
        ]);
    }
}
?>