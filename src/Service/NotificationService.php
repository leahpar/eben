<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;

class NotificationService
{

    public function __construct(
        #[Autowire('%env(MAILER_SENDER)%')]
        private readonly string $sender,
        private readonly MailerInterface $mailer,
        private readonly TexterInterface $texter,
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


    public function sms(string $message, string $tel): void
    {
        // Formattage tel
        $tel = preg_replace('#[^0-9\+]#', '', $tel);
        $tel = preg_replace('#^0#', '+33', $tel);

        $sms = new SmsMessage($tel, $message);
//        $res = $this->texter->send($sms);
        //dump($res);
    }

    public function generateCode(array $projet, array $contact): string
    {
        $str = array_reduce(
            [...$projet, ...$contact],
            fn ($str, $item) => $str . $item,
        );
        $md5 = md5($str);
        $md5 = preg_replace("/[^0-9]/", "", $md5);
        return substr($md5.$md5, rand(0, 10), 6);
    }

}
