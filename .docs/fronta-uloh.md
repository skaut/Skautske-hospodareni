# Fronta úloh

Hosting neumí pustit proces na pozadí ani příkaz v konzoli s přístupem do databáze. Jeden webový požadavek smí běžet nejvýš 30 sekund. Práce, která by trvala déle, například rozeslání stovek e-mailů, se proto ukládá do fronty v databázi. Frontu zpracovává cron: externí server volá `/cron.php` každou minutu (viz [cron.md](cron.md)).

## Kdy se pošta posílá přes frontu

Platí jedno pravidlo: **každý e-mail plateb odchází přes frontu**, i jeden jediný. Odejde do minuty, až ho vezme cron. Mimo frontu jde jen testovací e-mail na vlastní adresu.

| Odkud | E-mail |
| --- | --- |
| Obálka nebo zvonek u jedné platby | platební údaje, upomínka |
| Menu E-mail → Rozeslat e-maily, hromadná akce Odeslat email | platební údaje |
| Menu E-mail → Rozeslat upomínky, hromadná akce Odeslat upomínku | upomínka |
| Dokončení platby (Zaplaceno, párování s bankou, import výpisu) | potvrzení o úhradě |
| Automatické upomínky (cron) | upomínka |

Upomínka poslaná ručně a automatická upomínka jsou pro frontu stejný e-mail. Ve frontě tak může čekat nebo běžet jen jedna z nich. Když čeká ruční upomínka, automatická se ten den nezařadí, a když ruční upomínka odejde, automatická už se nepošle. Do fronty se dostane jen e-mail, který může odejít. Když skupina nemá odesílatele, Google její odesílatele odmítá (viz [Když Google odmítne e-mail odesílatele](#když-google-odmítne-e-mail-odesílatele)) nebo má šablonu e-mailu vypnutou, nezařadí se nic. Nezařadí se ani e-mail platby bez e-mailové adresy, uzavřené platby a upomínka platby, která ji teď dostat nemůže (není po splatnosti nebo už dnes upomínku dostala). Uživatel se hned dozví, u kolika plateb a proč.

Co se nemění: typy e-mailů, šablony, záznam o odeslání u platby (počet obálek v seznamu) a jméno odesílatele. Do záznamu i do šablony se dosadí jméno toho, kdo e-maily zadal. U automatických úloh je to „AUTOMAT“, jako dřív. Údaje platby (částka, VS, příjemci) se berou až v okamžiku odeslání. Když se platba mezitím zaplatí nebo zruší, upomínka se vynechá.

Potvrzení o úhradě se zařazuje ve stejné databázové transakci, v jaké se platba dokončuje. Když se dokončení nepovede, potvrzení se nezařadí. Potvrzení jedné skupiny z jednoho požadavku (např. z párování) tvoří jednu dávku.

## Jak fronta zaručuje, že nic neodejde dvakrát

Každá úloha (např. jeden e-mail) prochází stavy:

| Stav | Význam |
| --- | --- |
| čeká (`pending`) | Je ve frontě. |
| převzato (`claimed`) | Vzal si ji jeden běh. Ještě se nic nestalo. |
| běží (`running`) | Začala nevratná část, u e-mailu předání Gmailu. |
| hotovo (`done`) | Proběhla. U e-mailu je zapsaný záznam o odeslání. |
| vynecháno (`skipped`) | Už není potřeba, např. platba je mezitím zaplacená. |
| neproběhlo (`failed`) | Nemohla proběhnout a prokazatelně nic nezpůsobila, např. chybí e-mail příjemce nebo Google účet odesílatele je potřeba znovu propojit. |
| nejisté (`uncertain`) | Běh skončil uprostřed nevratné části. Nevíme, jestli e-mail odešel. |

- Úlohu převezme jen ten běh, kterému se podaří podmíněně přepsat její stav. Souběžné běhy (překryv cronu, víc serverů) si ji nemohou vzít oba.
- Úloha, kterou převzal běh, jenž zemřel (timeout PHP, limit 30 s), se po 10 minutách vrátí do fronty. Nevratná část ještě nezačala, takže se nic neopakuje. Když běh zemře na stejné úloze potřetí, úloha se označí jako neproběhlá, aby nezabíjela další běhy. Počítají se jen běhy, které zemřely. Odložení úlohy (např. kvůli limitu Gmailu) se do toho nepočítá.
- Úloha ve stavu „běží“ se po 10 minutách označí jako nejistá a **automaticky se už nikdy nespustí**. Gmail máme jen s oprávněním odesílat, odeslanou poštu ověřit nejde. Uživatel to uvidí v přehledu a rozhodne sám.
- Když Gmail odmítne e-mail kvůli limitu odesílání, odloží se o hodinu celá dávka, protože by Gmail odmítl i další e-maily téhož odesílatele. Pokus se počítá jen odmítnutému e-mailu. Denní limit Gmailu trvá až den, proto se e-mail zkouší až 26krát (déle než den). Panel mezitím ukazuje, do kdy je odesílání odložené, a dávku nehlásí jako zastavenou.
- Když Google před odesláním neodpoví (výpadek spojení, timeout, chyba na jeho straně), nic neodešlo, a tak se celá dávka odloží o 10 minut, nejvýš 6krát.
- Když nejde poslat nic z dávky (např. neplatný Google účet odesílatele, chybí šablona), zbytek dávky se hned označí jako neproběhlý se stejným důvodem.
- Stejná úloha (stejný e-mail stejné platby) nemůže čekat ve frontě dvakrát. Hlídá to unikátní klíč, který se po dokončení úlohy uvolní.

## Když Google odmítne e-mail odesílatele

Google může přestat přijímat propojení účtu odesílatele (odpoví `invalid_grant`). Stane se to například tehdy, když majitel aplikaci odebral ve svém Google účtu nebo si změnil heslo. Přesný důvod Google neříká. Aplikace si to zapamatuje u účtu: čas v `google_oauth.invalid_since`, odpověď Googlu pro správce v `invalid_reason`. Do znovupropojení pak platí:

- Ruční e-maily skupin s tímto odesílatelem se nezařadí a uživatel hned dostane chybovou hlášku.
- Automatické upomínky těchto skupin se vynechají. Zařadí se první den po znovupropojení.
- Potvrzení o úhradě se zařadí, ale čekají. Po znovupropojení odejdou hned. Když skupina mezitím dostane jiného odesílatele, odejdou do hodiny.
- E-maily, které už ve frontě byly, skončí jako neproběhlé s návodem, jak účet propojit.

Uživatelé o tom vědí z těchto míst:

- Nástěnka, v oznámeních. Upozornění je vždy první, nezabírá místo oznámením a vidí ho jen ten, kdo vidí otevřenou skupinu s tímto odesílatelem.
- Detail skupiny.
- Nastavení → E-maily.

Kdo má aktivní roli v jednotce účtu a smí ji upravovat, dostane tlačítko „Propojit účet znovu“. To rovnou otevře přihlášení k Googlu s nabídnutým účtem. Ostatní se dozví, že to musí udělat někdo s oprávněním upravovat tu jednotku.

Znovupropojení stejného účtu ve stejné jednotce příznak zruší a skupiny si odesílatele ponechají. Odebrat účet nepomůže: odebrání zruší odesílatele ve všech skupinách, které ho používají. Příznak zruší i úspěšné odeslání z účtu, například testovací e-mail, pokud byla chyba na straně Googlu.

## Časový rozpočet

Fronta je v cronu poslední a nové úlohy bere jen do 20 sekund od začátku požadavku. Když čas spotřebují úlohy před ní (např. párování), počká na další minutu. Google klient má timeout 8 s. Před odesláním ale může ještě obnovit token odesílatele, což je další volání Googlu. Nevratnou část (předání e-mailu Gmailu) proto úloha začne jen do 21. sekundy, kdy ji ještě stihne dokončit do 30 sekund. Když už to nestihne, nic se nestane, úloha se bez započítání pokusu vrátí do fronty a běh skončí. Úlohy se berou střídavě z jednotlivých dávek, malá dávka jedné skupiny tak nečeká, až doběhne velká dávka jiné.

Interval „každou minutu“ jde v Admin → Cron nastavit jen frontě. Ostatní úlohy běží ve stejném požadavku před ní a při běhu každou minutu by jí mohly vzít všechen čas.

## Co vidí uživatel

Na detailu platební skupiny je panel „Odesílání e-mailů na pozadí“. Vidí ho každý, kdo smí skupinu otevřít, bez ohledu na to, kdo dávku zadal a pod jakou rolí je přihlášen. Každá skupina má vlastní dávky a počty. Panel ukazuje dávky, které se zpracovávají, a dokončené dávky s problémem, dokud je někdo s právem úprav neskryje. Kdo smí skupinu upravovat, může u běžící dávky zrušit e-maily, které ještě čekají (tlačítko „Zrušit čekající e-maily“). Co už odešlo nebo se právě odesílá, zůstane. Zrušená automatická upomínka se už znovu nezařadí, rozhodl o ní uživatel. Když uživatel uzavře skupinu nebo vypne automatické upomínky a ve frontě ještě čekají e-maily skupiny, dostane upozornění a rozhodne sám, jestli je nechá odejít, nebo je zruší. Sama od sebe je fronta neruší. Stránka se sama neobnovuje, každých 15 sekund se obnoví jen panel, a to jen když se něco zpracovává a záložka je vidět. Dávka, která jen čeká na odložený čas, panel neobnovuje. Obnova panelu se nepočítá jako aktivita uživatele a neprodlužuje přihlášení do skautISu. Panel se obnovuje ze samostatné adresy `/platby/skupiny/{id}/odesilani` (`Payments:TaskQueueStatus`). Ta ověří stejná práva ke skupině jako stránka skupiny ještě před jakýmkoli signálem panelu a nespouští nic jiného: žádnou akci stránky, zápis návštěvy, seznam plateb ani import z banky.

## Administrace

- **Admin → Fronta úloh** (`/admin/fronta`) ukazuje stav podle typu úloh (čeká, zpracovává se a výsledky za 24 hodin) a všechny dávky napříč skupinami, které se zpracovávají nebo skončily s problémem. Dál ukazuje statistiku posledních běhů. Ukládají se jen běhy, které něco zpracovaly; běh bez času konce přerušil hosting.
- **Admin → Cron** ukazuje u každé automatizace její využití: počet otevřených skupin s automatickým párováním a s automatickými upomínkami a počet čekajících úloh fronty. Uzavřené skupiny se nepočítají.

Dokončené dávky a záznamy běhů se mažou po 30 dnech.

## Nový typ úlohy

1. Napište obsluhu, která implementuje `App\Model\TaskQueue\TaskHandler`, a zaregistrujte ji jako službu. Fronta si ji najde sama.
2. V `handle()` zavolejte `beginIrreversible()` těsně před nevratnou akcí a výsledek uložte přes `complete()`. Úlohu bez efektu ukončete výjimkou `TaskSkipped`, `TaskFailed` nebo `TaskRetryLater`.
3. Úlohy zařazujte přes `App\Model\TaskQueue\TaskQueue::enqueue()` s rozsahem (`TaskScope`), podle kterého se přehled odděluje, a s klíčem proti duplicitě.

## Faktury: plánované převedení na frontu

Faktury zatím frontu nepoužívají. Dnes se vždy posílá jeden e-mail na kliknutí (Odeslat, Znovu odeslat, Upomínka) a PDF se vyrenderuje přes Gotenberg přímo v požadavku. Hromadné odesílání ani automatické upomínky faktur nejsou. Šablona „při dokončení platby“ se ve fakturační řadě dá vyplnit, ale nic ji neposílá.

Postup převedení:

- Nový typ úlohy `invoice_email`, rozsah fakturační řada (`invoice_sequence` + id), klíč proti duplicitě `invoice_email:<typ>:<id faktury>`.
- `InvoiceMailingService` rozdělit stejně jako u plateb na sestavení e-mailu a odeslání. Záznam o pokusu (`recordEmailAttempt`) zůstane. Nejisté odeslání se zapíše jako neúspěšný pokus s poznámkou.
- Render PDF má timeout 60 s, což se do rozpočtu fronty nevejde. Pro frontu timeout zkrátit (při překročení se úloha vrátí do fronty, nic neodešlo), nebo PDF vyrenderovat už při zařazení.
- Hromadné odeslání, automatické upomínky po splatnosti a potvrzení po spárování pak půjdou podle stejného pravidla jako platby: všechno přes frontu.
- Panel stavu (`TaskQueueStatus`) dát na seznam faktur dané řady.
