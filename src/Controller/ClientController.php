<?php
// src/Controller/ClientController.php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Plat;
use App\Entity\Menu;
use App\Form\ReservationType;
use App\Form\UserProfileType;
use App\Repository\ReservationRepository;
use App\Repository\PlatRepository;
use App\Repository\MenuRepository;
use App\Repository\RestaurantTableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
#[IsGranted('ROLE_CLIENT')]
class ClientController extends AbstractController
{
    // AJOUTEZ CETTE MÉTHODE AU DÉBUT DE LA CLASSE
    private function checkUserType(): ?Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // UTILISEZ getType() comme dans votre User.php
        $type = $user->getType();
        
        if ($type === 'serveur') {
            $this->addFlash('error', 'Accès refusé. Les serveurs doivent utiliser le dashboard serveur.');
            return $this->redirectToRoute('app_serveur_dashboard');
        }
        
        if ($type === 'admin') {
            $this->addFlash('error', 'Accès refusé. Les administrateurs doivent utiliser le dashboard admin.');
            return $this->redirectToRoute('app_admin_dashboard');
        }
        
        return null;
    }

    #[Route('/dashboard', name: 'app_client_dashboard')]
    public function dashboard(
        ReservationRepository $reservationRepository,
        PlatRepository $platRepository,
        MenuRepository $menuRepository
    ): Response {
        // VÉRIFICATION DU TYPE D'UTILISATEUR
        $typeCheck = $this->checkUserType();
        if ($typeCheck) {
            return $typeCheck;
        }
        
        $user = $this->getUser();
        
        // Récupérer les réservations du client
        $reservations = $reservationRepository->findBy(
            ['client' => $user], 
            ['date' => 'DESC']
        );
        
        // Statistiques
        $stats = $this->getReservationStats($reservations);
        
        // Plats populaires (6 derniers plats)
        $popularPlats = $platRepository->findBy([], ['createdAt' => 'DESC'], 6);
        
        // Menus
        $menus = $menuRepository->findAll();
        
        return $this->render('client/dashboard.html.twig', [
            'user' => $user,
            'reservations' => $reservations,
            'stats' => $stats,
            'popularPlats' => $popularPlats,
            'menus' => $menus,
        ]);
    }

    #[Route('/reservations', name: 'app_client_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        // VÉRIFICATION DU TYPE D'UTILISATEUR
        $typeCheck = $this->checkUserType();
        if ($typeCheck) {
            return $typeCheck;
        }
        
        $user = $this->getUser();
        $reservations = $reservationRepository->findBy(
            ['client' => $user], 
            ['date' => 'DESC']
        );
        
        return $this->render('client/reservations/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservations/nouvelle', name: 'app_client_reservation_new')]
    public function newReservation(
        Request $request,
        EntityManagerInterface $entityManager,
        RestaurantTableRepository $tableRepository
    ): Response {
        // VÉRIFICATION DU TYPE D'UTILISATEUR
        $typeCheck = $this->checkUserType();
        if ($typeCheck) {
            return $typeCheck;
        }
        
        $reservation = new Reservation();
        $user = $this->getUser();
        
        // Définir le client
        $reservation->setClient($user);
        
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Vérifier la disponibilité de la table
                    $table = $reservation->getRestaurantTable();
                    $date = $reservation->getDate();
                    
                    $existingReservations = $entityManager->getRepository(Reservation::class)
                        ->createQueryBuilder('r')
                        ->where('r.restaurantTable = :table')
                        ->andWhere('r.date = :date')
                        ->andWhere('r.status IN (:statuses)')
                        ->setParameter('table', $table)
                        ->setParameter('date', $date)
                        ->setParameter('statuses', ['en_attente', 'confirmée'])
                        ->getQuery()
                        ->getResult();
                    
                    if (count($existingReservations) > 0) {
                        $this->addFlash('error', 'Cette table n\'est pas disponible à cette date et heure. Veuillez choisir une autre table ou un autre créneau.');
                    } else {
                        // Définir le statut par défaut
                        $reservation->setStatus('en_attente');
                        
                        $entityManager->persist($reservation);
                        $entityManager->flush();
                        
                        $this->addFlash('success', 'Votre réservation a été créée avec succès ! Elle est en attente de confirmation.');
                        return $this->redirectToRoute('app_client_reservations');
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la création de la réservation: ' . $e->getMessage());
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }
        
        return $this->render('client/reservations/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
            'tables' => $tableRepository->findAll(),
        ]);
    }

    #[Route('/reservations/{id}/annuler', name: 'app_client_reservation_cancel', methods: ['POST'])]
    public function cancelReservation(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // VÉRIFICATION DU TYPE D'UTILISATEUR
        $typeCheck = $this->checkUserType();
        if ($typeCheck) {
            return $typeCheck;
        }
        
        // Vérifier que la réservation appartient bien au client
        if ($reservation->getClient() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à annuler cette réservation.');
            return $this->redirectToRoute('app_client_reservations');
        }
        
        if ($this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatus('annulée');
            $entityManager->flush();
            
            $this->addFlash('success', 'Réservation annulée avec succès.');
        }
        
        return $this->redirectToRoute('app_client_reservations');
    }

    #[Route('/carte', name: 'app_client_carte')]
    public function carte(PlatRepository $platRepository, MenuRepository $menuRepository): Response
    {
        // VÉRIFICATION DU TYPE D'UTILISATEUR
        $typeCheck = $this->checkUserType();
        if ($typeCheck) {
            return $typeCheck;
        }
        
        $plats = $platRepository->findAll();
        $menus = $menuRepository->findAll();
        
        return $this->render('client/carte/index.html.twig', [
            'plats' => $plats,
            'menus' => $menus,
        ]);
    }

    #[Route('/plats/{id}', name: 'app_client_plat_show')]
    public function showPlat(Plat $plat): Response
    {
        // VÉRIFICATION DU TYPE D'UTILISATEUR
        $typeCheck = $this->checkUserType();
        if ($typeCheck) {
            return $typeCheck;
        }
        
        return $this->render('client/carte/plat_show.html.twig', [
            'plat' => $plat,
        ]);
    }

    #[Route('/menus/{id}', name: 'app_client_menu_show')]
    public function showMenu(Menu $menu): Response
    {
        // VÉRIFICATION DU TYPE D'UTILISATEUR
        $typeCheck = $this->checkUserType();
        if ($typeCheck) {
            return $typeCheck;
        }
        
        return $this->render('client/carte/menu_show.html.twig', [
            'menu' => $menu,
        ]);
    }

    #[Route('/profile', name: 'app_client_profile')]
    public function profile(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // VÉRIFICATION DU TYPE D'UTILISATEUR
        $typeCheck = $this->checkUserType();
        if ($typeCheck) {
            return $typeCheck;
        }
        
        $user = $this->getUser();
        
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre profil a été mis à jour.');
            return $this->redirectToRoute('app_client_profile');
        }
        
        return $this->render('client/profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
    
    // Méthode pour obtenir les statistiques réelles des réservations
    private function getReservationStats(array $reservations): array
    {
        $today = new \DateTime('today');
        
        $stats = [
            'total' => count($reservations),
            'upcoming' => 0,
            'past' => 0,
            'confirmed' => 0,
            'pending' => 0,
            'cancelled' => 0,
        ];
        
        foreach ($reservations as $reservation) {
            $reservationDate = $reservation->getDate();
            $status = $reservation->getStatus();
            
            if ($reservationDate >= $today && in_array($status, ['en_attente', 'confirmée'])) {
                $stats['upcoming']++;
            } else {
                $stats['past']++;
            }
            
            switch ($status) {
                case 'confirmée':
                    $stats['confirmed']++;
                    break;
                case 'en_attente':
                    $stats['pending']++;
                    break;
                case 'annulée':
                    $stats['cancelled']++;
                    break;
            }
        }
        
        return $stats;
    }
}