# Jak připravit Pull Request

Změny do `master` přicházejí přes [Pull Request](https://docs.github.com/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/about-pull-requests).

## Před otevřením Pull Requestu

- Větší změna má issue s domluveným cílem.
- Změnu rozdělte do malých commitů, které řeší jednu související věc.
- Spusťte nejmenší odpovídající sadu testů a kontrol; postup je v [Jak ověřovat změny](tipy-pro-testovani.md) a [Příkazech pro práci na projektu](nastroje.md).
- Stručně popište dopad na uživatele, způsob ověření a případná omezení nebo navazující práci.

## Automatické kontroly a schválení

Každý Pull Request spustí automatické kontroly na GitHubu. Ověří podobu kódu, možné chyby, šablony, databázi a testy; přesný seznam je v [nastavení kontrol](../.github/workflows/main.yml).

Před mergem musí být CI zelené a PR schválené maintainerem. U naléhavého bugfixu může maintainer postup upravit podle závažnosti situace.

Pokud to usnadní následnou kontrolu, upravte poslední commit pomocí [git fixup](https://filip-prochazka.com/blog/git-fixup).
