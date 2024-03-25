<?php

namespace App\Service;

use Google\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GoogleApiService
{

    private ?Client $client = null;
    private ?\Google_Service_Sheets $sheetsService = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    private function getClient()
    {
        if ($this->client == null) {
            // configure the Google Client
            $client = new Client();
            $client->setApplicationName('Google Sheets API');
            $client->setScopes([
                \Google_Service_Sheets::SPREADSHEETS,
            ]);
            $client->setAccessType('offline');
            // credentials.json is the key file we downloaded while setting up our Google Sheets API
            $client->setAuthConfig($this->projectDir . '/var/credentials.json');
            $this->client = $client;
        }
        return $this->client;
    }

    public function getService(string $type)
    {
        return match ($type) {
            'sheets' => $this->getSheetService(),
            //'drive' => $this->getDriveService(),
            default => throw new \Exception("Unknown service $type")
        };
    }

    private function getSheetService()
    {
        if ($this->sheetsService == null) {
            $this->sheetsService = new \Google_Service_Sheets($this->getClient());
        }
        return $this->sheetsService;
    }

}
