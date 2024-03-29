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

    #[Route('/textes', name: 'textes')]
    public function tarif(string $type, SheetTarifLoader $sheetTarifLoader)
    {
        return $this->json([
            'tarif' => $sheetTarifLoader->get($type),
        ]);
    }

    #[Route('/defauts', name: 'defauts')]
    public function defauts(Request $request, DevisService $devisService, LoggerInterface $logger)
    {
        try {
            $debug = [];
            $logger->debug(print_r($request->toArray(), true));
            $input = new DevisInput($request->toArray());
            $defauts = $devisService->defauts($input->type);
            $response = [
                ...$defauts,
//                'debug' => $debug,
            ];

            $logger->debug(print_r($response, true));
            return $this->json($response);
        }
        catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route('/devis', name: 'devis', methods: ['POST'])]
    public function devis(Request $request, DevisService $devisService, LoggerInterface $logger)
    {
        try {
            $debug = [];
            $logger->debug(print_r($request->toArray(), true));
            $input = new DevisInput($request->toArray());
            $montant = $devisService->devis($input, $debug);
            $response = [
                'montant' => $montant,
//                'debug' => $debug,
            ];

            $logger->debug(print_r($response, true));
            return $this->json($response);
        }
        catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }


}
