<?php

namespace App\Controller;

use Nelmio\ApiDocBundle\ApiDocGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiDocController extends AbstractController
{
    #[Route('/api/doc.json', name: 'api_doc_json', methods: ['GET'])]
    public function apiDocJson(ApiDocGenerator $apiDocGenerator): Response
    {
        $spec = $apiDocGenerator->generate()->toArray();

        return $this->json($spec);
    }

    #[Route('/api/doc', name: 'api_doc', methods: ['GET'])]
    public function apiDoc(): Response
    {
        return $this->render('api_doc/index.html.twig');
    }
}
