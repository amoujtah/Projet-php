<?php
namespace App\Controller;

use App\Entity\Plat;
use App\Entity\Menu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
    #[Route('/carte', name: 'app_public_carte')]
    public function carte(EntityManagerInterface $em): Response
    {
        $plats = $em->getRepository(Plat::class)->findAll();
        $menus = $em->getRepository(Menu::class)->findAll();
        
        // Grouper les plats par catégorie
        $platsParCategorie = [];
        foreach ($plats as $plat) {
            $categorie = $plat->getCategorie();
            if (!isset($platsParCategorie[$categorie])) {
                $platsParCategorie[$categorie] = [];
            }
            $platsParCategorie[$categorie][] = $plat;
        }
        
        return $this->render('public/carte.html.twig', [
            'platsParCategorie' => $platsParCategorie,
            'menus' => $menus,
        ]);
    }
    
    #[Route('/reserver', name: 'app_public_reserver')]
    public function reserver(): Response
    {
        // Rediriger vers la connexion ou l'inscription
        if (!$this->getUser()) {
            $this->addFlash('info', 'Veuillez vous connecter ou créer un compte pour réserver.');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->redirectToRoute('app_client_reservation_new');
    }
    
    #[Route('/contact', name: 'app_public_contact')]
    public function contact(): Response
    {
        // Solution 1: Si vous avez un template contact.html.twig directement dans templates/
        return $this->render('contact.html.twig');
        
        // OU Solution 2: Si vous avez un template public/contact.html.twig
        // return $this->render('public/contact.html.twig');
    }
}
?>