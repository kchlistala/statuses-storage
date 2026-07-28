# StatusStorageFinal

Serwis do przechowywania statusów. PHP 8.4 na RoadRunner, PostgreSQL przez Doctrine DBAL
(bez ORM), Kafka przez RoadRunner Jobs plugin, cache in-memory przez RoadRunner KV plugin.
Całość uruchamiana z Dockera.

## Stack

- **PHP 8.4**, serwer aplikacyjny **RoadRunner** (`.rr.yaml`) z pluginami:
  - `http` — wejście HTTP, routing przez `league/route` (PSR-15).
  - `kv` — cache in-memory (driver `memory`, storage `local-cache`).
  - `jobs` z driverem **kafka** — zarówno **konsumpcja**, jak i **produkcja** wiadomości
    idzie przez ten sam plugin (`Spiral\RoadRunner\Jobs\Jobs`). Świadomie nie używamy
    osobnej biblioteki klienckiej Kafki w PHP — jedna ścieżka integracji, mniej zależności.
    Pipeline `app-events-consume` jest aktywnie konsumowany (wpisany w `jobs.consume`
    w `.rr.yaml`); pipeline `app-events-produce` istnieje tylko po to, by PHP mogło do
    niego dispatchować wiadomości (`Jobs::connect('app-events-produce')->dispatch(...)`) —
    RR go nie konsumuje.
- **PostgreSQL** + **Doctrine DBAL** (query builder / raw SQL). **ORM jest zabronione** —
  żadnych encji Doctrine ORM, żadnego UnitOfWork. Migracje przez `doctrine/migrations`
  (działa niezależnie od ORM), ale **bez automatycznego `migrations:diff`** — bez ORM nie ma
  źródła "docelowego schematu" do porównania, więc migracje pisze się ręcznie przez
  `Doctrine\DBAL\Schema\Schema` w metodach `up()`/`down()`.
- **Kafbat UI** (`ghcr.io/kafbat/kafka-ui`) — podgląd topiców/wiadomości w docker-compose.
- **DI**: `symfony/dependency-injection`, samodzielnie (bez pełnego frameworka Symfony).
  Kontener kompilowany i cache'owany w `var/cache/<env>/container.php`
  (`src/Kernel/ContainerFactory.php`), definicje usług w `config/services.yaml`.
- **Console**: `symfony/console`, samodzielnie — `bin/console`
  (`src/Kernel/Console/ConsoleApplicationFactory.php` zbiera usługi otagowane
  `app.console_command`).

## Architektura modułowa

```
src/Module/<Moduł>/<Submoduł>/{Public,Internal}
```

Przykład docelowy (jeszcze niezescaffoldowany): `src/Module/Status/Order/{Public,Internal}`,
`src/Module/Status/OrderItem/{Public,Internal}`.

- **Public** — interfejsy, DTO, eventy, z których mogą korzystać INNE moduły.
- **Internal** — implementacja, repozytoria (dziedziczące po
  `App\Shared\Database\Public\AbstractRepository`), logika domenowa. **Niedostępne spoza
  własnego modułu/submodułu.**
- Moduły mogą mieć submoduły (dowolna głębokość zagnieżdżenia katalogów przed `Public`/`Internal`).
- `src/Shared` (Cache, Kafka, Database) i `src/Kernel` stosują ten sam podział Public/Internal
  dla wspólnej infrastruktury.

### Jak wymuszane są granice

Dwa niezależne, uzupełniające się narzędzia:

1. **Deptrac** (`deptrac.yaml`) — generyczne warstwy jeden-do-wielu, które nie wymagają
   enumeracji per moduł: nikt spoza `Shared/<X>` nie dotyka `Shared/<X>/Internal`,
   moduły widzą tylko `Shared`'s Public, `Kernel` widzi tylko warstwy Public. Deptrac **nie
   potrafi** automatycznie odizolować `Internal`-do-`Internal` między KAŻDĄ parą modułów bez
   ręcznej enumeracji dwóch warstw + reguły przy każdym nowym module — celowo tego nie
   robimy tutaj (patrz punkt 2).
2. **Custom reguła PHPStan** (`tools/PHPStanRules/ModuleBoundaryRule.php`, zarejestrowana
   w `phpstan.neon`) — daje pełną izolację N×N **bez enumeracji modułów**: dla każdej
   referencji do klasy `X\Internal\Y`, dozwolone jest tylko wtedy, gdy odwołujący się kod
   sam znajduje się pod `X\*`. Działa identycznie dla `Module/A` vs `Module/B`, dla
   submodułów (`Module/Status/Order` vs `Module/Status/OrderItem`) i dla `Shared/*`
   — automatycznie, bez dopisywania czegokolwiek przy nowym module.
   Test weryfikujący regułę na fixture'ach: `tests/Unit/PHPStanRules/ModuleBoundaryRuleTest.php`
   (uruchamia `phpstan analyse -c tests/PHPStan/fixtures.neon` na `tests/PHPStan/Fixtures/`
   i sprawdza, że import z `Internal` innego "modułu" jest złapany, a import z `Public` — nie).

### Checklist: dodanie nowego modułu

