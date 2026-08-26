# Instalace pro lokální vývoj

Pro práci na vlastním počítači potřebujete Docker Engine nebo Docker Desktop s Docker Compose a volný HTTP port. PHP, databáze, Composer, Node.js, Yarn i webový server běží v připravených kontejnerech, proto je kvůli tomuto projektu neinstalujte přímo do počítače.

## Příprava projektu

1. Stáhněte repozitář a přejděte do jeho kořenové složky.
2. Přidejte do `/etc/hosts` řádek `127.0.0.1 moje-hospodareni.cz`. SkautIS při přihlašování používá právě tuto adresu.
3. Spusťte přípravu projektu:

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
