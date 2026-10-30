# PDF výstupy aplikace

Kompletní seznam tiskových (PDF) výstupů. Všechny se renderují přes
[`App\Model\Services\PdfRenderer`](../app/Model/Services/PdfRenderer.php) → Gotenberg (headless Chromium).

Odkazy jsou **relativní routy** s zástupnými parametry (`<id>`, `<aid>`, `<cashbookId>`, `<method>`) –
konkrétní hodnoty závisí na přihlášeném uživateli a datech.

Každý výstup jde **stáhnout** i **otevřít v prohlížeči**. Bez parametru se PDF vrací k zobrazení
(`Content-Disposition: inline`); s parametrem `download=1` jako příloha ke stažení (`attachment`). Odpověď
sestavuje `PdfRenderer::createResponse()` → [`App\Http\PdfResponse`](../app/Http/PdfResponse.php).

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

- **Faktura**, **cestovní smlouvy** a **cestovní příkaz** jsou plnohodnotné HTML dokumenty s vlastním `<!DOCTYPE html>` a `<head>`;
  `PdfRenderer` do nich normalizační `<style>` vkládá **do `<head>`** (zachová standards mode).
- **Reporty**, **seznamy účastníků** a **doklady** jsou HTML fragmenty; normalizace se prependuje. Fragment Chromium
  vykreslí v quirks mode – tabulky v něm nepřebírají velikost písma z `body`, proto ji nová šablona nemá nastavovat
  jen na `body` (nebo má být plnohodnotným dokumentem).
- Chromium je na CSS přísnější než mpdf: `font: 16px bold` vyloží doslova („bold“ bere jako název písma), neplatné
  `padding: 0 auto` zahodí a plovoucí bloky (`float`) nezvětšují výšku rodiče. Rozvržení PDF šablon proto stavět
  na flexboxu, ne na `float`.
- Zlomy stránek se dělají v CSS: `break-before: page` / `break-after: page`, a `break-inside: avoid` pro blok, který se
  nesmí rozdělit. Značky mpdf jako `<pagebreak>` Chromium ignoruje a obsah pak teče za sebou. V dokladech má hromadný
  příjmový a každý výdajový doklad vlastní stránku a příjmové doklady jdou za sebou, aniž by se některý rozdělil;
  části „Všech dokladů akce“ odděluje `ExportService::getNewPage()`.
- Uživatelská data v obrázcích (loga a razítka faktur) se vkládají **inline jako base64** – nikdy nejdou přes veřejnou URL.
- Horní kontejnery mají vynucené `max-width:100%` + `box-sizing:border-box`, aby se přeširoké šablony (fixní `800px`)
  vešly na tiskovou plochu A4 (emulace mpdf `shrink_tables_to_fit`).
- Produkce může používat externí Gotenberg přes `GOTENBERG_URL`. Pokud endpoint chrání HTTP Basic autentizace,
  nastavují se společně `GOTENBERG_USERNAME` a `GOTENBERG_PASSWORD`; heslo patří jen do tajemství nasazení.

## Ovládání stažení v aplikaci

Všechna místa se soubory používají sdílenou šablonu
[`app/Components/templates/FileActionButton.latte`](../app/Components/templates/FileActionButton.latte):

- **PDF** – dělené tlačítko: hlavní část soubor stáhne (`download=1`), šipka nabídne „Otevřít v nové kartě“
  (`target="_blank"`, `rel="noopener noreferrer"`).
- **Excel, ZIP, obrázky a ostatní formáty** – jen „Stáhnout …“, bez možnosti otevření.
- Komponenta umí odkazy i odesílací tlačítka formulářů (hromadný tisk a export vybraných dokladů, export souhrnu
  akcí a táborů).

Nahrané skeny (doklady, technické průkazy) vrací [`App\Http\FileStreamResponse`](../app/Http/FileStreamResponse.php).
Typ se určuje podle přípony (`FilePath::getContentType()`); obsah se proti povoleným typům ověřuje už při nahrání.
Otevřít v prohlížeči (`?open=1`, `inline`) jde jen PDF – ostatní soubory se vždy vrací jako příloha.

Testy: hlavičky [`tests/unit/Http`](../tests/unit/Http), vykreslení komponenty
[`tests/unit/Components/FileActionButtonTest.php`](../tests/unit/Components/FileActionButtonTest.php), průchody
v akceptačních testech cestovního příkazu, smlouvy, faktury, evidence plateb a seznamu účastníků.
