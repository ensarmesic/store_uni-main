# AErchi Intelligence 2.0 — rad i ograničenja

## Pokretanje

```sh
php artisan migrate
php artisan products:sync
php artisan watch:check
php artisan schedule:work
```

Scheduler već uključuje uvoz svaki sat, obične alerte i Watch svakih pet minuta. `lens:index` se pokreće jednom dnevno kada postoji prvi indeks. U produkciji pokretati `schedule:run` svake minute. Pokrenuti samo jedan trajni scheduler; komande imaju zaštitu od preklapanja.

Nove stranice: `/agent`, `/watch`, `/purchases`; Lens ostaje na `/scanner`, Fit DNA na kompatibilnoj ruti `/fit-passport`. Na proizvodu se nalaze Decision Card, historija odabrane veličine, Watch i obrazac iskustva kupovine.

## Podaci i odluka

- Import upisuje posljednju stvarno opaženu cijenu dostupne veličine po danu. Efektivna cijena je cijena varijante ili cijena ponude kada varijanta nema zasebnu cijenu. Nema retroaktivnog izmišljanja historije.
- `sizes_checked_at` se ažurira samo kada importer eksplicitno dostavi `available_sizes`. Ponuda i veličine moraju biti provjerene unutar 48 sati. Nakon migracije prethodne ponude čekaju novu provjeru veličina.
- Za cijenu se računaju dnevni minimumi među trgovinama. Potrebna su najmanje tri različita dana i raspon od sedam dana unutar posljednjih 90 dana za Buy/Wait.
- Buy: cijena do 5% iznad 90-dnevnog minimuma ili do 10% uz Stock Pressure najmanje 50. Wait: najmanje 15% iznad minimuma bez jakog stock pritiska. Između tih granica rezultat je neutralan ili traži razmatranje. Ovo su objašnjiva pravila, ne prognoza sniženja.
- Stock Pressure: neto procentualni pad broja trgovina za broj tokom sedam dana, najmanje dvije dostupne trgovine na početku. Koristi isti skup trgovina s poznatim stanjem na oba datuma, deduplicira ponude jedne trgovine. Nestale ponude i stare/nepotvrđene provjere se isključuju. Prikazani su i periodi 3/14 dana. Ne znamo broj komada u trgovini.
- Fit DNA: lični zapis za tačan model; zatim relativna razlika veličina između modela kod istih drugih profila sa odgovarajućim opisom izvornog fita. Najmanje tri različita profila podržavaju pobjednički broj i najmanje 60% glasova. Jedan profil daje najviše jedan glas. Broj je orijentacija iz vlastitog brenda samo kada nema jače veze. Ne postoji fallback na prosječnu veličinu drugih kupaca.
- Zadržani parovi koji odgovaraju iz prijavljenih kupovina mogu biti ciljevi dodatnih modelskih odnosa. Vraćeni parovi nisu pozitivni ciljevi. Iskustva su samoprijavljena, ne potvrđene kupovine. Anonimni profili nisu dokaz jedinstvenih fizičkih osoba.
- Fit ocjena `/100` je heuristička jačina dokaza, ne kalibrisana vjerovatnoća. Tijesan/širok lični zapis nikada ne daje pozitivan signal za isti broj.

## Shopping Agent

Lokalni parser pravila prepoznaje budžet, broj, nekoliko boja, čekanje, eksplicitno željeni brend i tačne poznate modele s opisom fita. Broj/budžet iz zasebnih polja imaju prednost. Ne mijenja Fit DNA tokom analize. Višeznačan naziv modela traži odabir kroz profil. Nije LLM i ne tvrdi da razumije svaku rečenicu niti medicinske/sportske potrebe. UI prikazuje protumačene uslove i ograničenja.

Analizira najviše 60 kandidata i prikazuje šest rezultata. Budžet se provjerava prema stvarnoj dostupnoj veličini. Personalizovani rezultati nisu zajednički keširani. Namjena proizvoda se ne izmišlja iz imena.

## Watch i email

Obavijest nastaje za Buy signal unutar opcionalnog budžeta. Zapis obavijesti i promjena generacije su u jednoj transakciji. Ponovljeni Buy ne pravi duplikate. Novi signal se šalje tek nakon prekida uslova i najmanje 24 sata. Potvrđeni vlastiti loš fit blokira pozitivan signal i za gosta.

