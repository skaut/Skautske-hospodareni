#Skautské hospodaření
[![Run Status](https://api.shippable.com/projects/57566fec2a8192902e22bd24/badge?branch=master)](https://app.shippable.com/projects/57566fec2a8192902e22bd24)

#Vývoj

Aplikace vyžaduje:
- PHP 7.1
- MySQL 5
- Apache
- Composer

## Postup instalace

### Docker
Pro lokální vývoj je připraven Docker container a konfigurace pro **docker-compose**.

```bash
docker-compose up -d # Spustí container v detached modu
```

V kontejneru je možné zpustit bash pomocným skriptem:
```bash
docker/ssh
```


### config.neon
V repozitáři je připraven vzorový konfigurační soubor - `app/config/config.samble.local.neon`,
který obsahuje nastavení, které lze okamžitě začít používat s Docker containerem,
pro jiný způsob vývoje může být potřeba upravit např. přístupy k DB.

Stačí tady zkopírovat soubor `config.sample.local.neon` a uložit pod názvem `config.local.neon`.

### Nastavení hosts
Skautis při přihlašování přesměrovává na `test-h.skauting.cz`.
Proto je třeba nastavit si mapování této domény na localhost.

Stačí přidat tento řádek do souboru `/etc/hosts`:
```
127.0.0.1   hospodareni.loc
```

### Databáze
Při prvním spuštění je třeba vytvořit schéma . Pro práci s databází lze využít přibalený adminer
na adrese `http://test-h.skauting.cz/adminer.php`. Při používání Dockeru se lze přihlásit
jako uživatel **root** bez hesla.

**DB dump:** https://github.com/skaut/Skautske-hospodareni/files/799142/db.sql.txt

## Testy
Snažíme se psát testy (i když jich zatím moc není).
Pro testování používáme Nette Tester.
Testy lze spustit příkazem v kontejneru:
```bash
vendor/bin/tester tests -C
```

## Coding standard
Coding standard vychází z Nette coding standardu.
Zda je projekt v souladu s CS lze ověřit pomocí příkazu v kontejneru:
```bash
vendor/bin/phpcs app --standard=ruleset.xml
```

## CI
Pro každý PR běží v Shippable CI testy a kontrola coding standardu, která musí projít.
