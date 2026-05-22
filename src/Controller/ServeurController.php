<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\RestaurantTable;
use App\Entity\User;
use App\Entity\Plat;
use App\Entity\Menu;
use App\Repository\ReservationRepository;
use App\Repository\RestaurantTableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/serveur')]
#[IsGranted('ROLE_SERVEUR')]
class ServeurController extends AbstractController
{
    #[Route('/', name: 'app_serveur_dashboard')]
    public function dashboard(
        ReservationRepository $reservationRepo,
        RestaurantTableRepository $tableRepo
    ): Response {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        $reservationsToday = $reservationRepo->createQueryBuilder('r')
            ->where('r.date >= :startOfDay')
            ->andWhere('r.date < :endOfDay')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('startOfDay', $today)
            ->setParameter('endOfDay', $tomorrow)
            ->setParameter('statuses', ['en_attente', 'confirmée'])
            ->orderBy('r.date', 'ASC')
            ->getQuery()
            ->getResult();
        
        $tables = $tableRepo->findAll();
        
        return $this->render('serveur/dashboard.html.twig', [
            'reservations' => $reservationsToday,
            'tables' => $tables,
            'today' => $today,
        ]);
    }

    #[Route('/reservations', name: 'app_serveur_reservations')]
    public function reservations(ReservationRepository $reservationRepo): Response
    {
        $reservations = $reservationRepo->findBy(
            [],
            ['date' => 'DESC'],
            50
        );
        
        return $this->render('serveur/reservations/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservations/ajouter', name: 'app_serveur_reservation_new')]
    public function newReservation(
        Request $request,
        EntityManagerInterface $em,
        RestaurantTableRepository $tableRepo
    ): Response {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            if (empty($data['nom']) || empty($data['email']) || empty($data['date']) || 
                empty($data['heure']) || empty($data['nb_personnes']) || empty($data['table_id'])) {
                $this->addFlash('error', 'Tous les champs obligatoires doivent être remplis.');
                return $this->redirectToRoute('app_serveur_reservation_new');
            }
            
            try {
                $dateTime = new \DateTime($data['date'] . ' ' . $data['heure']);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date ou heure invalide.');
                return $this->redirectToRoute('app_serveur_reservation_new');
            }
            
            $userRepo = $em->getRepository(User::class);
            $client = $userRepo->findOneBy(['email' => $data['email']]);
            
            if (!$client) {
                $client = new User();
                $client->setEmail($data['email']);
                $client->setNom($data['nom']);
                
                if (!empty($data['phone'])) {
                    $client->setPhone($data['phone']);
                }
                
                if (method_exists($client, 'setCreatedAt')) {
                    $client->setCreatedAt(new \DateTime());
                }
                $client->setRoles(['ROLE_CLIENT']);
                
                $tempPassword = bin2hex(random_bytes(8));
                $client->setPassword(password_hash($tempPassword, PASSWORD_DEFAULT));
                
                $em->persist($client);
            }
            
            $table = $tableRepo->find($data['table_id']);
            if (!$table) {
                $this->addFlash('error', 'Table non trouvée.');
                return $this->redirectToRoute('app_serveur_reservation_new');
            }
            
            if (!$table->isStatus()) {
                $this->addFlash('error', 'Cette table n\'est pas disponible.');
                return $this->redirectToRoute('app_serveur_reservation_new');
            }
            
            $existingReservation = $em->getRepository(Reservation::class)
                ->createQueryBuilder('r')
                ->where('r.restaurantTable = :table')
                ->andWhere('r.date = :dateTime')
                ->andWhere('r.status IN (:statuses)')
                ->setParameter('table', $table)
                ->setParameter('dateTime', $dateTime)
                ->setParameter('statuses', ['en_attente', 'confirmée'])
                ->getQuery()
                ->getOneOrNullResult();
            
            if ($existingReservation) {
                $this->addFlash('error', 'Cette table est déjà réservée à cette heure.');
                return $this->redirectToRoute('app_serveur_reservation_new');
            }
            
            $reservation = new Reservation();
            $reservation->setClient($client);
            $reservation->setRestaurantTable($table);
            $reservation->setDate($dateTime);
            $reservation->setNbPersonnes((int) $data['nb_personnes']);
            $reservation->setStatus('confirmée');
            
            $em->persist($reservation);
            $em->flush();
            
            $this->addFlash('success', 'Réservation créée avec succès !');
            return $this->redirectToRoute('app_serveur_dashboard');
        }
        
        $tables = $tableRepo->findBy(['status' => true]);
        
