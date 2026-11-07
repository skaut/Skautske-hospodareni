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

Hospodaření nepoužívá žádnou analytiku třetí strany a v prohlížeči se kvůli měření neukládá nic. Měří se jen na serveru a jen u přihlášených uživatelů. Veřejné stránky, přihlašování a administrace se nepočítají.

**Sezení** (`usage_session_daily`). Sezení je jedno přihlášení za jeden den. Co už se započítalo, si pamatuje serverová session. Do databáze jdou jen součty: den × jednotka × úroveň × počet sezení, bez uživatele a bez IP adresy. Počítá se dvakrát:

- Za celý systém (prázdný kód jednotky): přihlášení se za den započítá jednou celkem, jednou v každé sekci, na každé obrazovce a na každé stránce, ať se tam vrací kolikrát chce a ať přepne roli kolikrát chce.
- Pod jednotkou role: přihlášení se za den započítá jednou pod každou jednotkou, pod kterou pracovalo, celkem, v sekci a na obrazovce. Z těchto řádků se sčítají kraje a jednotky. Kdo za den přepne roli do jiné jednotky, započítá se u obou, proto součet krajů může být vyšší než celý systém.

Jednotka je registrační číslo jednotky aktivní role zkrácené na dvě části, tedy na středisko. Oddíl `621.66.004` i jeho družina se počítají pod `621.66`, role okresu pod `621`. Ústředí má vlastní kód. Role bez jednotky, nebo s jednotkou, kterou skautIS nevrátí, se počítá jako neurčená. Jednotka se zjistí jednou za přihlášení a roli. Sekce (platby, faktury, pokladní kniha jednotky, akce, tábory, vzdělávací akce, cestovní příkazy, nastavení, ostatní) a jejich obrazovky určuje `App\Model\Usage\UsageSection` a `UsageScreen` podle presenteru. Prázdný řetězec v tabulce znamená úroveň nad: prázdná sekce jsou všechny sekce, prázdná obrazovka celá sekce, prázdná stránka celá obrazovka.

Počítá se jen načtení stránky, ne dotažení její části přes AJAX. Zapisuje se stranou provozu: když zápis selže, zaloguje se, stránka se zobrazí normálně a při dalším požadavku se zápis zkusí znovu. Do doby, než proběhne migrace, měření samo mlčí.

**Admin → Využití** ukazuje vždy celý systém, role administrátora ve skautISu nehraje roli. První úroveň je celý systém se všemi kraji, po kliknutí na kraj jeho okresy, po kliknutí na okres jeho střediska. Číslování podle Směrnice pro označování jednotek: kraje mají pevná trojmístná čísla (číselník `UsageUnitCode::REGIONS`), okres začíná prvními dvěma místy svého kraje (Mělník 216 ve Středočeském kraji 210), středisko přidává za tečku dvě číslice k číslu okresu nebo kraje. Zvláštní jednotky (`900.xx`), ústředí a role bez jednotky mají vlastní řádek. Kraj a okres se dopočítají při zobrazení, ukládá se jen kód střediska. Období je celý rok, jeden měsíc, nebo rok po měsících. Obecný přehled (zařízení, prohlížeče, kdy se pracuje, volitelné funkce) se počítá z přihlášení `user_login` a jednotky nerozlišuje.

**Přihlášení** (`user_login`) slouží jen obecnému přehledu. Místo uživatele se ukládá jednosměrný hash s klíčem (HMAC-SHA256), podle kterého jde spočítat různé uživatele, ale ne zjistit, kdo to byl. Jednotka ani role se neukládají. Klíč je obecný klíč aplikace v proměnné prostředí `APP_KEY` (`App\Model\Common\Services\KeyedHasher`). Cokoli dalšího aplikace bude hashovat, použije stejný klíč, jen s vlastním účelem, takže hashe různých použití spolu nesouvisí. Vývoj, testy a CI mají výchozí klíč, nasazené servery ho dostávají z tajemství `APP_KEY` v GitHubu (stejně jako `CRON_TOKEN_SHA256`, viz [cron.md](cron.md)). Bez klíče se přihlášení nezaznamenávají a Admin → Využití na to upozorní. Změna klíče rozdělí stejné uživatele na nové, proto se nemění.

**Tmavý režim.** O tmavém režimu ví jen prohlížeč (volba v `localStorage` nebo nastavení systému). Stránka ho proto jednou za přihlášení pošle na pozadí (`navigator.sendBeacon`) na `/vzhled` a uloží se k přihlášení (`user_login.dark_mode`). `ThemeReportPresenter` schválně nedědí z `BasePresenter`: nevolá skautIS, nepočítá se jako aktivita uživatele a nespouští žádnou stránku. Podíl v přehledu se počítá z posledního přihlášení každého uživatele v období.

**Admin → Nápovědy** řadí stránky podle sezení za posledních 90 dní, takže je vidět, kde se psaní nápovědy vyplatí nejdřív.

Co aplikace o uživatelích ukládá a co zůstává v prohlížeči, popisuje veřejná stránka `/zasady-soukromi`.

Podrobné pracovní pokyny pro automatizované agenty nejsou součástí této dokumentace; jsou odděleně ve složce `.agents`.
