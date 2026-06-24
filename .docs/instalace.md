# Instalace pro lokální vývoj

Aplikace vyžaduje:
- PHP 8.3
- MySQL 8
- Nginx
- Composer
- Node.js 20 a Yarn
- Docker Compose

Všechny potřebné nástroje jsou instalovány v příslušných kontejnerech
Na hostujícím stroji musí být ve výchozím stavu volné porty 80 a 3306. Port 3306 je možné použít pro propojená s IDE

## Docker
Pro lokální vývoj je připraven Docker container a konfigurace pro **docker compose**.
Všechny potřebné příkazy jsou definované v `Makefile`.

```bash
make build # Sestaví image
make up    # Spustí dev stack v detached módu
```

V kontejneru je možné spustit bash pomocným skriptem:
```bash
make enter
```

## Nastavení hosts
Skautis při přihlašování přesměrovává na `moje-hospodareni.cz`.
Proto je třeba nastavit si mapování této domény na localhost.

Stačí přidat tento řádek do souboru `/etc/hosts`:
```
127.0.0.1   moje-hospodareni.cz
```

## Příprava projektu
Stačí spustit příkaz `make init`.
Ten sestaví image, spustí dev stack a uvnitř PHP kontejneru zavolá `composer app-init`, který provede lokální inicializaci projektu včetně závislostí, migrací a frontend buildu.

## Rozběhnutí na macOS
Je potřeba si založit v domovské složce `.env` soubor s obsahem
```bash
COMPOSE_FILE=-f docker/docker-compose.yml -f docker/docker-compose.macos.yml
```
