<?php

namespace App\Service;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SheetTarifLoader
{

    private PhpArrayAdapter $cache;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,

        #[Autowire('%env(SPREADSHEET_ID)%')]
        private readonly string $spreadsheetId,

        #[Autowire(lazy: true)]
        private readonly GoogleApiService $googleApiService,
    ) {
        $this->cache = new PhpArrayAdapter(
        // single file where values are cached
            $this->projectDir . '/var/data.cache.php',
            // a backup adapter, if you set values after warm-up
            new FilesystemAdapter()
        );
    }

    public function load(): void
    {
        $data = [
            'Params' => $this->load_params(),
            'FOB1' => $this->load_price_array('FOB1'),
            'FOB2' => $this->load_price_array('FOB2'),
            'PF2'  => $this->load_price_array('PF2'),
            'PFC2' => $this->load_price_array('PFC2'),
            'Poignees' => $this->load_price_array('Poignees', false),
            'Messages' => $this->load_messages(),
        ];
        $this->cache->warmUp($data);
    }

    private function load_messages(): array
    {
        $service = $this->googleApiService->getService('sheets');

        // get all the rows of a sheet
        $range = "messages"; // Name of the Sheet
        $response = $service->spreadsheets_values->get($this->spreadsheetId, $range);
        $data = $response->getValues();

        // ligne d'entête = liste des colonnes
        array_shift($data);

        $data = array_combine(
            array_column($data, 0),
            array_column($data, 1)
        );

        return $data;
    }


    private function load_price_array(string $onglet, ?bool $intKeys = true): array
    {
        $service = $this->googleApiService->getService('sheets');

        // get all the rows of a sheet
        $range = "'$onglet'"; // Name of the Sheet
        $response = $service->spreadsheets_values->get($this->spreadsheetId, $range);
        $data = $response->getValues();

        // Suppression 1e ligne (titre) et 2e ligne ("largeur")
        $data = array_slice($data, 2);

        // ligne d'entête = liste des colonnes
        $head = array_shift($data);
        if ($intKeys) {
            // on convertit en int
            $head = array_map(fn($col) => (int)preg_replace('/[^0-9-]/', '', $col), $head);
        }
        // on retire la 1e colonne ("hauteur") et la 2e colonne (référence)
        $head = array_slice($head, 2);

        $rows = $data;
        if ($intKeys) {
            // on convertit en int
            $rows = array_map(
                fn($row) => array_map(fn($col) => (int)preg_replace('/[^0-9-]/', '', $col), $row),
                $rows
            );
        }

        // La 1e colonne est le libellé ("hauteur") la 2e colonne est la référence
        $rows = array_combine(
            array_map(fn ($row) => $row[1], $rows),
            array_map(fn ($row) => array_slice($row, 2), $rows)
        );

        // on merge le tout
        $rows = array_map(
            fn ($row) => array_combine(
                $head,
                $row
            ),
            $rows
        );

        return $rows;
    }

    private function load_params(): array
    {
        $service = $this->googleApiService->getService('sheets');

        // get all the rows of a sheet
        $range = "'Params'"; // Name of the Sheet
        $response = $service->spreadsheets_values->get($this->spreadsheetId, $range);
        $data = $response->getValues();

        // Suppression 1e ligne (entête)
        array_shift($data);

        $rows = [];
        foreach ($data as $row) {
            $rows[$row[0]] = [
                'largeur'  => (int)preg_replace('/[^0-9-]/', '', $row[1]),
                'hauteur'  => (int)preg_replace('/[^0-9-]/', '', $row[2]),
                'bois'     => preg_split('/[\s\n,;]+/', $row[3]),
                'poignees' => preg_split('/[\s\n,;]+/', $row[4]),
            ];
        }

        return $rows;
    }

    public function get(string $reference): ?array
    {
        return $this->cache->getItem($reference)->get();
    }

    public function getMessage(string $code): string
    {
        $messages = $this->cache->getItem('Messages')->get();
        foreach ($messages as $c => $message) {
            if ($c == $code) return $message;
        }
        return "ERREUR";
    }

//    public function persist(Mariage $mariage): void
//    {
//        $row = EntityMapping::createDataRowFromMariage($mariage);
//
//        $body = new \Google_Service_Sheets_ValueRange();
//        $body->setValues([$row]);
//
//        $range = 'Base Photos'; // the service will detect the last row of this sheet
//
//        $options = ['valueInputOption' => 'USER_ENTERED'];
//
//        $service = $this->googleApiService->getService('sheets');
//        $service->spreadsheets_values->append(
//            $this->spreadsheetId,
//            $range,
//            $body,
//            $options
//        );
//    }
//
//    public function getAll(?string $filter = null, ?string $search = null, ?int $page = 1, ?int $limit = 24): array
//    {
//        $page = $page < 1 ? 1 : $page;
//        $from = ($page-1) * $limit;
//        $to = $from + $limit;
//
//        $cpt = 0;
//        $mariages = [];
//        $keys = $this->cache->getItem('keys')->get();
//
//        if ($limit == 0) {
//            $from = 0;
//            $to = count($keys);
//        }
//
//        foreach ($keys as $key) {
//            $mariage = $this->get($key);
//            if (($mariage->isInFilter($filter))
//             && $mariage->isSearch($search)
//            ) {
//                if ($cpt++ <= $from) continue;
//                if ($cpt > $to+1) continue;
//                $mariages[] = $mariage;
//            }
//        }
//
//        return [
//            'mariages' => $mariages,
//            'page' => $page,
//            'count' => $cpt,
//        ];
//    }

}
