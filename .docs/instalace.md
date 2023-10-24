# Instalace pro lokální vývoj

Aplikace vyžaduje:
- PHP 8.1
- MySQL 8
- Nginx
- Composer
- Yarn

## Docker
Pro lokální vývoj je připraven Docker container a konfigurace pro **docker compose**.
Všechny potřebné příkazy jsou definované v Make file

```bash
make up # Spustí container v detached modu
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