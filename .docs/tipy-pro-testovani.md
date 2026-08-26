# Jak ověřovat změny

Automatické testy ověřují změnu co nejjednodušším vhodným způsobem. Všechny běží v Dockeru a nesmějí komunikovat se skutečným SkautISem ani jinou externí službou.

## Volba typu testu

- **Jednotkový test** ověřuje malou část pravidel bez databáze.
- **Integrační test** ověřuje spolupráci kódu s databází nebo nastavením aplikace.
- **Akceptační test** ověřuje důležitý a stabilní postup uživatele v prohlížeči, například stránku, formulář nebo seznam.

Ověřte běžný postup i důležité chybové stavy. Pravidla hospodaření nepatří do šablon ani obrazovek, aby šla ověřit samostatně.

## Databáze a obrazovky

- Komunikaci se SkautISem nahraďte v testech bezpečnou náhradou; test nesmí volat síť.
- Ukládání dat a změny databáze ověřujte integračním testem proti testovací databázi.
- Akceptační test si připraví vlastní data a musí fungovat samostatně i v celé sadě.
- Pokud se při přechodu mezi stránkami nebo odeslání formuláře může objevit dočasná chyba spojení se SkautISem, použijte společný pomocný postup z rodičovské třídy akceptačních testů. Nevytvářejte vlastní opakování ani pevně zapsané čekání.

## Spouštění

Při prvním spuštění připravte testovací prostředí:

```bash
make test-init
```

Cílený test spusťte přes `TEST` včetně cesty k souboru:

```bash
make test-unit TEST=tests/unit/App/SomeTest.php
make test-integration TEST=tests/integration/SomeCest.php
make test-acceptance TEST=tests/acceptance/SomeCest.php:scenarioName
```

Celé skupiny spustíte příkazy `make test-unit`, `make test-integration` a `make test-acceptance`. Před odevzdáním změny spusťte nejmenší odpovídající sadu a příslušné kontroly z [Příkazů pro práci na projektu](nastroje.md).
