# Příkazy pro práci na projektu

Všechny příkazy spouštějte přes Docker. Nejjednodušší je použít `make help`; příkazy z `Makefile` samy zvolí správný kontejner i uživatele.

## Závislosti a databáze

```bash
make composer-install
make composer-update
make fixtures
make test-init
make test-mapping
```

Strukturu databáze měňte vždy migrací, aby ji šlo bezpečně zopakovat v každém prostředí. Pokud pro daný krok není příkaz `make`, spusťte příkaz v PHP kontejneru:

```bash
docker compose -f docker/docker-compose.yml run --rm -T --entrypoint '' --user docker php \
    bin/console migrations:diff
docker compose -f docker/docker-compose.yml run --rm -T --entrypoint '' --user docker php \
    bin/console migrations:migrate
```

## Vzhled a chování stránek

Po změně vzhledu (SCSS) nebo chování stránky (TypeScript) spusťte v běžícím vývojovém prostředí:

```bash
docker compose -f docker/docker-compose.yml exec -T php yarn check-types
docker compose -f docker/docker-compose.yml exec -T php yarn build
```

Při průběžné práci použijte `docker compose -f docker/docker-compose.yml exec php yarn build --watch`; soubory se po změně sestaví znovu.

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

`check-cs` formátování opraví, zatímco `check-cs-check` jej jen zkontroluje. `make ci` spustí celou sadu místních kontrol včetně testů v prohlížeči.
