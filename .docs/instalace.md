# Instalace pro lokální vývoj

Pro práci na vlastním počítači potřebujete Docker Engine nebo Docker Desktop s Docker Compose a volný HTTP port. PHP, databáze, Composer, Node.js, npm i webový server běží v připravených kontejnerech, proto je kvůli tomuto projektu neinstalujte přímo do počítače.

## Příprava projektu

1. Stáhněte repozitář a přejděte do jeho kořenové složky.
2. Přidejte do `/etc/hosts` řádek `127.0.0.1 moje-hospodareni.cz`. SkautIS při přihlašování používá právě tuto adresu.
3. Pokud používáte rootless Docker, vytvořte v kořeni projektu soubor `.make.local` s obsahem `DOCKER_ROOTLESS=1`. Pro běžný Docker tento soubor není potřeba.
4. Spusťte přípravu projektu:

   ```bash
   make init
   ```

`make init` připraví vše potřebné: kontejnery, závislosti, databázi, vývojová data i podobu stránky. Aplikace pak běží na `http://moje-hospodareni.cz`; nástroj pro prohlížení databáze je na `http://adminer.localhost`.

## Běžná práce

```bash
make up       # spustí vývojové prostředí
make down     # zastaví vývojové prostředí a odstraní jeho kontejnery
make logs     # zobrazí logy
make enter    # otevře shell ve vývojovém PHP kontejneru
make help     # vypíše všechny dostupné příkazy
```

K vývojové databázi se lze z vývojového nástroje připojit přes port 3306. Testovací databáze z počítače dostupná není.

## Rootless Docker

Příznak v `.make.local` nastavte jednou; platí pro všechny vývojové příkazy. Soubor se necommituje ani nekopíruje do CI image. Po změně příznaku spusťte `make up`, aby se kontejnery vytvořily se správným nastavením. Není potřeba znovu spouštět `make init` ani resetovat databázi.

`make enter` a `make enter-xdebug` otevřou shell jako kontejnerový `root`. U rootless Dockeru odpovídá tento uživatel vašemu účtu na hostiteli. Přímo v shellu proto můžete spouštět `composer install`, `bin/console` nebo `npm run build`; stejné oprávnění i prostředí zdědí jejich skripty. PHP-FPM používá stejného uživatele. Checkout musí patřit účtu, pod kterým rootless Docker běží.

Příznak nezapínejte u rootful Dockeru. Výchozí vývojové CLI používá uživatele `docker`. Testy včetně `make ci` vždy používají společný CI stack a testovacího uživatele `docker`, bez vývojového rootless override. GitHub Actions nepotřebují lokální příznak ani jiné příkazy.

## Obsazený HTTP port

Výchozí port 80 používá webový server projektu. Pokud jej používá jiná aplikace, spusťte projekt na jiném portu:

```bash
TRAEFIK_HOST_PORT=8080 make up
```

Potom otevírejte aplikaci na `http://moje-hospodareni.cz:8080`.

## macOS

Na Macu s Apple Silicon nastavte před spuštěním příkazů `make` oba Compose soubory, oddělené dvojtečkou:

```bash
export COMPOSE_FILE=docker/docker-compose.yml:docker/docker-compose.macos.yml
```
