<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Votre API Symfony fonctionne !',
            'status' => 'success',
            'version' => '1.0.0',
            'path' => 'src/Controller/DefaultController.php',
        ]);
    }
}
