# Instalace pro lokální vývoj

Aplikace vyžaduje:
- PHP 7.4
- MySQL 8
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
V kontejneru stačí spustit příkaz `phing init`.

**Poznámka**: Při commitování se automaticky opravuje coding standard v PHP - to však vyžaduje lokálně nainstalované PHP.
Pokud nemáte mimo kontejner instalované PHP alespoň ve verzi jako používá Hospodaření,
je možné automatickou opravu coding standardu vypnout nastavením proměnné `HUSKY_SKIP_INSTALL` na `true` při instalaci
`yarn` závislostí. Tedy např.:

- `export HUSKY_SKIP_INSTALL=true; phing init`
- `HUSKY_SKIP_INSTALL=true yarn install`
