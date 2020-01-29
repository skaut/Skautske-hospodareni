# Nástroje
Pro správu závislostí, kontrolu kvality kódu atd. používáme tyto nástroje:

## Doctrine migrace
Změny v databázi jsou verzované.

Migrace na aktuální schéma:
```bash
php www/index.php migrations:migrate
```

Generování migrace se změnami:
```bash
php www/index.php migrations:diff
```

## Buildování frontendu
Pro vybuildování assetů používáme [Webpack](https://webpack.js.org/) a [Sass](https://sass-lang.com/).

Yarn je k dispozici v hlavním docker containeru.

```bash
yarn install
yarn build
```

Pro automatické buildování při změně SCSS/TS souboru, lze použít:
```bash
yarn build --watch
```

## Testy
Pro testování používáme [Codeception](http://codeception.com/).


Testy lze spustit příkazem v kontejneru:
```bash
phing tests-unit # Pouze jednotkové testy
phing tests-integration # Pouze integrační testy
phing tests # Jednotkové + Integrační testy
phing tests-acceptance # Akceptační testy
```

## Coding standard
Coding standard vychází z [Doctrine Coding Standardu](https://github.com/doctrine/coding-standard).
Zda je projekt v souladu s CS lze ověřit pomocí příkazu v kontejneru:

```bash
phing coding-standard
```

Automaticky lze nechat opravit pomocí:

```bash
./vendor/bin/phpcbf app
```
