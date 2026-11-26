<?php

declare(strict_types=1);

namespace App\Model\Usage;

/**
 * Names of the screens (presenters) the usage statistics show as subsections of a part of the system.
 * Each name is unique within its section, the overview would otherwise show two screens as one.
 */
final class UsageScreen
{
    private const LABELS = [
        'Payments:Dashboard' => 'Přehled plateb',
        'Payments:GroupList' => 'Seznam skupin',
        'Payments:Group' => 'Založení a úprava skupiny',
        'Payments:Payment' => 'Detail skupiny',
        'Payments:Repayment' => 'Vratky',
        'Payments:Participants' => 'Účastníci skupiny',
        'Payments:People' => 'Osoby skupiny',
        'Payments:Journal' => 'Časopisy',
        'Payments:RegistrationJournal' => 'Časopisy registrace',
        'Payments:CampSelectForGroup' => 'Výběr tábora pro skupinu',
        'Payments:CampCreateGroup' => 'Založení skupiny z tábora',
        'Payments:CampAddParticipants' => 'Přidání účastníků tábora',
        'Payments:EventSelectForGroup' => 'Výběr akce pro skupinu',
        'Payments:EventCreateGroup' => 'Založení skupiny z akce',
        'Payments:EventAddParticipants' => 'Přidání účastníků akce',
        'Payments:EducationSelectForGroup' => 'Výběr vzdělávací akce pro skupinu',
        'Payments:EducationCreateGroup' => 'Založení skupiny ze vzdělávací akce',
        'Payments:EducationAddParticipants' => 'Přidání účastníků vzdělávací akce',
        'Payments:RegistrationCreateGroup' => 'Založení skupiny z registrace',
        'Payments:RegistrationAddMembers' => 'Přidání členů z registrace',
        'Payments:InvoiceList' => 'Faktury',
        'Payments:InvoiceSequenceList' => 'Seznam fakturačních řad',
        'Payments:InvoiceSequence' => 'Detail fakturační řady',
        'Settings:Invoices' => 'Nastavení fakturace',
        'Unit:Cashbook' => 'Pokladní kniha',
        'Unit:Chit' => 'Přehled paragonů',
        'Unit:Budget' => 'Rozpočet',
        'Unit:CashbookExport' => 'Export',
        'Events:Default' => 'Seznam akcí',
        'Events:NewEvent' => 'Nová akce',
        'Events:Event' => 'Detail akce',
        'Events:Cashbook' => 'Pokladní kniha akce',
        'Events:Participant' => 'Účastníci',
        'Camps:Default' => 'Seznam táborů',
        'Camps:Detail' => 'Detail tábora',
        'Camps:Cashbook' => 'Pokladní kniha tábora',
        'Camps:Participant' => 'Účastníci',
        'Camps:Budget' => 'Rozpočet',
        'Education:Default' => 'Seznam vzdělávacích akcí',
        'Education:Education' => 'Detail vzdělávací akce',
        'Education:Cashbook' => 'Pokladní kniha vzdělávací akce',
        'Education:Participant' => 'Účastníci',
        'Education:Budget' => 'Rozpočet',
        'Travel:Default' => 'Seznam cestovních příkazů',
        'Travel:Command' => 'Cestovní příkaz',
        'Travel:VehicleList' => 'Seznam vozidel',
        'Travel:Vehicle' => 'Detail vozidla',
        'Travel:Contract' => 'Smlouvy',
        'Settings:Default' => 'Přehled nastavení',
        'Settings:User' => 'Uživatel',
        'Settings:BankAccounts' => 'Bankovní účty',
        'Settings:Mails' => 'E-maily',
        'Settings:Google' => 'Propojení s Googlem',
        'Settings:Automation' => 'Automatizace',
        'Dashboard' => 'Nástěnka',
        'Announcements' => 'Oznámení',
        'BugReport' => 'Nahlášení problému',
        'Tutorial' => 'Příručka',
    ];

    /** @param string $presenterName full name, e.g. Payments:GroupList */
    public static function label(string $presenterName): string
    {
        return self::LABELS[$presenterName] ?? $presenterName;
    }
}
