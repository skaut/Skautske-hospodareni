# Instalace pro lokální vývoj

Aplikace vyžaduje:
- PHP 7.3
- MySQL 5
- Apache
- Composer
- Yarn

## Docker
Pro lokální vývoj je připraven Docker container a konfigurace pro **docker-compose**.

```bash
docker-compose up -d # Spustí container v detached modu
```

V kontejneru je možné spustit bash pomocným skriptem:
```bash
docker/ssh
```

## Nastavení hosts
Skautis při přihlašování přesměrovává na `hospodareni.loc`.
Proto je třeba nastavit si mapování této domény na localhost.

Stačí přidat tento řádek do souboru `/etc/hosts`:
```
127.0.0.1   hospodareni.loc
```

## Příprava projektu
V kontejneru stačí spustit příkaz `phing build`.
