<?php

namespace App\DataFixtures;

use App\Entity\Plat;
use App\Entity\Menu;
use App\Entity\RestaurantTable;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    private $faker;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Créer des utilisateurs
        $this->createUsers($manager);

        // 2. Créer des plats
        $this->createPlats($manager);

        // 3. Créer des menus avec références aux plats
        $this->createMenus($manager);

        // 4. Créer des tables
        $this->createTables($manager);

        // 5. Créer des réservations avec références
        $this->createReservations($manager);

        $manager->flush();
    }

    private function createUsers(ObjectManager $manager): void
    {
        // Créer admin
        $admin = new User();
        $admin->setEmail('admin@restaurant.com');
        $admin->setNom('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setCreatedAt(new \DateTime());
        $manager->persist($admin);
        $this->addReference('user_admin', $admin);

        // Créer plusieurs clients
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();
            $user->setEmail($this->faker->email());
            $user->setNom($this->faker->lastName());
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setCreatedAt($this->faker->dateTimeBetween('-1 year', 'now'));
            $manager->persist($user);
            $this->addReference('user_' . $i, $user);
        }
    }

    private function createPlats(ObjectManager $manager): void
    {
        $categories = ['Entrée', 'Plat principal', 'Dessert', 'Boisson'];
        $nomsPlats = [
            'Soupe à l\'oignon', 'Salade César', 'Foie gras',
            'Steak frites', 'Poulet rôti', 'Saumon grillé',
            'Tarte Tatin', 'Crème brûlée', 'Mousse au chocolat',
            'Vin rouge', 'Eau minérale', 'Café'
        ];

        for ($i = 0; $i < 12; $i++) {
            $plat = new Plat();
            $plat->setNom($nomsPlats[$i] ?? $this->faker->word());
            $plat->setDescription($this->faker->sentence(15));
            $plat->setPrix($this->faker->randomFloat(2, 5, 50));
            $plat->setCategorie($categories[$i % 4]);
            $plat->setCreatedAt($this->faker->dateTimeBetween('-6 months', 'now'));
            
            $manager->persist($plat);
            $this->addReference('plat_' . $i, $plat);
        }
    }

    private function createMenus(ObjectManager $manager): void
    {
        $menus = [
            'Menu Découverte' => [50.00, [0, 1, 3, 6]],
            'Menu Gourmet' => [85.00, [2, 4, 5, 7]],
            'Menu Enfant' => [25.00, [1, 8, 9]],
            'Menu Végétarien' => [45.00, [0, 1, 6, 10]]
        ];

        $i = 0;
        foreach ($menus as $nom => $details) {
            $menu = new Menu();
            $menu->setNom($nom);
            $menu->setPrixTotal($details[0]);
            
            // CORRECTION ICI : Deuxième paramètre est le nom de la classe
            foreach ($details[1] as $platIndex) {
                $plat = $this->getReference('plat_' . $platIndex, Plat::class);
                $menu->addPlat($plat);
            }
            
            $manager->persist($menu);
            $this->addReference('menu_' . $i, $menu);
            $i++;
        }
    }

    private function createTables(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $table = new RestaurantTable();
            $table->setNumero($i);
            $table->setCapacite($this->faker->randomElement([2, 4, 6, 8]));
            $table->setStatus($this->faker->boolean(70)); // 70% de chances d'être libre
            
            $manager->persist($table);
            $this->addReference('table_' . $i, $table);
        }
    }

    private function createReservations(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $reservation = new Reservation();
            
            // CORRECTION ICI : Deuxième paramètre est le nom de la classe
            $user = $this->getReference('user_' . ($i % 10 + 1), User::class);
            $table = $this->getReference('table_' . ($i % 15 + 1), RestaurantTable::class);
            
            $reservation->setClient($user);
            $reservation->setRestaurantTable($table);
            
            $date = $this->faker->dateTimeBetween('now', '+3 months');
            $reservation->setDate($date);
            $reservation->setHeure($this->faker->randomElement([
                new \DateTime('12:00'),
                new \DateTime('13:00'),
                new \DateTime('19:00'),
                new \DateTime('20:00'),
                new \DateTime('21:00')
            ]));
            
            $reservation->setNbPersonnes($this->faker->numberBetween(1, 8));
            $reservation->setStatus($this->faker->randomElement(['confirmée', 'en attente', 'annulée']));
            
            $manager->persist($reservation);
        }
    }
}