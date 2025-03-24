<?php

namespace App\Service;

use Google\Service\Sheets\ValueRange;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SheetProspectsService
{

    public function __construct(
        #[Autowire('%env(SPREADSHEET_ID2)%')]
        private readonly string $spreadsheetId,

        #[Autowire(lazy: true)]
        private readonly GoogleApiService $googleApiService,
    ) {}

    /**
     * Enregistre les données utilisateur dans Google Sheets
     */
    public function saveProspect(array $userData): void
    {
        $valueRange = new ValueRange();
        $valueRange->setValues([
            array_values($userData)
        ]);

        $service = $this->googleApiService->getService('sheets');
        $service->spreadsheets_values->append(
            $this->spreadsheetId,
            "A:" . chr(65 + count($userData) - 1),
            $valueRange,
            ['valueInputOption' => 'RAW']
        );
    }

}
