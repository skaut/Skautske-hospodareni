# Tipy pro testování
Automatické testy by nám měly zajišťovat ověření správného chování funkcí.

Pro efektivní a ne příliš otravující testování je pár doporučení, kterými je dobré se řídit.
Spousta jich vychází z [Extremely Defensive PHP](https://ocramius.github.io/extremely-defensive-php/#/)

## Jak testovat agregáty (entity) a doménové servisy
- Chování (změnu stavu) pokrýváme unit testy. Testujeme všechny scénáře (cesty, kterými se kód může ubírat) [\[1\]](https://ocramius.github.io/extremely-defensive-php/#/107)

## Jak testovat listenery
V listenerech se většinou nachází nějaké side-effecty, kterými se nechceme zabývat při vykonávání hlavní akce (odeslání emailu o nějaké akci, apod). Stačí testovat v rámci integračního testu hlavní akce.

## Jak testovat repozitáře
Integrační test s databází

## Jak testovat komunikaci se SkautISem
Vyřezávat komunikaci se SkautISem do samostatných služeb které pouze volají SkautIS a mapují I/O na nějaká rozumná DTO.
API těchto služeb nadefinovat jako interface a pro testy implementaci nahrazovat fake objektem.

## Jak testovat presentery
Akceptační testy, pokud jsou jednoduché (většinou nejsou a je s tím hrozná práce). Pokud nejsou, tak :pray: 


## Spouštění testů
Jednotlivé testy: 
```bash
vendor/bin/codecept run <cesta k souboru>
```

Všechny daného typu: 
```bash
phing tests
phing tests-acceptance
phing tests-integration
phing tests-unit
phing tests-with-coverage
```
