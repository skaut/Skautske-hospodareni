# Jak na Pull Request

Všechny změny v masteru probíhají přes [Pull Requesty](https://help.github.com/en/articles/about-pull-requests) (PR).

Pro mergnutí je třeba splnit několik náležitostí:
- [Zelený CI build](#ci)
- Existující [issue](planovani-prace.md#issues) (u větších změn)
- Schválený PR od maintainera

## CI
Pro každý PR běží ve Github Actions workflow. Co vše v buildu běží lze zjistit z [konfiguračního souboru](../.github/workflows/main.yml).

## Code review
Před mergnutím PR je třeba alespoň jedno schválení (*Approve*) od nějakého z maintainerů.
V případě bugfixu může maintainer mergnout PR i bez code review.

Nový kód [by měl být otestovaný](tipy-pro-testovani.md) a ideálně implementovaný
v rámci menších commitů, které výrazně usnadní review. Změny v existujících commitech je nejlepší dělat pomocí [fixupů](https://filip-prochazka.com/blog/git-fixup)
