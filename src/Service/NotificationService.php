<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NotificationService
{

    public function __construct(
        #[Autowire('%env(MAILER_SENDER)%')]
        private readonly string $sender,
        private readonly MailerInterface $mailer,
    ) {}

    public function email(array $projet, float $montant, array $contact)
    {
        $nomComplet = strtoupper($contact['nom']) . ' ' . ucfirst($contact['prenom']);
        $email = (new TemplatedEmail())
            ->from(Address::create($this->sender))
            ->to(new Address($contact['email'], $nomComplet))
            ->replyTo($this->sender)
            ->subject("Votre devis Eben Fenêtres")
            ->htmlTemplate('emailClient.html.twig')
            ->context([
                'projet' => $projet,
                'montant' => $montant,
                'contact' => $contact,
                'nomComplet' => $nomComplet,
            ]);

        $this->mailer->send($email);

        $email = (new TemplatedEmail())
            ->from(Address::create($this->sender))
            ->to($this->sender)
            ->subject("Nouvelle demande sur le configurateur Eben")
            ->htmlTemplate('emailEben.html.twig')
            ->context([
                'projet' => $projet,
                'montant' => $montant,
                'contact' => $contact,
                'nomComplet' => $nomComplet,
            ]);

        $this->mailer->send($email);
    }

}
