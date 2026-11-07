# Pravidelné úlohy (cron)

Hosting aplikace neumí spouštět cron ani příkazy v konzoli. Pravidelné úlohy proto spouští adresa `/cron.php`, kterou volá externí server. Aplikace sama pozná, které úlohy jsou právě na řadě, a spustí jen ty.

Jeden požadavek smí na hostingu běžet nejvýš 30 sekund. Dlouhou práci (např. rozeslání stovek e-mailů) proto úlohy jen zařadí do fronty v databázi a fronta ji zpracovává po částech každou minutu. Podrobnosti jsou ve [fronta-uloh.md](fronta-uloh.md).

## Které úlohy běží

| Úloha | Kdy | Co dělá |
| --- | --- | --- |
| Párování plateb | každé 2 hodiny, vždy v celou sudou hodinu (0:00, 2:00, …) | Páruje transakce z účtů napojených na FIO s platbami v otevřených skupinách, které mají zapnutou volbu „Automaticky párovat úhrady v cronu“. |
| Automatické upomínky plateb | denně v 6:00; **po nasazení je pozastavená**, zapíná ji administrátor v Admin → Cron | Zařadí do fronty upomínky nezaplacených plateb ve skupinách, které mají automatické upomínky zapnuté a nastavený počet dní po splatnosti. Každá platba dostane automatickou upomínku jen jednou, a jen když jí ještě žádná upomínka neodešla, ani ručně. |
| Fronta úloh | každou minutu, jako poslední úloha | Zpracuje úlohy z fronty, dnes hlavně e-maily plateb. Nové úlohy bere jen do 20 s od začátku požadavku. |

Podrobnosti párování:

- Výpis se stahuje za každý FIO účet jen jednou za běh, i když na něm je víc skupin. Stahuje se od data posledního párování do dneška, takže každý běh znovu načte celý dnešní den i s platbami, které přibyly od minulého běhu.
- Účty s importem výpisů (GPC) cron vynechává. Výpis se po importu nemění, a tak by další průchod nic nového nenašel. Ve formuláři skupiny s účtem GPC proto automatické párování zapnout nejde. Platby se párují ručně, nebo hned po importu, když v dialogu importu zaškrtnete „Po importu spárovat platby“. Párují se pak všechny otevřené skupiny s tímto účtem podle transakcí od prvního dne nahraného výpisu.
- Skupiny, kterým už nezbývá žádná nezaplacená platba, se přeskočí. Když nezbývá nic na celém účtu, banka se vůbec nevolá.
- Když se nepodaří načíst transakce jednoho účtu (např. kvůli neplatnému tokenu FIO nebo výpadku banky), ostatní účty se spárují normálně a chyba se zapíše do logu.
- E-mail „platba přijata“ se po spárování zařadí do fronty a odejde s ní. Za odesílatele se do něj doplní „AUTOMAT“.

Podrobnosti automatických upomínek:

- Nastavují se ve formuláři skupiny u „E-mail upomínka platby“: šablona musí být aktivní, zapnutá volba „Automaticky odeslat upomínku po splatnosti“ a vyplněný „Počet dní po splatnosti“. Skupina musí mít e-mail odesílatele a být otevřená.
- Upomínka odejde v den, kdy od splatnosti uplyne nastavený počet dní. Platby, které jsou po splatnosti už déle, ji dostanou při nejbližším běhu.
- Když upomínka prokazatelně neodejde (např. platba nemá e-mail, Google účet odesílatele je potřeba znovu propojit), zkusí se to při dalším běhu znovu.
- Skupiny se starou volbou bez vyplněného počtu dní upomínky automaticky neposílají, dokud počet dní někdo nevyplní.

Úloha se přidává do `app/config/scheduler.neon` (co se má spustit a výchozí interval). Název a popis pro lidi jí doplňte v `CronJobRegistry`. Uživatelům úlohy ukazuje stránka Nastavení → Automatizace.

## Správa v administraci

Administrátor vidí úlohy v Admin → Cron (`/admin/cron`):

- kdy úloha poběží příště a kdy a s jakým výsledkem proběhla naposledy, včetně textu chyby,
- využití: kolik otevřených skupin automatické párování a automatické upomínky používají a kolik úloh čeká ve frontě (uzavřené skupiny se nepočítají),
- upozornění, když plánované spuštění neproběhlo, typicky proto, že externí server přestal volat `/cron.php`,
- **Pozastavit / Obnovit**: pozastavená úloha se nespouští, dokud ji někdo neobnoví,
- **Změnit interval**: výběr z pevných intervalů (od 15 minut po jednou denně). Volba výchozího intervalu vrátí úlohu k nastavení ze `scheduler.neon`.

Změny se ukládají do tabulky `cron_job` a projeví se při nejbližším volání cronu, bez nasazení.

Stav fronty a statistika jejích posledních běhů je v Admin → Fronta úloh (`/admin/fronta`).

## Nastavení

1. Vygenerujte token a jeho otisk (hash):

   ```bash
   TOKEN=$(openssl rand -hex 32)
   printf %s "$TOKEN" | sha256sum
   ```

2. Otisk (64 znaků bez pomlčky na konci) uložte v GitHubu jako tajemství `CRON_TOKEN_SHA256` pro každé prostředí, kde má cron běžet (`beta`, `test`, `production`). Při nasazení se zapíše do `.env.local` na serveru. Na server se tak dostane jen otisk, samotný token zná jen externí server.
3. Nasaďte aplikaci.
4. Na externím serveru nastavte volání **každou minutu** (stačí i každých 5 minut). Úloha se spustí jen tehdy, když volání přijde v minutě, kdy je na řadě. Když jedno volání vypadne, párování proběhne až v dalším termínu, ale nic se neztratí: další běh naváže tam, kde skončil ten předchozí.

```bash
curl --fail --silent --show-error --max-time 300 \
    -X POST -H "Authorization: Bearer $TOKEN" \
    https://h.skauting.cz/cron.php
```

Pokud hosting hlavičku `Authorization` nepředá a cron vrací 403, pošlete token v těle požadavku:

```bash
curl --fail --silent --show-error --max-time 300 \
    --data-urlencode "token=$TOKEN" \
    https://h.skauting.cz/cron.php
```

Token nikdy nedávejte do adresy (`?token=…`), protože by zůstal v logu přístupů.

Externí server nemusí čekat na dokončení, úlohy doběhnou i po ukončení spojení.

## Odpovědi

| Stav | Význam |
| --- | --- |
| 200 | Proběhlo. Tělo vypíše spuštěné úlohy (`paymentPairing: ok`), nebo `No job is due.`, když žádná nebyla na řadě. |
| 500 | Některá úloha selhala (`paymentPairing: failed`). Podrobnosti jsou v logu aplikace, a pokud je nastavené, i v Sentry. |
| 403 | Chybí nebo nesedí token, případně na serveru není nastavený `CRON_TOKEN_SHA256`. |
| 405 | Požadavek nebyl `POST`. |
| 503 | Aplikace je v režimu údržby, úlohy se nespouští. |

`already running` znamená, že předchozí běh stejné úlohy ještě neskončil, a tak se tentokrát přeskočila.

## Vyzkoušení lokálně

Do `.env.local` doplňte `CRON_TOKEN_SHA256` podle kroku 1 a zavolejte `http://moje-hospodareni.cz/cron.php` stejně jako výše. Úloha, která v tu minutu není na řadě, se nespustí. Pro okamžité spuštění použijte `make run CMD='bin/console scheduler:force-run paymentPairing'`.