        return $this->render('serveur/reservations/new.html.twig', [
            'tables' => $tables,
            'today' => new \DateTime(),
        ]);
    }

    #[Route('/reservations/{id}/annuler', name: 'app_serveur_reservation_cancel', methods: ['POST'])]
    public function cancelReservation(
        Reservation $reservation,
        EntityManagerInterface $em
    ): Response {
        $reservation->setStatus('annulée');
        $em->flush();
        
        $this->addFlash('success', 'Réservation annulée avec succès !');
        return $this->redirectToRoute('app_serveur_dashboard');
    }

    #[Route('/reservations/{id}/honorer', name: 'app_serveur_reservation_honor', methods: ['POST'])]
    public function honorReservation(
        Reservation $reservation,
        EntityManagerInterface $em
    ): Response {
        $reservation->setStatus('honorée');
        $em->flush();
        
        $this->addFlash('success', 'Réservation honorée avec succès !');
        return $this->redirectToRoute('app_serveur_dashboard');
    }

    #[Route('/reservations/{id}/confirmer', name: 'app_serveur_reservation_confirm', methods: ['POST'])]
    public function confirmReservation(
        Reservation $reservation,
        EntityManagerInterface $em
    ): Response {
        if ($reservation->getStatus() === 'en_attente') {
            $reservation->setStatus('confirmée');
            $em->flush();
            
            $this->addFlash('success', 'Réservation confirmée avec succès !');
        }
        
        return $this->redirectToRoute('app_serveur_reservations');
    }

    #[Route('/tables', name: 'app_serveur_tables')]
    public function tables(RestaurantTableRepository $tableRepo): Response
    {
        $tables = $tableRepo->findBy([], ['numero' => 'ASC']);
        
        return $this->render('serveur/tables/index.html.twig', [
            'tables' => $tables,
        ]);
    }

    #[Route('/tables/{id}/changer-statut', name: 'app_serveur_table_status', methods: ['POST'])]
    public function changeTableStatus(
        RestaurantTable $table,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $status = $request->request->get('status') === 'true';
        $table->setStatus($status);
        $em->flush();
        
        $this->addFlash('success', 'Statut de la table mis à jour !');
        return $this->redirectToRoute('app_serveur_tables');
    }

    #[Route('/tables/ajouter', name: 'app_serveur_table_add', methods: ['POST'])]
    public function addTable(Request $request, EntityManagerInterface $em): Response
    {
        $numero = $request->request->get('numero');
        $capacite = $request->request->get('capacite');
        $status = $request->request->get('status') === '1';

        $existingTable = $em->getRepository(RestaurantTable::class)
            ->findOneBy(['numero' => $numero]);
        
        if ($existingTable) {
            $this->addFlash('error', 'Une table avec ce numéro existe déjà.');
            return $this->redirectToRoute('app_serveur_tables');
        }

        $table = new RestaurantTable();
        $table->setNumero((int) $numero);
        $table->setCapacite((int) $capacite);
        $table->setStatus($status);

        $em->persist($table);
        $em->flush();

        $this->addFlash('success', 'Table ajoutée avec succès !');
        return $this->redirectToRoute('app_serveur_tables');
    }

    #[Route('/tables/{id}/modifier', name: 'app_serveur_table_edit', methods: ['POST'])]
    public function editTable(RestaurantTable $table, Request $request, EntityManagerInterface $em): Response
    {
        $newNumero = (int) $request->request->get('numero');
        
        if ($newNumero !== $table->getNumero()) {
            $existingTable = $em->getRepository(RestaurantTable::class)
                ->findOneBy(['numero' => $newNumero]);
            
            if ($existingTable && $existingTable->getId() !== $table->getId()) {
                $this->addFlash('error', 'Une table avec ce numéro existe déjà.');
                return $this->redirectToRoute('app_serveur_tables');
            }
        }
        
        $table->setNumero($newNumero);
        $table->setCapacite((int) $request->request->get('capacite'));
        $table->setStatus($request->request->get('status') === '1');

        $em->flush();

        $this->addFlash('success', 'Table modifiée avec succès !');
        return $this->redirectToRoute('app_serveur_tables');
    }

    #[Route('/tables/{id}/supprimer', name: 'app_serveur_table_delete', methods: ['POST'])]
    public function deleteTable(RestaurantTable $table, EntityManagerInterface $em): Response
    {
        $reservationRepo = $em->getRepository(Reservation::class);
        $futureReservations = $reservationRepo->createQueryBuilder('r')
            ->where('r.restaurantTable = :table')
            ->andWhere('r.date >= :today')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('table', $table)
            ->setParameter('today', new \DateTime())
            ->setParameter('statuses', ['en_attente', 'confirmée'])
            ->getQuery()
            ->getResult();

        if (count($futureReservations) > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette table car elle a des réservations futures.');
            return $this->redirectToRoute('app_serveur_tables');
        }

        $pastReservations = $reservationRepo->findBy(['restaurantTable' => $table]);
        foreach ($pastReservations as $reservation) {
            $em->remove($reservation);
        }

        $em->remove($table);
        $em->flush();

        $this->addFlash('success', 'Table et ses réservations passées supprimées avec succès !');
        return $this->redirectToRoute('app_serveur_tables');
    }

    #[Route('/carte', name: 'app_serveur_carte')]
    public function carte(EntityManagerInterface $em): Response
    {
        $platRepository = $em->getRepository(Plat::class);
        $menuRepository = $em->getRepository(Menu::class);
        
        $plats = $platRepository->findBy([], ['categorie' => 'ASC', 'nom' => 'ASC']);
        $menus = $menuRepository->findAll();
        
        return $this->render('serveur/carte/index.html.twig', [
            'plats' => $plats,
            'menus' => $menus,
        ]);
    }

    #[Route('/statistiques', name: 'app_serveur_statistics')]
    public function statistics(
        ReservationRepository $reservationRepo,
        RestaurantTableRepository $tableRepo
    ): Response {
        $today = new \DateTime();
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        $endOfMonth->setTime(23, 59, 59);
        
        $startOfDay = new \DateTime('today');
        $endOfDay = new \DateTime('tomorrow');
        
        $todayReservations = $reservationRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.date >= :startOfDay')
            ->andWhere('r.date < :endOfDay')
            ->andWhere('r.status = :status')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->setParameter('status', 'confirmée')
            ->getQuery()
            ->getSingleScalarResult();
        
        $todayTotalReservations = $reservationRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.date >= :startOfDay')
            ->andWhere('r.date < :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->getQuery()
            ->getSingleScalarResult();
        
        $monthReservations = $reservationRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.date >= :start')
            ->andWhere('r.date <= :end')
            ->andWhere('r.status = :status')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->setParameter('status', 'confirmée')
            ->getQuery()
            ->getSingleScalarResult();
        
        $totalTables = $tableRepo->count([]);
        $occupiedTables = $tableRepo->count(['status' => false]);
        $availableTables = $totalTables - $occupiedTables;
        
        $statusSummary = $reservationRepo->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->where('r.date >= :startOfDay')
            ->andWhere('r.date < :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->groupBy('r.status')
            ->getQuery()
            ->getResult();
        
        return $this->render('serveur/statistics.html.twig', [
            'today' => $today,
            'today_reservations' => $todayReservations,
            'today_total_reservations' => $todayTotalReservations,
            'month_reservations' => $monthReservations,
            'total_tables' => $totalTables,
            'occupied_tables' => $occupiedTables,
            'available_tables' => $availableTables,
            'status_summary' => $statusSummary,
        ]);
    }

    #[Route('/carte/plats/ajouter', name: 'app_serveur_plat_add', methods: ['POST'])]
    public function addPlat(Request $request, EntityManagerInterface $em): Response
    {
        $nom = $request->request->get('nom');
        $categorie = $request->request->get('categorie');
        $description = $request->request->get('description');
        $prix = $request->request->get('prix');
        
        $plat = new Plat();
        $plat->setNom($nom);
        $plat->setCategorie($categorie);
        $plat->setDescription($description);
        $plat->setPrix($prix);
        $plat->setDisponible(true);
        
        $em->persist($plat);
        $em->flush();
        
        $this->addFlash('success', 'Plat ajouté avec succès !');
        return $this->redirectToRoute('app_serveur_carte');
    }

    #[Route('/carte/menus/ajouter', name: 'app_serveur_menu_add', methods: ['POST'])]
    public function addMenu(Request $request, EntityManagerInterface $em): Response
    {
        // Utilisez $request->get() au lieu de $request->request->get() pour éviter l'erreur de non-scalaire
        $nom = $request->get('nom');
        $description = $request->get('description');
        $prix = $request->get('prix');
        $categorie = $request->get('categorie');
        
        // CORRECTION CRITIQUE : Utilisez $request->get() sans valeur par défaut
        $platsParam = $request->get('plats');
        $platsIds = [];
        
        // Gestion des platsIds
        if ($platsParam !== null) {
            if (is_array($platsParam)) {
                $platsIds = $platsParam;
            } elseif (is_string($platsParam)) {
                // Si c'est une chaîne (un seul ID ou plusieurs séparés par des virgules)
                $platsIds = array_filter(array_map('trim', explode(',', $platsParam)));
            } else {
                $platsIds = [(string) $platsParam];
            }
        }
        
        $menu = new Menu();
        $menu->setNom($nom);
        $menu->setDescription($description);
        $menu->setPrixTotal($prix);
        $menu->setCategorie($categorie);
        $menu->setDisponible(true);
        
        // Ajouter les plats au menu
        if (!empty($platsIds)) {
            $platRepository = $em->getRepository(Plat::class);
            foreach ($platsIds as $platId) {
                $plat = $platRepository->find($platId);
                if ($plat) {
                    $menu->addPlat($plat);
                }
            }
        }
        
        $em->persist($menu);
        $em->flush();
        
        $this->addFlash('success', 'Menu ajouté avec succès !');
        return $this->redirectToRoute('app_serveur_carte');
    }

    #[Route('/carte/menus/{id}/modifier', name: 'app_serveur_menu_edit', methods: ['POST'])]
    public function editMenu(
        Menu $menu,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Utilisez $request->get() au lieu de $request->request->get()
        $nom = $request->get('nom');
        $categorie = $request->get('categorie');
        $description = $request->get('description');
        $prix = $request->get('prix');
        
        // CORRECTION CRITIQUE : Même approche que pour addMenu()
        $platsParam = $request->get('plats');
        $platsIds = [];
        
        if ($platsParam !== null) {
            if (is_array($platsParam)) {
                $platsIds = $platsParam;
            } elseif (is_string($platsParam)) {
                $platsIds = array_filter(array_map('trim', explode(',', $platsParam)));
            } else {
                $platsIds = [(string) $platsParam];
            }
        }
        
        $menu->setNom($nom);
        $menu->setCategorie($categorie);
        $menu->setDescription($description);
        $menu->setPrixTotal($prix);
        
        // Réinitialiser les plats
        foreach ($menu->getPlats() as $plat) {
            $menu->removePlat($plat);
        }
        
        // Ajouter les nouveaux plats
        if (!empty($platsIds)) {
            $platRepository = $em->getRepository(Plat::class);
            foreach ($platsIds as $platId) {
                $plat = $platRepository->find($platId);
                if ($plat) {
                    $menu->addPlat($plat);
                }
            }
        }
        
        $em->flush();
        
        $this->addFlash('success', 'Menu modifié avec succès !');
        return $this->redirectToRoute('app_serveur_carte');
    }

    #[Route('/carte/menus/{id}/supprimer', name: 'app_serveur_menu_delete', methods: ['POST'])]
    public function deleteMenu(
        Menu $menu,
        EntityManagerInterface $em
    ): Response {
        $em->remove($menu);
        $em->flush();
        
        $this->addFlash('success', 'Menu supprimé avec succès !');
        return $this->redirectToRoute('app_serveur_carte');
    }

    #[Route('/carte/menus/{id}/changer-disponibilite', name: 'app_serveur_menu_toggle', methods: ['POST'])]
    public function toggleMenuAvailability(
        Menu $menu,
        EntityManagerInterface $em
    ): Response {
        $menu->setDisponible(!$menu->isDisponible());
        $em->flush();
        
        $status = $menu->isDisponible() ? 'disponible' : 'indisponible';
        $this->addFlash('success', 'Menu marqué comme ' . $status . ' !');
        return $this->redirectToRoute('app_serveur_carte');
    }

    #[Route('/carte/plats/{id}/modifier', name: 'app_serveur_plat_edit', methods: ['POST'])]
    public function editPlat(
        Plat $plat,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $nom = $request->request->get('nom');
        $categorie = $request->request->get('categorie');
        $description = $request->request->get('description');
        $prix = $request->request->get('prix');
        $disponible = $request->request->get('disponible') === '1';
        
        $plat->setNom($nom);
        $plat->setCategorie($categorie);
        $plat->setDescription($description);
        $plat->setPrix($prix);
        $plat->setDisponible($disponible);
        
        $em->flush();
        
        $this->addFlash('success', 'Plat modifié avec succès !');
        return $this->redirectToRoute('app_serveur_carte');
    }

    #[Route('/carte/plats/{id}/supprimer', name: 'app_serveur_plat_delete', methods: ['POST'])]
    public function deletePlat(
        Plat $plat,
        EntityManagerInterface $em
    ): Response {
        $em->remove($plat);
        $em->flush();
        
        $this->addFlash('success', 'Plat supprimé avec succès !');
        return $this->redirectToRoute('app_serveur_carte');
    }

    #[Route('/carte/plats/{id}/changer-disponibilite', name: 'app_serveur_plat_toggle', methods: ['POST'])]
    public function togglePlatAvailability(
        Plat $plat,
        EntityManagerInterface $em
    ): Response {
        $plat->setDisponible(!$plat->isDisponible());
        $em->flush();
        
        $status = $plat->isDisponible() ? 'disponible' : 'indisponible';
        $this->addFlash('success', 'Plat marqué comme ' . $status . ' !');
        return $this->redirectToRoute('app_serveur_carte');
    }
}