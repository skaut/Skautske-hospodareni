# Nástroje
Pro správu závislostí, kontrolu kvality kódu atd. používáme tyto nástroje:

## Doctrine migrace
Změny v databázi jsou verzované.

Migrace na aktuální schéma:
```bash
bin/console migrations:migrate
```

Generování migrace se změnami:
```bash
bin/console migrations:diff
```

## Buildování frontendu
Pro vybuildování assetů používáme [Webpack](https://webpack.js.org/) a [Sass](https://sass-lang.com/).

npm je k dispozici v hlavním docker containeru.

```bash
npm install
npm run build
```

Pro automatické buildování při změně SCSS/TS souboru, lze použít:
```bash
npm run build -- --watch
```

## Testy
Pro testování používáme [Codeception](http://codeception.com/).


Testy lze spustit pomocí `make`:
```bash
make test-unit
make test-integration
make test-acceptance
make ci-acceptance
make ci
```

Případně přímo v testovacím kontejneru:
```bash
docker exec hskauting.app-test vendor/bin/codecept run unit --no-colors
docker exec hskauting.app-test vendor/bin/codecept run integration --no-colors
docker exec hskauting.app-test vendor/bin/codecept run acceptance --no-colors
```


## Coding standard
Coding standard vychází z [Doctrine Coding Standardu](https://github.com/doctrine/coding-standard).
Zda je projekt v souladu s CS lze ověřit pomocí příkazu v kontejneru:

```bash
make check-cs-check
```

Automaticky lze nechat opravit pomocí:

```bash
make check-cs
```

Další užitečné kontroly:

```bash
make check-phpstan
make check-latte
```
