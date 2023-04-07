## Implementační dokumentace k 1. úloze do IPP 2022/23

### Jméno a příjmení: Jakub Pomsár

### Login: xpomsa00

<br>

### Kontrola argumentů

Skript parse.php na začátku zkontroluje řádný počet argumentů, se kterými byl spuštěn, a případně zda má pracovat s argumentem *--help* a vypsat na standardní výstup nápovědu skriptu.
### Inicializace a příprava

S pomocí rozšíření XMLWriter inicializuje buffer a zapíše první element *program* s argumentem *language* a hodnoutou *IPPcode23*
Skript poté začne načítat řádky ze zdrojového kódu přes standardní vstup a upravovat je. Úprava spočívá v zahození všech řádků, které začínají komentářem či jsou prázdné, výměně všech bílých znaků za pouze jednu mezeru a odstranění znaku nového řádku.
 
### Analýza
Následně kontroluje první výskyt hlavičky *.IPPcode23* a poté upravené řádky tokenizuje na jednotlivé instrukce a případné operátory. V konečném automatu je nejprve ověřen odpovídající počet operandů k dané instrukci a dále jednotlivé instrukce a jejich operandy prochází odpovídající *syntaktickou* a částečnou *sémantickou* (v rámci syntaktické analýzy) kontrolou především pomocí regulárních výrazů. V případě úspěchu jsou instrukce a dané operandy uloženy do XMLWriter bufferu a proces se opakuje pro další načtený vstup.

### Generace mezikodu ve formátu XML
Po úspěšné provedené analýze a zapsání posledního elementu je *XML* dokument ukončen, buffer vytisknut na standardní výstup a vyčištěn. Skript vrací hodnotu *0* jako úspěch.
