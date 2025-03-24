<?php

namespace App\Command;

use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When('dev')]
#[AsCommand(name: 'app:test', description: 'Test command')]
class TestCommand extends Command
{

    public function __construct(
        private readonly NotificationService $notificationService,
    ){
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->notificationService->sms("pwet?", "06 50 76 83 67");

        $io->success("OK");
        return Command::SUCCESS;
    }


}
