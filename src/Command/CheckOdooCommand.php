<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\OdooRpcService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:odoo:check', description: 'Check Odoo')]
class CheckOdooCommand extends Command
{
    public function __construct(
        private readonly OdooRpcService $odooRpcService,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$this->odooRpcService->request('db_list', []);

//        $res = $this->odooRpcService->request('dataset', [
//            'model' => 'eben.lead',
//            'method' => 'fields_get',
//            'args' => [[]],
//            'kwargs' => ['attributes' => []],
//        ]);
//        dump($res);

//        $res = $this->odooRpcService->getTimeSlots();
//        dump($res);

//        dump($this->odooRpcService->buildDefaultCalendar());
        dump($this->odooRpcService->getCalendrier());

        return Command::SUCCESS;
    }
}
