#nrk

Et kommandolinjebasert verktøy for å rippe fra tv.nrk.no og muxe til mkv
Det er mulig å laste ned enkelte programmer/episoder eller hele serier.
Når en nedlasting er ferdig vil scriptet sjekke varigheten på den nedlastede filen med mediainfo og sammenligne dette med oppgitt varighet på nrk.
Hvis varigheten er ulik blir programmet lastet ned igjen.
I noen tilfeller oppgir NRK og mediainfo varigheten litt forskjellig, så det er derfor lagt inn en toleranse på 90 sekunder.

For å bruke verktøyet må du ha [php](http://www.php.net) installert med curl aktivert.
I tillegg må følgende programmer være tilgjengelig i path:
* mkvmerge (http://www.bunkus.org/videotools/mkvtoolnix/) (Påkrevd)
* mediainfo (http://mediainfo.sourceforge.net)

På debian og lignende distribusjoner kan alt installeres med
``` apt-get install php5 php5-curl mediainfo mkvtoolnix```

##Bruk
###Enkel episode/program

For å laste ned et enkelt program eller en episode brukes nrkdl.php

Link til en eller flere programmer oppgis som argumenter:
```php nrkdl.php http://tv.nrk.no/serie/store-maskiner/obui30001107/sesong-1/episode-1```
```php nrkdl.php http://tv.nrk.no/serie/store-maskiner/obui30001107/sesong-1/episode-1 http://tv.nrk.no/serie/store-maskiner/obui30001207/sesong-1/episode-2```

###Hel serie
Hele serier kan lastes ned med serie.php. 
```php serie.php http://tv.nrk.no/serie/store-maskiner```

###Video fra nyhetsartikkel
For å laste ned videoer fra en nyhetsartikkel brukes newsrip.php
Den henter ned alle videoer i artikkelen. Link til den aktuelle artikkelen oppgis på kommandolinjen:
php newsrip.php http://www.nrk.no/kultur/se-lilyhammer-uten-spesialeffekter-1.11315012

##Plattformstøtte
Verktøyet er testet på og tilpasset windows og linux.

##Annen informasjon
Prosjektet bruker en submodule. For å få med denne må repoet klones rekursivt:
```git clone git@github.com:datagutten/nrk.git --recursive```

Finner du feil eller ønsker å forbedre prosjektet, send meg gjerne en pull request.
