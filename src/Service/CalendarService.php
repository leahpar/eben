<?php

namespace App\Service;

use Spatie\IcalendarGenerator\Components as ICS;

class CalendarService
{
    public function __construct(
        private readonly SheetTarifLoader $sheetTarifLoader,
    ) {}

    public function getIcalUrl(array $data): string
    {
        $date = new \DateTime($data['datetime']);
        $ics = ICS\Calendar::create()
            ->event(ICS\Event::create($this->sheetTarifLoader->getMessage('rdv_titre'))
                ->startsAt($date)
                ->endsAt((clone $date)->modify('+1 hour'))
                ->description($this->sheetTarifLoader->getMessage('rdv_texte'))
            )
            ->get();

        return "data:text/calendar;charset=utf8;base64,"
            . base64_encode($ics);
    }

    public function getGoogleUrl(array $data): string
    {
        $date = new \DateTime($data['datetime']);
        return "https://www.google.com/calendar/render?action=TEMPLATE"
            . "&text=" . urlencode($this->sheetTarifLoader->getMessage('rdv_titre'))
            . "&dates=" . $date->format('Ymd\THis') . "/" . (clone $date)->modify('+1 hour')->format('Ymd\THis')
            . "&ctz=Europe/Paris"
            . "&details=" . urlencode($this->sheetTarifLoader->getMessage('rdv_texte'));
    }


}
