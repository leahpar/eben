<?php

namespace App\Controller;

use App\Entity\DevisInput;
use App\Service\DevisService;
use App\Service\SheetTarifLoader;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DevisController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index()
    {
        return $this->json([
            'api' => 'ok',
        ]);
    }

    #[Route('/load', name: 'load')]
    public function load(SheetTarifLoader $sheetTarifLoader)
    {
        $sheetTarifLoader->load();
        return $this->json([
            'load' => 'ok',
        ]);
    }

    #[Route('/test', name: 'test')]
    public function test()
    {
        return $this->json([
            'test' => 'ok',
        ]);
    }

    #[Route('/tarifs/{type}', name: 'tarifs')]
    public function tarif(string $type, SheetTarifLoader $sheetTarifLoader)
    {
        return $this->json([
            'tarif' => $sheetTarifLoader->get($type),
        ]);
    }

    #[Route('/devis', name: 'devis', methods: ['POST'])]
    public function devis(Request $request, DevisService $devisService, LoggerInterface $logger)
    {
        try {
            $debug = [];
            $input = new DevisInput($request->toArray());
            $montant = $devisService->devis($input, $debug);
            $defauts = $devisService->defauts($input->type);
            $response = [
                'montant' => $montant,
                'defauts' => $defauts,
                'debug' => $debug,
            ];

            $logger->debug(json_encode($response));
            return $this->json($response);
        }
        catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

}
