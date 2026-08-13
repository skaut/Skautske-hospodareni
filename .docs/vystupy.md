# PDF výstupy aplikace

Kompletní seznam tiskových (PDF) výstupů. Všechny se renderují přes
[`App\Model\Services\PdfRenderer`](../app/Model/Services/PdfRenderer.php) → Gotenberg (headless Chromium).

Odkazy jsou **relativní routy** s zástupnými parametry (`<id>`, `<aid>`, `<cashbookId>`, `<method>`) –
konkrétní hodnoty závisí na přihlášeném uživateli a datech.

Filtry v šablonách hlídá test [`tests/unit/Export/PdfTemplateFiltersTest.php`](../tests/unit/Export/PdfTemplateFiltersTest.php),
routy [`tests/unit/App/RouterFactoryTest.php`](../tests/unit/App/RouterFactoryTest.php).

## Reporty

| Výstup | Relativní odkaz | Presenter::akce | Zdroj HTML | Šablona | Latte filtry |
|---|---|---|---|---|---|
| Report akce | `/akce/<aid>/report` | `Events:Event:report` | `ExportService::getEventReport()` | `app/Model/Export/templates/eventReport.latte` | date, price, stripHtml |
| Report tábora | `/tabory/<aid>/report` | `Camps:Detail:report` | `ExportService::getCampReport()` | `app/Model/Export/templates/campReport.latte` | price |
| Report vzdělávací akce | `/vzdelavacky/<aid>/report` | `Education:Education:report` | `ExportService::getEducationReport()` | `app/Model/Export/templates/educationReport.latte` | date, number, price |

## Seznamy účastníků

| Výstup | Relativní odkaz | Presenter::akce | Zdroj HTML | Šablona | Latte filtry |
|---|---|---|---|---|---|
| Seznam účastníků akce | `/akce/<aid>/ucastnici/export` | `Events:Participant:export` | `ExportService::getParticipants(GENERAL)` | `app/Model/Export/templates/participant.latte` | postCode, price |
| Seznam účastníků tábora | `/tabory/<aid>/ucastnici/export` | `Camps:Participant:export` | `ExportService::getParticipants(CAMP)` | `app/Model/Export/templates/participantCamp.latte` | postCode, price |
| Seznam účastníků vzdělávací akce | `/vzdelavacky/<aid>/ucastnici/export` | `Education:Participant:export` | `ExportService::getParticipants(EDUCATION)` | `app/Model/Export/templates/participantEducation.latte` | postCode, price |

## Pokladna a doklady

| Výstup | Relativní odkaz | Presenter::akce | Zdroj HTML | Šablona | Latte filtry |
|---|---|---|---|---|---|
| Všechny doklady akce | `/akce/<aid>/print-all` | `Events:Event:printAll` | `ExportChits::all()` | `.../Pdf/templates/chits.latte` (+ `chits.in`, `chits.out`, `chits.hpd`) | date, noescape, price, priceToString |
| Tisk vybraných dokladů | `/export/print-chits/<cashbookId>?chitIds[]=<id>` | `Unit:CashbookExport:printChits` | `ExportChits::withChitIds()` | `.../Pdf/templates/chits.latte` | date, noescape, price, priceToString |
| Seznam dokladů | `/export/print-all-chits/<cashbookId>` | `Unit:CashbookExport:printAllChits` | `ExportService::getChitlist()` | `app/Model/Export/templates/chitlist.latte` | price |
| Pokladní kniha | `/export/print-cashbook/<cashbookId>?paymentMethod=<method>` | `Unit:CashbookExport:printCashbook` | `ExportService::getCashbook()` | `app/Model/Export/templates/cashbook.latte` | date, noescape, price |

`chits.latte` podle typu dokladu vkládá `chits.in.latte` (příjmový), `chits.out.latte` (výdajový)
nebo `chits.hpd.latte` (hospodářský doklad).

## Cestovní náhrady

| Výstup | Relativní odkaz | Presenter::akce | Zdroj HTML | Šablona | Latte filtry |
|---|---|---|---|---|---|
| Cestovní příkaz | `/cestaky/prikazy/<id>/print` | `Travel:Command:print` | presenter (Latte přes `AccountancyLatteExtension`) | `app/Presentation/Travel/Command/ex.command.latte` | price |
| Smlouva o proplácení náhrad | `/cestaky/smlouvy/print/<id>` | `Travel:Contract:print` | presenter | `ex.contract.old.latte` / `ex.contract.noz.latte` (dle data účinnosti NOZ) | date |

## Faktury

| Výstup | Relativní odkaz | Kde se generuje | Zdroj HTML | Šablona | Latte filtry |
|---|---|---|---|---|---|
| Faktura – stažení PDF | `/platby/faktury/<id>` (signál `downloadPdf`) | `Payments:InvoiceList::handleDownloadPdf()` | `ExportService::getInvoice()` | `app/Model/Export/templates/invoice.latte` | number (+ `nl2br()` jako funkce) |
| Faktura – e-mailová příloha | odesílá se e-mailem při odeslání faktury | `InvoiceMailingService::sendEmail()` | `ExportService::getInvoice()` | `app/Model/Export/templates/invoice.latte` | number |

## Poznámky k renderování

- **Faktura** a **cestovní smlouvy** jsou plnohodnotné HTML dokumenty s vlastním `<!DOCTYPE html>` a `<head>`;
  `PdfRenderer` do nich normalizační `<style>` vkládá **do `<head>`** (zachová standards mode).
- **Reporty**, **seznamy účastníků**, **doklady** a **cestovní příkaz** jsou HTML fragmenty; normalizace se prependuje.
- Uživatelská data v obrázcích (loga a razítka faktur) se vkládají **inline jako base64** – nikdy nejdou přes veřejnou URL.
- Horní kontejnery mají vynucené `max-width:100%` + `box-sizing:border-box`, aby se přeširoké šablony (fixní `800px`)
  vešly na tiskovou plochu A4 (emulace mpdf `shrink_tables_to_fit`).
