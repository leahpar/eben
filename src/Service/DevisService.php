<?php

namespace App\Service;

use App\Entity\DevisInput;

class DevisService
{

    public function __construct(
        private readonly SheetTarifLoader $sheetTarifLoader,
    ) {}

    public function devis(DevisInput $input, array &$debug = []): float
    {
        $montant = 0;
        $debug['input'] = $input->toArray();

        // Taille fenetre
        $montant += $this->getMontantTaille($input, $debug);

        // Option pin/chêne
        if (str_starts_with($input->bois, 'chene')) {
            $montant *= 1.12; // +12%
        }

        // Poignées
        $montant += $this->getMontantPoignee($input, $debug);

        // NB: couleur n'impacte pas le montant

        return $montant;
    }

    private function getMontantTaille(DevisInput $input, array &$debug): float
    {
        $tarifs = $this->sheetTarifLoader->get($input->type);
        if ($tarifs === null) {
            throw new \Exception("Type de fenêtre invalide");
        }

        $hauteur = 0;
        foreach ($tarifs as $key => $value) {
            if ($input->hauteur >= $key) $hauteur = $key;
        }

        $data = $tarifs[$hauteur];
        $largeur = 0;
        foreach ($data as $key => $value) {
            if ($input->largeur >= $key) $largeur = $key;
        }

        $debug['hauteur'] = $hauteur;
        $debug['largeur'] = $largeur;

        $montant = $tarifs[$hauteur][$largeur];
        $debug['montant_fenetre'] = $montant;
        if ($montant < 0) {
            throw new \Exception($this->sheetTarifLoader->getMessage($montant));
        }

        return $montant;

    }

    private function getMontantPoignee(DevisInput $input, array &$debug): float
    {
        $tarifs = $this->sheetTarifLoader->get('Poignees');

        $data = $tarifs[$input->poignee] ?? null;
        if ($data === null) {
            throw new \Exception('Type de poignée invalide (1)');
        }

        $montant = $data[$input->pCouleur] ?? null;
        $debug['montant_poignee'] = $montant;
        if ($montant === null) {
            throw new \Exception('Type de poignée invalide');
        }

        if ($montant < 0) {
            throw new \Exception('Couleur de poignée invalide (2)');
        }

        return $montant;
    }

    public function defauts(string $type)
    {
        $params = $this->sheetTarifLoader->get('Params');
        $poignees = $this->sheetTarifLoader->get('Poignees');
        return [
            ...$params[$type],
//            'pCouleurs' => array_map(
//                fn ($row) => array_keys(array_filter($row, fn ($couleur) => $couleur >= 0)),
//                $poignees
//            ),
            'pCouleurs'=> $poignees,
        ];
    }

}
