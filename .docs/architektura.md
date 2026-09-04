# Architektura systému

Aplikace odděluje změny dat od jejich zobrazování. Pravidla hospodaření jsou v `app/Model`, obrazovky v `app/Presentation` a znovupoužitelné části obrazovek v `app/Components`.

## Když se mění data

Změna začíná příkazem, který předá práci správné části aplikace. Ta načte potřebná data, použije pravidla hospodaření a výsledek uloží. Pravidla patří do modelu; presenter ani Latte šablona je nenahrazují.

- Rozhraní pro práci s uloženými daty patří k dané oblasti modelu. Jejich databázové provedení je v `app/Model/Infrastructure/Repositories`.
- SkautIS, e-mail a jiné vnější služby jsou oddělené za rozhraním, aby je šlo bezpečně nahradit při testování.
- Každá změna struktury databáze musí mít migraci v `migrations/<rok>`.

Samostatná pravidla ověřují malé testy bez databáze. Spolupráci s databází a vnějšími službami ověřují integrační testy.

## Když se data zobrazují

Načítání dat pro obrazovku nic nemění. Presenter vytvoří dotaz z `ReadModel/Queries`, předá ho aplikaci a dostane data připravená pro zobrazení.

Tím zůstává načítání dat oddělené od akcí, které něco mění. Kód zajišťující dotaz data neukládá ani nemění pravidla hospodaření.

## Uživatelské rozhraní

Nette presentery a Latte šablony jsou rozdělené podle částí aplikace v `app/Presentation`. Presenter připraví data a předá práci modelu; šablona výsledek jen zobrazí. Nové společné ovládací prvky patří do `app/Components`.

Adresy stránek jsou definované v `app/router/RouterFactory.php`. Formuláře používají Nette Forms. Rostoucí seznamy používají společný Ublaboo DataGrid; vzhled a chování stránky staví na TypeScriptu, Sassu, Tableru a Bootstrapu.

### Instalace do telefonu

Hospodaření jde nainstalovat jako aplikaci a spouštět z plochy telefonu. Popis aplikace je ve `www/manifest.webmanifest`, ikony ve `www/images/pwa` a service worker se sestavuje z `frontend/sw`; ten kromě náhradní stránky `www/offline.html` nic neukládá, aby uživatel nikdy neviděl stará data.

Nabídku instalace řeší `frontend/ts/appInstall.ts`. Na Androidu ji prohlížeč umí spustit přímo z tlačítka v nabídce, na iOS zbývá jen postup přes nabídku Sdílet, takže se tam místo tlačítka ukáže návod. V nainstalované aplikaci se nabídka neobjeví.

### Měření používání

Hospodaření nepoužívá žádnou analytiku třetí strany. Místo ní si u každé stránky počítá na serveru, kolikrát se za daný den otevřela: jeden součet na stránku a den v tabulce `page_view_daily`. K záznamu nepatří uživatel, jednotka ani IP adresa, takže z něj nejde zjistit, kdo se na co koukal, a v prohlížeči se kvůli měření neukládá nic.

Počítá se jen běžné zobrazení stránky, ne dotažení části stránky přes AJAX ani odeslání formuláře. Zapisuje se stranou provozu: když zápis selže, zaloguje se a stránka se zobrazí normálně. Do doby, než proběhne migrace, měření samo mlčí.

Čísla se zobrazují v Admin:Nápovědy jako součet za posledních 90 dní. Seznam stránek bez nápovědy je díky tomu seřazený od nejpoužívanějších, takže je vidět, kde se psaní nápovědy vyplatí nejdřív.

Co aplikace o uživatelích ukládá a co zůstává v prohlížeči, popisuje veřejná stránka `/zasady-soukromi`.

Podrobné pracovní pokyny pro automatizované agenty nejsou součástí této dokumentace; jsou odděleně ve složce `.agents`.
