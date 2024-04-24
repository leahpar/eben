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

        $montant = $data[$input->couleurPoignee] ?? null;
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
        // Valeurs par défaut
        $params = $this->sheetTarifLoader->get('Params');

        // Min/max
        $tarifs = $this->sheetTarifLoader->get($type);
        $hMin = 99999999;
        $hMax = 0;
        $lMin = 99999999;
        $lMax = 0;

        foreach ($tarifs as $key => $value) {
            $t = array_filter($value, fn($x) => $x>0);
            if (count($t) > 0) {
                // S'il y a des tarifs pour cette hauteur
                $hMin = min($hMin, $key);
                $hMax = max($hMax, $key);
                $lMin = min($lMin, min(array_keys($t)));
                $lMax = max($lMax, max(array_keys($t)));
            }
        }

        // Poignées
        $poignees = $this->sheetTarifLoader->get('Poignees');

        return [
            ...$params[$type],
            'hauteur_min' => $hMin,
            'hauteur_max' => $hMax,
            'largeur_min' => $lMin,
            'largeur_max' => $lMax,
            'couleurPoigneeLst'=> $poignees,
        ];



    }

}
