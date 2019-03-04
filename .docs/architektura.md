# Write model
Jedná se víceméně o [OLTP](https://en.wikipedia.org/wiki/Online_transaction_processing).

Hodně je zde využívána terminologie 

Zde je implementována (převážně) logika, která vede k side-effectům (změna v db, poslání mailu, ...).
Většina logiky je realizována skrze [agregáty](https://martinfowler.com/bliki/DDD_Aggregate.html),
případně doménové služby.

Write model můžeme rozdělit do několika vrstev:
- Aplikační - command bus
- Doména - agregáty, doménové služby - logika spojená s hospodařením
- Infrastruktura - prakticky vše, co se dotýká světa mimo doménu (persistence do databáze, Skautis)

## Doména
agregáty, doménové služby - logika spojená s hospodařením

Patří sem také Commandy a Command Handlery.

Pokud Doména využívá nějakých externích služeb, definuje rozhraní,
které je následně implementováno v Infrastruktuře.

Jednotlivé části aplikace jsou dekomponovány do modulů/[bounded contextů](https://martinfowler.com/bliki/BoundedContext.html).

### Testování
Doménovou logiku (AKA business logic) testujeme pomocí unit testů.
Testujeme všechny scénáře, ve kterých jsou agregáty a doménové
služby používány.


## Infrastruktura

### Repozitáře
Agregáty jsou v rámci write modelu získávány a ukládány pomocí repozitářů.
Repozitáře jsou definovány jako interface v `<bounded context>/Repositories`.
Implementace těchto repozitářů jsou v Infrastructure (ve adresáři `Infrastructure/Repositories/<bounded context>`),
případně v jiném bounded contextu, který implementaci poskytuje (pro implementaci rozhraní z `ContextA` v `ContextB` v adresáři `ContextB\ContextA\Repositories`.

# Read model
Model, který slouží pouze pro získávání dat pro UI.
Je bez side-effectů (pokud pomineme IO spojené s cachováním).

Existují zde 4 základní typy objektů:
- DTO - data transfer objekt
- Query - value objekt posílaný přes query bus
- Query handler - zpracovává query a vrací DTO/kolekci DTO
