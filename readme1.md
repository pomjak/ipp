## Implementační dokumentace k 1. úloze do IPP 2022/23

### Jméno a příjmení: Jakub Pomsár

### Login: xpomsa00

<br>

### Kontrola argumentů
Skript parse.php na zacatku zkontroluje radny pocet argumentu se kterymi byl spusten a popripade zda ma pracovat s argumentem --help a vypsat na standardni vystup napovedu skriptu.
### Inicializace a příprava
S pomoci rozsireni XMLWriter inicializuje buffer a zapise prvni element *program* s argumentem *language* a hodnoutou *IPPcode23*. 
Skript pote zacne nacitat radky ze zdrojoveho kodu pres standardni vstup a upravovat je. Uprava spociva v zahozeni vsech radku ktere zacinaji komentarem ci jsou prazdne, vymeneni vsech bilych znaku za pouze jednu mezeru a odstraneni charakteru noveho radku. 
### Analýza
Nasledne kontroluje prvni vyskyt hlavicky *.IPPcode23* a pote upravene radky tokenizuje na jednotlive instrukce a pripadne operatory. V konecnem automatu je nejprve overen odpovidajici pocet operandu k dane instrukci a dale jednotlive instrukce a jejich operandy prochazi odpovidajici *synktaktickou* a castecnou *semantickou* (v ramci synktakticke analyzy) kontrolou predevsim pomoci regularnich vyrazu. V pripade uspechu jsou instrukce a dane operandy ulozeny do XMLWriter bufferu a proces se opakuje pro dalsi nacteny vstup.

### Generace mezikodu ve formátu XML
Po uspesne provedene analyze a zapsani posledniho elementu je XML dokument ukoncen, buffer vytisknut na standardni vystup a vycisten, skript vraci hodnotu 0 jako uspech.  