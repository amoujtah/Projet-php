<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
{
    $admin = new User();
    $admin->setNom('Admin');
    $admin->setEmail('admin@example.com');
    $admin->setType('admin'); // ✅ AJOUT
    $admin->setRoles(['ROLE_ADMIN']);
    $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
    $admin->setCreatedAt(new \DateTime());
    $manager->persist($admin);

    $serveur = new User();
    $serveur->setNom('Serveur');
    $serveur->setEmail('serveur@example.com');
    $serveur->setType('serveur'); // ✅ AJOUT
    $serveur->setRoles(['ROLE_SERVEUR']);
    $serveur->setPassword($this->hasher->hashPassword($serveur, 'serveur123'));
    $serveur->setCreatedAt(new \DateTime());
    $manager->persist($serveur);

    $client = new User();
    $client->setNom('Client');
    $client->setEmail('client@example.com');
    $client->setType('client'); // ✅ (optionnel mais propre)
    $client->setRoles(['ROLE_CLIENT']);
    $client->setPassword($this->hasher->hashPassword($client, 'client123'));
    $client->setCreatedAt(new \DateTime());
    $manager->persist($client);

    $manager->flush();
}

}
