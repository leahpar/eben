<?php

namespace App\Entity;

class DevisInput
{

    /**
     * Type de fenêtre :
     * FOB1 : Fenêtre oscillo-battante 1 battant
     * FOB2 : Fenêtre oscillo-battante 2 battants
     * BC   : Baie coulissante
     * PF   : Porte fenêtre
     */
    public ?string $type = null;

    /**
     * Hauteur & Largeur fenêtre
     */
    public ?int $hauteur = null;
    public ?int $largeur = null;

    /**
     * Bois fenêtre
     * pin blanc, pin beige, chêne naturel... (en slug)
     */
    public ?string $bois = null;

    /**
     * Type de poignée :
     * Moderne, Bouton olive...
     */
    public ?string $poignee = null;

    /**
     * Couleur de poignée :
     * blanc, noir, laiton...
     */
    public ?string $pCouleur = null;

    /**
     * Couleur alu extérieur
     */
    public ?string $couleur = null;


    public function __construct(?array $data = [])
    {
        $this->hydrate($data);
    }

    private function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

}
