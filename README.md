# Skautské hospodaření
[![wercker status](https://app.wercker.com/status/605e6519883993559fc355cc988e08a9/s/master "wercker status")](https://app.wercker.com/project/byKey/605e6519883993559fc355cc988e08a9)

# Vývoj

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

V kontejneru je možné spustit bash pomocným skriptem:
```bash
docker/ssh
```


### config.neon
V repozitáři je připraven vzorový konfigurační soubor - `app/config/config.sample.local.neon`,
který obsahuje nastavení, které lze okamžitě začít používat s Docker containerem,
pro jiný způsob vývoje může být potřeba upravit např. přístupy k DB.

Stačí tady zkopírovat soubor `config.sample.local.neon` a uložit pod názvem `config.local.neon`.

### Nastavení hosts
Skautis při přihlašování přesměrovává na `hospodareni.loc`.
Proto je třeba nastavit si mapování této domény na localhost.

Stačí přidat tento řádek do souboru `/etc/hosts`:
```
127.0.0.1   hospodareni.loc
```

### Databáze
Při prvním spuštění je třeba vytvořit schéma . Pro práci s databází lze využít přibalený adminer
na adrese `http://hospodareni.loc/adminer.php`. Při používání Dockeru se lze přihlásit
jako uživatel **root** bez hesla.

Změny v databázi jsou verzované, stačí tedy spustit příkaz:
```bash
php www/index.php migrations:migrate
```

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
Pro každý PR běží ve [Werckeru](http://www.wercker.com/) testy a kontrola coding standardu, která musí projít.
Pro lokální build lze využít [Wercker CLI](http://www.wercker.com/wercker-cli) (Mac, Linux).