1. Utwórz `src/Module/<Moduł>/<Submoduł>/Public` i `.../Internal`.
2. Interfejsy/DTO/eventy do użytku przez inne moduły → `Public`. Implementacje, repozytoria,
   logika domenowa → `Internal`.
3. Repozytoria DBAL dziedziczą po `App\Shared\Database\Public\AbstractRepository`
   (`$this->createQueryBuilder()` na wstrzykniętym `Doctrine\DBAL\Connection`).
4. Jeśli moduł ma reagować na wiadomości z Kafki: zaimplementuj
   `App\Shared\Kafka\Public\JobHandlerInterface` (`supports()`/`handle()`) w `Internal` —
   zostanie automatycznie otagowany `app.kafka_job_handler` przez `_instanceof` w
   `config/services.yaml` i podpięty do `TaggedJobDispatcher`.
5. Jeśli moduł ma publikować wiadomości do Kafki: wstrzyknij
   `App\Shared\Kafka\Public\ProducerInterface` i wywołaj `publish(new KafkaMessage(...))`.
6. Migracje: dopisz plik w `migrations/` (klasa `Doctrine\Migrations\AbstractMigration`,
   `up()`/`down()` przez `Doctrine\DBAL\Schema\Schema` — ręcznie, bez diff).
7. **Nie trzeba** nic dopisywać w `deptrac.yaml` — izolacja Internal działa automatycznie
   przez `ModuleBoundaryRule`. Jeśli chcesz dodatkowo ograniczyć, co dany moduł może
   *importować* (np. moduł X nie powinien nigdy zależeć od modułu Y w ogóle, nawet Public),
   to już wymaga świadomego dopisania reguły w `deptrac.yaml`.
8. Uruchom `composer phpstan`, `composer deptrac`, `composer test` przed commitem.

## Uruchomienie

```
docker compose up -d --build
docker compose run --rm migrate
```

Jednym poleceniem: `make up` (patrz `Makefile` — `make help` pokazuje pełną listę celów,
w tym skróty do `composer phpstan`/`test`/`deptrac`/itd. jako `make qa`).

Serwisy: `app` (RR, host port `8090` → domyślnie zmapowany z 8080 w kontenerze — zmień przez
`APP_HTTP_PORT`, jeśli 8090 koliduje z czymś innym), `postgres` (host port `5433`, w środku
5432 — zmień przez `POSTGRES_PORT`), `kafka` (KRaft, bez Zookeepera, port `9092`, zmienna
`KAFKA_PORT`), `kafbat-ui` (port `8081`, zmienna `KAFBAT_UI_PORT`), `migrate` (jednorazowy,
uruchamia `bin/console migrations:migrate`). Porty hosta są celowo nietypowe (nie
`8080`/`5432`) i konfigurowalne, żeby nie kolidować z innymi projektami uruchomionymi lokalnie.

Uwaga: dopóki `migrations/` jest puste (brak modułów), `docker compose run --rm migrate`
zakończy się błędem `"there are no registered migrations"` — to oczekiwane na świeżym
fundamencie, nie błąd. Zniknie, gdy dojdzie pierwsza migracja.

Lokalnie bez Dockera: `composer install`, skopiuj `.env.dist` do `.env` (lub ustaw zmienne
środowiskowe bezpośrednio — `Dotenv::bootEnv()` w `bin/console`/`bin/worker.php` używa
`.env.dist` jako fallbacku, gdy `.env` nie istnieje), potem `rr serve -c .rr.yaml` (binarka
`rr` pobierana przez `vendor/bin/rr get`).

## QA

```
composer phpstan     # PHPStan level max + strict-rules + ModuleBoundaryRule
composer cs-check     # PHP-CS-Fixer --dry-run
composer cs-fix       # PHP-CS-Fixer fix
composer rector-dry   # Rector --dry-run
composer rector       # Rector fix
composer deptrac      # granice architektoniczne (patrz wyżej)
composer test         # PHPUnit (Unit + Integration)
```

Uwaga: reguła cs-fixer `new_expression_parentheses` (część `@PHP84Migration`) jest wyłączona
w `.php-cs-fixer.php` — generuje składnię `new X()->method()` bez nawiasów, której nie
parsuje jeszcze silnik PHPStan 1.x. Włączyć dopiero po migracji na PHPStan 2.x.

## Struktura katalogów (fundament)

```
bin/console, bin/worker.php        — entrypointy (console / RR worker HTTP+Jobs)
config/services.yaml               — definicje DI
docker/php/Dockerfile, docker-compose.yml
migrations/                        — migracje Doctrine (puste, brak modułów na razie)
src/Kernel/                        — bootstrap: DI, HTTP/Jobs runner, console
src/Shared/{Cache,Kafka,Database}/{Public,Internal} — wspólna infrastruktura
src/Module/                        — puste, gotowe na przyszłe moduły domenowe
tools/PHPStanRules/ModuleBoundaryRule.php
tests/{Unit,Integration,PHPStan}/
.rr.yaml, phpstan.neon, .php-cs-fixer.php, rector.php, deptrac.yaml, phpunit.xml.dist
```
