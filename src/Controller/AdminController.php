<?php
namespace App\Controller;

use App\Entity\RestaurantTable;
use App\Entity\Plat;
use App\Entity\Menu;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\RestaurantTableType;
use App\Form\PlatType;
use App\Form\MenuType;
use App\Form\UserType;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Repository\PlatRepository;
use App\Repository\RestaurantTableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        ReservationRepository $reservationRepo,
        UserRepository $userRepo,
        RestaurantTableRepository $tableRepository,
        EntityManagerInterface $em
    ): Response {
        $today = new \DateTime();
        
        // Statistiques
        $stats = [
            'reservations_aujourdhui' => $reservationRepo->count(['date' => $today]),
            'reservations_en_attente' => $reservationRepo->count(['status' => 'en_attente']),
            'tables_libres' => $tableRepository->count(['status' => true]),
            'tables_occupees' => $tableRepository->count(['status' => false]),
        ];
        
        // Statistiques des utilisateurs par type
        $stats['admin_count'] = $userRepo->count(['type' => 'admin']);
        $stats['serveur_count'] = $userRepo->count(['type' => 'serveur']);
        $stats['client_count'] = $userRepo->count(['type' => 'client']);
        $stats['total_count'] = $userRepo->count([]);
        
        // Calcul du taux d'occupation
        $totalTables = count($tableRepository->findAll());
        if ($totalTables > 0) {
            $stats['taux_occupation'] = round(($stats['tables_occupees'] / $totalTables) * 100, 1);
        }
        
        // CORRECTION : Supprimez 'heure' => 'DESC'
        $reservations = $reservationRepo->findBy([], ['date' => 'DESC'], 10);
        
        // Toutes les tables
        $tables = $tableRepository->findAll();
        
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'reservations' => $reservations,
            'tables' => $tables,
        ]);
    }

    // ============ GESTION DES TABLES ============
    #[Route('/tables', name: 'app_admin_tables')]
    public function tables(EntityManagerInterface $em): Response
    {
        $tables = $em->getRepository(RestaurantTable::class)->findAll();
        
        return $this->render('admin/tables/index.html.twig', [
            'tables' => $tables,
        ]);
    }

    #[Route('/tables/nouvelle', name: 'app_admin_table_new')]
    public function newTable(Request $request, EntityManagerInterface $em): Response
    {
        $table = new RestaurantTable();
        $form = $this->createForm(RestaurantTableType::class, $table);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($table);
            $em->flush();
            
            $this->addFlash('success', 'Table créée avec succès !');
            return $this->redirectToRoute('app_admin_tables');
        }
        
        return $this->render('admin/tables/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tables/{id}/edit', name: 'app_admin_table_edit')]
    public function editTable(Request $request, RestaurantTable $table, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RestaurantTableType::class, $table);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            
            $this->addFlash('success', 'Table modifiée avec succès !');
            return $this->redirectToRoute('app_admin_tables');
        }
        
        return $this->render('admin/tables/edit.html.twig', [
            'form' => $form->createView(),
            'table' => $table,
        ]);
    }

    #[Route('/tables/{id}/supprimer', name: 'app_admin_table_delete', methods: ['POST'])]
    public function deleteTable(
        RestaurantTable $table, 
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$table->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des réservations
            $reservationCount = count($table->getReservations());
            
            if ($reservationCount > 0) {
                $this->addFlash('error', 'Impossible de supprimer la table. Elle a ' . $reservationCount . ' réservation(s). Supprimez d\'abord les réservations.');
            } else {
                $em->remove($table);
                $em->flush();
                
                $this->addFlash('success', 'Table supprimée avec succès !');
            }
        }
        
        return $this->redirectToRoute('app_admin_tables');
    }

    // ============ GESTION DES PLATS ============
    #[Route('/plats', name: 'app_admin_plats')]
    public function plats(PlatRepository $platRepository): Response
    {
        $plats = $platRepository->findAll();
        
        return $this->render('admin/plats/index.html.twig', [
            'plats' => $plats,
        ]);
    }

    #[Route('/plats/nouveau', name: 'app_admin_plat_new')]
    public function newPlat(Request $request, EntityManagerInterface $em): Response
    {
        $plat = new Plat();
        $plat->setCreatedAt(new \DateTime());
        
        $form = $this->createForm(PlatType::class, $plat);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($plat);
            $em->flush();
            
            $this->addFlash('success', 'Plat créé avec succès !');
            return $this->redirectToRoute('app_admin_plats');
        }
        
        return $this->render('admin/plats/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/plats/{id}/edit', name: 'app_admin_plat_edit')]
    public function editPlat(Request $request, Plat $plat, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PlatType::class, $plat);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            
            $this->addFlash('success', 'Plat modifié avec succès !');
            return $this->redirectToRoute('app_admin_plats');
        }
        
        return $this->render('admin/plats/edit.html.twig', [
            'form' => $form->createView(),
            'plat' => $plat,
        ]);
    }

    #[Route('/plats/{id}/supprimer', name: 'app_admin_plat_delete', methods: ['POST'])]
    public function deletePlat(
        Plat $plat, 
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$plat->getId(), $request->request->get('_token'))) {
            $em->remove($plat);
            $em->flush();
            
            $this->addFlash('success', 'Plat supprimé avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_plats');
    }

    // ============ GESTION DES MENUS ============
    #[Route('/menus', name: 'app_admin_menus')]
    public function menus(EntityManagerInterface $em): Response
    {
        $menus = $em->getRepository(Menu::class)->findAll();
        
        return $this->render('admin/menus/index.html.twig', [
            'menus' => $menus,
        ]);
    }

    #[Route('/menus/nouveau', name: 'app_admin_menu_new')]
    public function newMenu(Request $request, EntityManagerInterface $em): Response
    {
        $menu = new Menu();
        $form = $this->createForm(MenuType::class, $menu);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($menu);
            $em->flush();
            
            $this->addFlash('success', 'Menu créé avec succès !');
            return $this->redirectToRoute('app_admin_menus');
        }
        
        return $this->render('admin/menus/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/menus/{id}/edit', name: 'app_admin_menu_edit')]
    public function editMenu(Request $request, Menu $menu, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MenuType::class, $menu);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            
            $this->addFlash('success', 'Menu modifié avec succès !');
            return $this->redirectToRoute('app_admin_menus');
        }
        
        $plats = $em->getRepository(Plat::class)->findAll();
        
        return $this->render('admin/menus/edit.html.twig', [
            'form' => $form->createView(),
            'menu' => $menu,
            'plats' => $plats,
        ]);
    }

    #[Route('/menus/{id}/supprimer', name: 'app_admin_menu_delete', methods: ['POST'])]
    public function deleteMenu(
        Menu $menu, 
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$menu->getId(), $request->request->get('_token'))) {
            $em->remove($menu);
            $em->flush();
            
            $this->addFlash('success', 'Menu supprimé avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_menus');
    }

    // ============ GESTION DES UTILISATEURS ============
    #[Route('/utilisateurs', name: 'app_admin_users')]
    public function users(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        
        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/utilisateurs/type/{type}', name: 'app_admin_users_by_type')]
    public function usersByType(string $type, EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findBy(['type' => $type], ['nom' => 'ASC']);
        
        $typeLabels = [
            'admin' => 'Administrateurs',
            'client' => 'Clients',
            'serveur' => 'Serveurs',
        ];
        
        return $this->render('admin/users/by_type.html.twig', [
            'users' => $users,
            'type' => $type,
            'typeLabel' => $typeLabels[$type] ?? $type,
        ]);
    }

    #[Route('/utilisateurs/nouveau', name: 'app_admin_user_new')]
    public function newUser(
        Request $request, 
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        
        $form = $this->createForm(UserType::class, $user, [
            'is_new' => true,
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'email existe déjà
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            
            if ($existingUser) {
                $this->addFlash('error', 'Un utilisateur avec l\'email "' . $user->getEmail() . '" existe déjà.');
                return $this->render('admin/users/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            
            // Hasher le mot de passe
            if ($user->getPlainPassword()) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $user->getPlainPassword()
                );
                $user->setPassword($hashedPassword);
            }
            
            try {
                $em->persist($user);
                $em->flush();
                
                $this->addFlash('success', 'Utilisateur créé avec succès !');
                return $this->redirectToRoute('app_admin_users');
                
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                    strpos($e->getMessage(), 'UNIQ_') !== false) {
                    $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                } else {
                    $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
                }
                
                return $this->render('admin/users/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }
        
        return $this->render('admin/users/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/utilisateurs/{id}/edit', name: 'app_admin_user_edit')]
    public function editUser(
        Request $request, 
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $originalEmail = $user->getEmail();
        
        $form = $this->createForm(UserType::class, $user);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification si l'email a été modifié
            if ($originalEmail !== $user->getEmail()) {
                // Vérifier si le nouvel email existe déjà
                $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('error', 'L\'email "' . $user->getEmail() . '" est déjà utilisé par un autre utilisateur.');
                    return $this->render('admin/users/edit.html.twig', [
                        'form' => $form->createView(),
                        'user' => $user,
                    ]);
                }
            }
            
            // Si un nouveau mot de passe est fourni, le hacher
            if ($user->getPlainPassword()) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $user->getPlainPassword()
                );
                $user->setPassword($hashedPassword);
            }
            
            try {
                $em->flush();
                
                $this->addFlash('success', 'Utilisateur modifié avec succès !');
                return $this->redirectToRoute('app_admin_users');
                
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                    strpos($e->getMessage(), 'UNIQ_') !== false) {
                    $this->addFlash('error', 'Cet email est déjà utilisé.');
                } else {
                    $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
                }
                
                return $this->render('admin/users/edit.html.twig', [
                    'form' => $form->createView(),
                    'user' => $user,
                ]);
            }
        }
        
        return $this->render('admin/users/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/utilisateurs/{id}/supprimer', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(
        User $user, 
        EntityManagerInterface $em,
        Request $request
    ): Response {
        // Empêcher l'utilisateur de se supprimer lui-même
        $currentUser = $this->getUser();
        if ($currentUser && $user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte !');
            return $this->redirectToRoute('app_admin_users');
        }
        
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            try {
                // Vérifier si l'utilisateur a des réservations
                $reservations = $user->getReservations();
                $reservationCount = count($reservations);
                
                if ($reservationCount > 0) {
                    $this->addFlash('error', 'Impossible de supprimer l\'utilisateur. Il a ' . $reservationCount . ' réservation(s).');
                } else {
                    $em->remove($user);
                    $em->flush();
                    
                    $this->addFlash('success', 'Utilisateur supprimé avec succès !');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }
        
        return $this->redirectToRoute('app_admin_users');
    }

    // ============ GESTION DES RÉSERVATIONS ============
    #[Route('/reservations', name: 'app_admin_reservations')]
    public function reservations(EntityManagerInterface $em): Response
    {
        // CORRECTION : Supprimez 'heure' => 'DESC'
        $reservations = $em->getRepository(Reservation::class)->findBy(
            [],
            ['date' => 'DESC']
        );
        
        // Récupérer toutes les tables pour le filtre
        $tables = $em->getRepository(RestaurantTable::class)->findAll();
        
        return $this->render('admin/reservations/index.html.twig', [
            'reservations' => $reservations,
            'tables' => $tables,
        ]);
    }

    #[Route('/reservations/{id}/changer-statut', name: 'app_admin_reservation_status', methods: ['POST'])]
    public function changeReservationStatus(
        Reservation $reservation, 
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $status = $request->request->get('status');
        
        if (in_array($status, ['confirmée', 'en_attente', 'annulée', 'honorée'])) {
            $reservation->setStatus($status);
            $em->flush();
            
            $this->addFlash('success', 'Statut de la réservation mis à jour !');
        }
        
        return $this->redirectToRoute('app_admin_reservations');
    }

    #[Route('/reservations/{id}/supprimer', name: 'app_admin_reservation_delete', methods: ['POST'])]
    public function deleteReservation(
        Reservation $reservation, 
        EntityManagerInterface $em,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $em->remove($reservation);
            $em->flush();
            
            $this->addFlash('success', 'Réservation supprimée avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_reservations');
    }
}