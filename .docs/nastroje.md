# Příkazy pro práci na projektu

Všechny příkazy spouštějte přes Docker. Nejjednodušší je použít `make help`; příkazy z `Makefile` samy zvolí správný kontejner i uživatele.

## Závislosti a databáze

```bash
make composer-install
make composer-update
make fixtures
make test-mapping
```

Strukturu databáze měňte vždy migrací, aby ji šlo bezpečně zopakovat v každém prostředí. Pokud pro daný krok není příkaz `make`, spusťte příkaz v PHP kontejneru:

```bash
make run CMD='bin/console migrations:diff'
make run CMD='bin/console migrations:migrate'
```

Pro interaktivní práci použijte `make enter` nebo `make enter-xdebug`. Shell i `make run` zvolí uživatele podle `.make.local`: v rootless režimu kontejnerového `root`, jinak `docker`. Uvnitř shellu spouštějte Composer, konzoli i npm přímo, bez `sudo`. Composer skripty zdědí stejného uživatele a prostředí.

## Buildování frontendu
Pro vybuildování assetů používáme [Vite](https://vite.dev/) a [Sass](https://sass-lang.com/).
Build vytváří hashované assety v `www/dist` a service worker v `www/sw.js`.

npm je k dispozici v hlavním Docker kontejneru.

Po spuštění vývojového prostředí přes `make up` nainstalujte frontendové závislosti a sestavte assety:
```bash
make run CMD='npm install'
make run CMD='npm run check-types'
make run CMD='npm run build'
```

Při průběžné práci použijte `make run CMD='npm run build -- --watch'`; soubory se po změně sestaví znovu.

## Testy a kontroly

Testy ověřují chování aplikace, kontroly hledají chyby ve zdrojových souborech. Spouštějte je přes `make`:

```bash
make test-unit
make test-integration
make test-acceptance
make ci-acceptance
make test-mapping

make check-phpstan
make check-latte
make check-cs-check
make check-cs
make ci
```

`check-cs` formátování opraví, zatímco `check-cs-check` jej jen zkontroluje. Každý testovací příkaz si vytvoří vlastní čisté CI prostředí a po doběhu ho odstraní. `make ci` spustí celou sadu místních kontrol včetně testů v prohlížeči.
