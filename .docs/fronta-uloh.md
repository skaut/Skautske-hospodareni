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

Upomínka poslaná ručně a automatická upomínka jsou pro frontu stejný e-mail. Ve frontě tak může čekat nebo běžet jen jedna z nich. Když čeká ruční upomínka, automatická se ten den nezařadí, a když ruční upomínka odejde, automatická už se nepošle. Do fronty se dostane jen e-mail, který může odejít. Když skupina nemá odesílatele nebo má šablonu e-mailu vypnutou, nezařadí se nic. Nezařadí se ani e-mail platby bez e-mailové adresy, uzavřené platby a upomínka platby, která ji teď dostat nemůže (není po splatnosti nebo už dnes upomínku dostala). Uživatel se hned dozví, u kolika plateb a proč.

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
- Úloha, kterou převzal běh, jenž zemřel (timeout PHP, limit 30 s), se po 10 minutách vrátí do fronty. Nevratná část ještě nezačala, takže se nic neopakuje.
- Úloha ve stavu „běží“ se po 10 minutách označí jako nejistá a **automaticky se už nikdy nespustí**. Gmail máme jen s oprávněním odesílat, odeslanou poštu ověřit nejde. Uživatel to uvidí v přehledu a rozhodne sám.
- Když Gmail odmítne e-mail kvůli limitu odesílání, úloha se odloží o hodinu, nejvýš na 3 pokusy.
- Když nejde poslat nic z dávky (např. neplatný Google účet odesílatele, chybí šablona), zbytek dávky se hned označí jako neproběhlý se stejným důvodem.
- Stejná úloha (stejný e-mail stejné platby) nemůže čekat ve frontě dvakrát. Hlídá to unikátní klíč, který se po dokončení úlohy uvolní.

## Časový rozpočet

Fronta je v cronu poslední a nové úlohy bere jen do 20 sekund od začátku požadavku. Když čas spotřebují úlohy před ní (např. párování), počká na další minutu. Google klient má timeout 8 s, takže i poslední odeslání doběhne do 30 sekund. Úlohy se berou střídavě z jednotlivých dávek, malá dávka jedné skupiny tak nečeká, až doběhne velká dávka jiné.

## Co vidí uživatel

Na detailu platební skupiny je panel „Odesílání e-mailů na pozadí“. Vidí ho každý, kdo smí skupinu otevřít, bez ohledu na to, kdo dávku zadal a pod jakou rolí je přihlášen. Každá skupina má vlastní dávky a počty. Panel ukazuje dávky, které se zpracovávají, a dokončené dávky s problémem, dokud je někdo s právem úprav neskryje. Stránka se sama neobnovuje, každých 15 sekund se obnoví jen panel, a to jen když se něco zpracovává a záložka je vidět.

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
