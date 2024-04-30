<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OdooRpcService
{

    private ?string $userSession = null;
    private string $requestId = 'eben_json';

    protected const SERVICES = [
        'dataset' => '/web/dataset/call_kw',
        'db_list' => '/web/database/list',
        'auth' => '/web/session/authenticate',
    ];

    public function __construct(
        private readonly HttpClientInterface $client,
        #[Autowire('%env(ODOO_URL)%')]      private readonly string $url,
        #[Autowire('%env(ODOO_LOGIN)%')]    private readonly string $login,
        #[Autowire('%env(ODOO_PASSWORD)%')] private readonly string $password,
        #[Autowire('%env(ODOO_DB_NAME)%')]  private readonly string $dbName,
    ) {}

    private function request(
        // string $method = call
        string $service = 'dataset',
        array $params = [],
    ): array
    {
        $url = $this->url . (static::SERVICES[$service] ?? throw new \Exception("Service $service invalide"));

        $headers = ($service != 'auth')
            ? ['Cookie' => 'session_id=' . $this->getUserSession()]
            : [];

        $json = [
            'jsonrpc' => '2.0',
            'method' => 'call', // $method
            'params' => $params,
            'id' => $this->requestId,
        ];
        $response = $this->client->request('POST', $url, [
            // Custom json_encode à cause des "/" dans les clés...
            // https://github.com/symfony/symfony/issues/50271
            // 'body' => json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'json' => $json,
            'headers' => [
                'Content-Type' => 'application/json',
                ...$headers,
            ]
        ]);

        $res = $response->toArray();
        $res['status'] = $response->getStatusCode();

        return $res;
    }

    private function authenticate(): void
    {
        $response = $this->request('auth', [
            'db' => $this->dbName,
            'login' => $this->login,
            'password' => $this->password,
        ]);

        $this->userSession = $response['result']['session_id'] ?? null;
        if (!$this->userSession) {
            throw new \Exception('Authentication failed');
        }
    }

    private function getUserSession()
    {
        if (!$this->userSession) {
            $this->authenticate();
        }
        return $this->userSession;
    }

    /**
     * Met à jour un lead bidon pour récupérer les slots
     */
    private function getCreneauxDisponibles()
    {
        $information = [
            //'firstname' => 'Leblanc',
            //'lastname' => 'Juste',
            //'email_from' => 'gameover@yopmail.com',
            //'phone' => '',
            'zip' => '75000',
            'country_id' => 'France',
        ];
        $args = [
            // ID Bidon mais toujours le même pour ne pas créer de contact à chaque fois
            'id' => "510fa7a4-b3ae-4cf8-868e-f77441864ab3",
            'type' => "opportunity",
            'team_id' => "quotation",
            ...$information
        ];

        $params = [
            'model' => 'eben.lead',
            'method' => 'create_or_update',
            'args' => [$args],
            'kwargs' => ['context' => []],
        ];
        return $this->request('dataset', $params)['result'];
    }

    /**
     * @see Drupal\eben_estimate\Form\Multistep\MultistepFiveAppointmentForm::buildDefaultCalendar()
     */
    public function getCalendrier(): array
    {
        $creneaux = $this->getCreneauxDisponibles();
        /*
         array:46 [
          0 => array:2 [
            "employee_id" => 25
            "datetime" => "2024-04-29 10:00:00"
          ]
          1 => array:2 [
            "employee_id" => 25
            "datetime" => "2024-04-29 12:00:00"
          ]
          ...
         */
        $employeeId = $creneaux[0]['employee_id'];

        $creneauxDispos = array_map(
            fn($slot) => (new \DateTime($slot['datetime']))->format('YmdHi'),
            $creneaux
        );
        /*
         * array:46 [
          0 => "202404291000"
          1 => "202404291200"
          ...
         */

        $calendrier = [];
        $begin = (new \DateTime())->add(new \DateInterval('P1D'));

        // Build default calendar with all slots.
        for ($i = 0; $i < 4; $i++) {
            // Add slots except for Sunday and Saturday.
            while (in_array($begin->format('w'), [0, 6])) {
                $begin->add(new \DateInterval('P1D'));
            }
            $creneaux = [];
            for ($h = 9; $h <= 18; $h++) {
                // Set hour on date.
                $begin->setTime($h, 0);
                //$timestamp = $begin->getTimestamp();
                $timestamp = $begin->format('YmdHi');
                $creneaux[$timestamp] = [
                    'employee_id' => $employeeId,
                    'datetime' => $begin->format('Y-m-d H:i:s'),
                    'heure' => $h . ':00',
                    //'timestamp' => $timestamp,
                    'disponible' => in_array($timestamp, $creneauxDispos),
                ];
            }

            $calendrier[] = [
                'date' => $begin->format('Y-m-d'),
                'creneaux' => $creneaux,
            ];

            $begin->add(new \DateInterval('P1D'));
        }

        return $calendrier;
    }

    public function demandeDevis(string $uid, array $projet, array $contact)
    {
        $information = [
            'firstname' => $contact['prenom'] ?? null,
            'lastname' => $contact['nom'],
            'email_from' => $contact['email'],
            'phone' => $contact['tel'],
            'zip' => $contact['code_postal'],
            'country_id' => 'France',
        ];

        $project = [
            [
                'name' => $projet['type'],
                'room' => "",
                'height' => $projet['hauteur'],
                'width' => $projet['largeur'],
                'handle' => $projet['poignee'] .' '. $projet['couleurPoignee'],
                'quantity' => 1,
            ],
            [
                'options' => [
                    'wood' => $projet['bois'],
                    'alu' => $projet['couleurExt'],
                    'glazing' => "",
                    'shutter' => "",
                    'details' => $contact['message'] ?? "",
                    'project_uuid' => $uid,
                ],
            ],
        ];

        $args = [
            'id' => $uid,
            'type' => "opportunity",
            'team_id' => "quotation",
            'project' => $project,
            ...$information,
        ];

        $params = [
            'model' => 'eben.lead',
            'method' => 'create_or_update',
            'args' => [$args],
            'kwargs' => ['context' => []],
        ];

        $res = $this->request('dataset', $params);
        return $res['result'];
    }

    public function reserverCreneau(string $uid, array $creneau): bool
    {
        $params = [
            'model' => 'eben.appointment',
            'method' => 'save',
            'args' => [[
                'opportunity_id/id' => $uid,
                'start' => $creneau['datetime'],
                'employee_id' => $creneau['employee_id'],
            ]],
            'kwargs' => ['context' => []],
        ];
        $res = $this->request('dataset', $params);
        return $res['result'] ?? throw new \Exception();
    }

}
