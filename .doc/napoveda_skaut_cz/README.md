# Návrh nápovědy h.skautingu

Tato složka obsahuje DokuWiki zdroje určené k ručnímu vložení do namespace
`h-skauting` na `napoveda.skaut.cz`. Cesty souborů zrcadlí URL stránek:
například `h-skauting/tabory/ucastnici.txt` patří na
`h-skauting:tabory:ucastnici`.

## Rozsah a výchozí stav

Návrh vychází z `origin/master` ve verzi `666f975d` a z hlavní navigace v
`app/config/menu.neon`. Balíček pokrývá běžně dostupné oblasti: Akce, Tábory,
Vzdělávačky, Cestovní příkazy, Jednotku, Platby a Nastavení. Administrace není
součástí veřejné uživatelské nápovědy.

Táborové stránky popisují skutečnou účast podle opravy filtru `Real => true`.
Zveřejnit je až s verzí aplikace, která tuto opravu obsahuje.

## Mapa článků a funkcí

| Oblast | DokuWiki zdroje | Ověřená funkčnost |
| --- | --- | --- |
| Akce | `h-skauting/akce.txt` | založení, účastníci, automatické statistiky, pokladna, oprávnění, historie změn, uzavření a exporty |
| Tábory | `h-skauting/tabory.txt` | údaje ze SkautISu, skutečná účast, e-přihlášky, rozpočet, pokladna a závěrečná zpráva |
| Vzdělávačky | `h-skauting/vzdelavacky.txt` | detail, účastníci, skutečné osobodny, rozpočet, pokladna a závěrečná zpráva |
| Cestovní příkazy | `h-skauting/cestovni-prikazy.txt` | příkazy, vozidla a smlouvy o proplácení cestovních náhrad |
| Jednotka | `h-skauting/jednotka.txt` | pokladní kniha, doklady, rozpočtové kategorie a exporty |
| Platby | `h-skauting/platby.txt` | platební skupiny, přidání plateb, párování, vratky, VS a exporty |
| Nastavení | `h-skauting/nastaveni.txt` | uživatel, bankovní účty, transakce, e-maily a automatizace |

`h-skauting.txt` je hlavní rozcestník. Každá oblast má jeden úplný článek s
oddíly; dílčí stránky Akcí jsou ponechané pouze pro kompatibilitu veřejných
odkazů. Stránky Akce, Platby a Cestovní příkazy zachovávají původní odkazy na
videonávody. Fakturace je ve všech článcích uváděná pouze jako připravovaná
funkce; neobsahuje žádný pracovní postup.

Existující stránka `h-skauting:role-a-opravneni` se nemění a zůstává zdrojem
pro popis oprávnění.

## Publikační kontrola

1. Vložit soubory do odpovídajících stránek DokuWiki a ověřit náhled na desktopu i mobilu.
2. Otevřít všechny interní odkazy z rozcestníku a ověřit, že každá stránka popisuje odpovídající obrazovku aplikace.
3. Ověřit, že zůstaly zachované odkazy na playlist a všech devět dosavadních videonávodů.
4. Redakčně projít pracovní scénáře: založení a uzavření akce; skutečná účast tábora; import příjmů; vytvoření a vrácení platby; bankovní transakce; vozidlo, smlouva a cestovní příkaz.
5. Táborovou kapitolu o skutečné účasti publikovat až po nasazení opravy táborových statistik.
