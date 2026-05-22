<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StatusController extends AbstractController
{
    #[Route('/status/health', name: 'app_health')]
    public function health(EntityManagerInterface $em): JsonResponse
    {
        try {
            $em->getConnection()->connect();
            $status = 'healthy';
        } catch (\Exception $e) {
            $status = 'unhealthy';
        }
        
        return $this->json([
            'status' => $status,
            'timestamp' => (new \DateTime())->format('c'),
            'version' => '1.0.0'
        ]);
    }
}