Email je dobrovoljan po praćenju, uz potpisani link potvrde s rokom jednog sata. SMTP mora biti podešen; log transport se nikada ne predstavlja kao poslan email. Neprovjerene adrese ne dobijaju Watch rezultate. Dostava pokušava do pet puta, najmanje 15 minuta između pokušaja. Inbox obavijest ostaje dostupna i kada SMTP ne radi. SMTP nema exactly-once garanciju ako proces padne nakon slanja, prije upisa potvrde.

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=...
APP_URL=https://your-real-host
```

Ispravan `APP_URL` je potreban za linkove u automatskim porukama. Ova implementacija ne uključuje mobilni Web Push. Stari `/alerts` i dalje prikazuje jednostavne in-app uslove.

## Lokalni Lens

Fotografije korisnika se obrađuju u lokalnom Python procesu iz privremene upload datoteke, bez slanja vanjskom API-ju i bez upisa u katalog. Indeks sadrži javne slike proizvoda. Model rangira slične kandidate; korisnik potvrđuje model. Nema obećanja tačnog prepoznavanja ulice, boje ili SKU-a. Najbolje radi s jasnim izrezom jedne patike; nema automatske segmentacije slike.

```powershell
python -m venv .runtime/lens-venv
.\.runtime\lens-venv\Scripts\python.exe -m pip install torch --index-url https://download.pytorch.org/whl/cpu
.\.runtime\lens-venv\Scripts\python.exe -m pip install -r scripts/lens/requirements.txt
php artisan lens:index --download-model
```

Na Linuxu koristiti `.runtime/lens-venv/bin/python`. `LENS_PYTHON` može promijeniti putanju. Prvi poziv preuzima model; indeksiranje/pretraga nakon toga koriste lokalne datoteke. Osnova je [CLIP image projection API](https://huggingface.co/docs/transformers/v4.56.0/en/model_doc/clip). Paket/model je lokalni pomoćni proces, ne OpenAI API.

Indeksiranje je inkrementalno po URL-u slike; puna izgradnja uklanja neaktivne modele. `--limit=40` je probna parcijalna izgradnja koja čuva ostatak postojećeg indeksa. Slike se preuzimaju samo sa eksplicitno dozvoljenih HTTPS hostova, bez preusmjeravanja, do 8 MB. Nepreuzete slike se preskaču i njihov broj prijavljuje. Indeks se zamjenjuje atomarno. Pretraga dodatno provjerava da proizvodi još postoje i imaju aktivne ponude.

## Popularnost, kupovine i dostava

Popularnost koristi interakcije zadnjih 30 dana, najviše jedan događaj iste vrste po profilu/modelu/danu: pregled 1, poređenje 2, favorit/praćenje 4, odlazak trgovcu 5. Bez interakcija prednost imaju modeli s više ponuda. Nema unaprijed favorizovanih naziva/brendova. Ovo su signali interesa, ne prodajni rezultati.

Kupovine se uređuju ponovnim unosom istog modela/broja, brišu na `/purchases` i spajaju pri prijavi. Zbirni prikaz na proizvodu traži tri različita profila i koristi samo najnoviji zapis svakog profila.

Ukupni trošak se računa za jednu ponudu u BAM i provjerene uslove iz `config/delivery.php`, važeće najviše 90 dana od provjere. Sport Vision i Buzz su provjereni 18.09.2026: 9,95 KM ispod 99 KM, besplatno iznad 99 KM; tačno 99 KM ostaje nepoznato zbog neprecizne granične formulacije. Click & Collect traži online karticu i potvrdu trgovine. Izvori: [Sport Vision](https://www.sportvision.ba/uslovi-isporuke), [Buzz](https://www.buzzsneakers.ba/isporuka). Za ostale trgovine ne pretpostavljamo besplatnu dostavu.

## Provjera

`php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml` provjerava migracije, signal/stock/fit pravila, izolaciju korisnika, email potvrde/retry, parser/agent, popularnost, cijene veličina i uslove dostave. Lokalni browser smoke uključuje desktop/mobilni prikaz i slanje Agent obrasca. SMTP slanje u testovima je zamijenjeno mockovima; ne šalju se stvarne poruke.
