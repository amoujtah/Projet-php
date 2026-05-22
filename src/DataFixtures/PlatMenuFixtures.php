<?php

namespace App\DataFixtures;

use App\Entity\Plat;
use App\Entity\Menu;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PlatMenuFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des Plats
        $plat1 = new Plat();
        $plat1->setNom('Salade César')
              ->setDescription('Salade avec poulet, parmesan et croûtons')
              ->setPrix('5.50')
              ->setCategorie('Entrée')
              ->setCreatedAt(new \DateTime());
        $manager->persist($plat1);

        $plat2 = new Plat();
        $plat2->setNom('Pizza Margherita')
              ->setDescription('Pizza classique avec tomate, mozzarella et basilic')
              ->setPrix('8.00')
              ->setCategorie('Plat principal')
              ->setCreatedAt(new \DateTime());
        $manager->persist($plat2);

        $plat3 = new Plat();
        $plat3->setNom('Tiramisu')
              ->setDescription('Dessert italien crémeux au café')
              ->setPrix('4.50')
              ->setCategorie('Dessert')
              ->setCreatedAt(new \DateTime());
        $manager->persist($plat3);

        // Création d’un Menu
        $menu1 = new Menu();
        $menu1->setNom('Menu Italien')
              ->setPrixTotal('18.00');
        $menu1->addPlat($plat1)
              ->addPlat($plat2)
              ->addPlat($plat3);
        $manager->persist($menu1);

        $manager->flush();
    }
}
