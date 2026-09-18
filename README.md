# AErchi.ba — BiH agregator cijena patika

Laravel aplikacija za uvoz, povezivanje i poređenje stvarnih ponuda patika isključivo iz trgovina koje posluju u Bosni i Hercegovini.

See `docs/product-import-poc.md` for source notes and setup instructions.

## Local setup

Requires PHP 8.2+ with SQLite support and Composer. Dependencies, local configuration and the SQLite database are not committed.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan products:sync
php artisan serve
```

Open `http://127.0.0.1:8000/catalog` after starting the server. The legacy `/asics` URL redirects to the ASICS brand filter. A single store can be refreshed with
`php artisan products:sync --store=buzz`. Run tests with `composer test`.

In PowerShell, use `Copy-Item .env.example .env` and `New-Item database/database.sqlite -ItemType File` instead of `cp` and `touch` on a fresh checkout.

## Product intelligence

Create a username/password account at `/account` to keep Fit Passport entries, Fit Graph relationships, and alerts connected to one profile. Natural search is available from `/catalog`, for example `crne Nike muske 43 do 180 KM`.

Use `/scanner` to upload a shoe label photo. The local OCR backend is Tesseract; set `TESSERACT_PATH` in `.env` when it is not installed at the default Windows path. Price and restock alerts are checked with:

```bash
php artisan alerts:check
```

Aktivni importer slugovi su `sportvision`, `buzz`, `sportreality`, `intersport`, `thespot`, `nsport`, `planika`, `underarmour`, `deichmann`, `officeshoes`, `astra` i `djaksport`. Izvori su ograničeni eksplicitnom BiH allowlistom u `config/catalog.php`. Podržani su sportski i lifestyle brendovi zastupljeni u domaćim trgovinama, uključujući ASICS, Nike, adidas, New Balance, Puma, Hoka, Skechers, Reebok, Salomon, Mizuno, On, Converse, Under Armour, ECCO, Geox, Fila, Joma, Umbro, Diadora, Head i druge.

Trenutna lokalna baza sadrži 4.654 modela, 5.660 ponuda i 75 brendova iz osam provjerenih trgovina: Sport Vision, Buzz Sneakers, N Sport BiH, Under Armour BiH, Sport Reality, Office Shoes BiH, Planika BiH i Deichmann BiH. Puni uvoz prati sve javne stranice kategorija i Office Shoes load-more rezultate. Konektori za izvore koji privremeno blokiraju javni katalog ostaju odvojeni dok se ne može garantovati pouzdan uvoz.

Ruta `/analytics` prikazuje tržišni pregled iz stvarnih podataka: rang trgovina i brendova, prosječnu i granične cijene, cjenovne segmente, broj akcija, najveće popuste, zastupljenost veličina i modele dostupne u više trgovina. Rezultati se keširaju deset minuta, a indeksi u bazi održavaju brzo prvo učitavanje i na punom katalogu.

Ruta `/deals` je zasebni Deal Radar nad svim evidentiranim sniženjima. Podržava pretragu te filtere po brendu, BiH trgovini, dostupnoj EU veličini, minimalnom procentu popusta i maksimalnoj cijeni, uz sortiranje po procentu popusta, uštedi u KM, cijeni ili vremenu provjere. Svaka kartica prikazuje staru i novu cijenu, apsolutnu uštedu i vodi direktno na javnu ponudu trgovine.
