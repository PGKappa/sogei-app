# Build delle immagini Docker – partner `sogei`

Questo documento descrive come vengono costruite le immagini Docker di produzione del partner **sogei**
(`sog-php`, `sog-pgv`) a partire dalla cartella `sogei/`, e come vengono poi avviate sul server.

L'immagine del viewer (`sog-viewer`) si costruisce a parte, nel progetto del viewer: vedi
[`README_Aggiorna_Viewer_PARTNER.md`](../README_Aggiorna_Viewer_PARTNER.md).

---

## 1. Panoramica

```
create.sh / create.ps1
 ├─ 1. imposta le variabili del partner (NOME_PARTNER, porte, nomi servizi)
 ├─ 2. riscrive fastcgi_pass nelle conf nginx  →  sog-php:9000
 ├─ 3. genera i compose dai template (envsubst)
 │      docker-compose-template.yaml         → docker-compose-create.yml
 │      docker-compose-server-template.yaml  → docker-compose-server.yml
 ├─ 4. docker-compose -f docker-compose-create.yml up -d --build
 │      ├─ build sog-php  (php/Dockerfile,   multi-stage: php-fpm + composer)
 │      └─ build sog-pgv  (nginx/Dockerfile)
 │      → i container partono, l'entrypoint esegue gli script di init
 ├─ 5. docker commit dei container avviati  → sog-php:latest, sog-pgv:latest
 └─ 6. docker save                          → images/sog-php.tar, images/sog-pgv.tar
```

Le immagini finali **non** sono quelle uscite da `docker build`, ma lo snapshot (`docker commit`) dei
container dopo che l'entrypoint ha girato almeno una volta (key generate, permessi, servizi runit, ecc.).

## 2. File coinvolti

| File | Ruolo |
|---|---|
| `create.sh` | Script di build (Linux/macOS/WSL). |
| `create.ps1` | Stesso script per Windows/PowerShell (scrive i file in UTF‑8 senza BOM). |
| `imposta-permessi.sh` | `chmod +x` su tutti i `.sh` del progetto (chiamato solo da `create.sh`). |
| `docker-compose-template.yaml` | Template del compose **di build** (contiene `build:`). |
| `docker-compose-server-template.yaml` | Template del compose **di produzione** (usa solo `image:`; include anche il viewer). |
| `docker-compose-create.yml`, `docker-compose-server.yml` | Generati da `create.*` — non modificarli a mano. |
| `php/Dockerfile` | Immagine PHP-FPM 8.2 + dipendenze composer. |
| `.env.docker` | Credenziali di build (`GITHUB_TOKEN`, opz. `DOCKER_USERNAME`/`DOCKER_PASSWORD`). Non versionato: copiarlo da `.env.docker.example`. |
| `php/rootfs/` | Copiato in `/` nell'immagine PHP: entrypoint, script di init, conf php-fpm. |
| `nginx/Dockerfile` | Immagine nginx con i sorgenti in `/var/www/html`. |
| `nginx/default.conf` | Virtual host nginx (copiato in `/etc/nginx/conf.d/`). |
| `../rootfs/etc/nginx/nginx.conf` | `nginx.conf` principale (copiato in `/etc/nginx/`). |
| `../.dockerignore` | Esclusioni dal build context (vedi §6). |

`docker-compose.yml`, `start.sh`, `stop.sh` e `docker/mysql/` sono l'ambiente di **sviluppo locale**
(`localstart`, con MySQL e phpMyAdmin) e non fanno parte della build di produzione.

## 3. Variabili

Impostate in testa a `create.sh` / `create.ps1`:

| Variabile | Valore | Uso |
|---|---|---|
| `NOME_PARTNER` | `sogei` | Nome cartella partner, passato come build-arg ai Dockerfile. |
| `PARTNER_TAG` | `sog` | Prime 3 lettere del partner; prefisso di container e rete. |
| `API_LISTEN_PORT` | `8083` | Porta host → nginx (API). |
| `VIEWER_LISTEN_PORT` | `8093` | Porta host → viewer. |
| `NGINX_SERVICE` | `sog-pgv` | Nome servizio/container/immagine nginx. |
| `PHP_SERVICE` | `sog-php` | Nome servizio/container/immagine PHP. È anche l'host usato in `fastcgi_pass`. |
| `VIEWER_SERVICE` | `sog-viewer` | Nome dell'immagine viewer (solo compose server). |

## 4. Dettaglio dei passi

### 4.1 Riscrittura `fastcgi_pass`

Nginx raggiunge PHP-FPM per nome container sulla rete Docker, quindi in entrambi i file
`nginx/default.conf` e `../rootfs/etc/nginx/nginx.conf` ogni `fastcgi_pass <qualcosa>:9000;` viene
sostituito con `fastcgi_pass sog-php:9000;`.

> ⚠️ Lo script modifica file **versionati**: dopo una build `git status` li mostra come modificati
> (es. `sta-php` → `sog-php` in `rootfs/etc/nginx/nginx.conf`). Valutare se committarli o ripristinarli.

### 4.2 Generazione dei compose

`envsubst` (o `Expand-Template` in PowerShell) sostituisce i `${VAR}` dei template con i valori del §3.

### 4.3 Build delle immagini

Il build context è la **root del repository** (`context: ../.`), quindi i Dockerfile vedono tutto il
progetto Laravel; il nome partner arriva come `ARG NOME_PARTNER`.

**`php/Dockerfile`** (multi-stage):

1. **`server`** – `php:8.2-fpm-alpine` con:
   - pacchetti di sistema (bash, git, runit, mysql-client, icu, libzip, gd deps, python3, scipy…);
   - estensioni PHP: `bcmath pdo pdo_mysql pdo_sqlite pcntl xml intl zip gd curl mcrypt`;
   - `envsubst` (estratto da gettext);
   - virtualenv Python in `/var/www/html/.venv` con il pacchetto `drs`, copiato anche in
     site-packages di sistema (usato dagli script `drs_exec.py` dei giochi);
   - `safe.directory` git per i pacchetti `vendor/pgvirtual/*`;
   - `COPY ./sogei/php/rootfs/ /` (entrypoint e init script);
   - variabili d'ambiente di tuning PHP (`memory_limit`, `upload_max_filesize`, …).
2. **`composer`** – immagine `composer`: copia il progetto in `/app` ed esegue
   `composer install --optimize-autoloader` con `platform.php` fissato a 8.2. I pacchetti `pgvirtual/*` sono
   scaricati dai repository privati GitHub `PGKappa/sogei-*` usando il build-arg `GITHUB_TOKEN`
   (da `.env.docker`, passato come `COMPOSER_AUTH`). Il token resta nello stage `composer` e non finisce
   nell'immagine finale.
   Vengono eseguiti anche gli script composer che generano `game-harness` e `game-horses` da `game-dogs`.
3. **finale** – parte da `server` e copia `/app` (sorgenti + `vendor/`) in `/var/www/html`. Espone la `9000`.

**`nginx/Dockerfile`**: `FROM nginx`, copia l'intero progetto in `/var/www/html` (serve per i file
statici di `public/`), poi `nginx/default.conf` e `rootfs/etc/nginx/nginx.conf`.

### 4.4 Avvio e init del container PHP

Il compose avvia `sog-php` con `bash -c "/bin/docker-entrypoint.sh"`, che:

1. esegue tutti gli script eseguibili in `/docker-entrypoint-init.d/`:
   - `01-uname.sh` – test;
   - `start_pgv.sh` – avvia `php-fpm`, `php artisan key:generate`, `chown www-data`, `chmod +x` dei
     `drs_exec.py` di dogs/horses/harness, e registra i servizi runit:
     `result` → `scripts/service-1.sh`, `isibet` → `scripts/service-3.sh`, `syncresult` → `scripts/service-4.sh`;
2. avvia `runsvdir` (runit) sui servizi in `/etc/service`;
3. intercetta SIGTERM/SIGINT per fermare i servizi in modo pulito.

### 4.5 Commit e salvataggio

```sh
docker commit sog-php sog-php:latest
docker commit sog-pgv sog-pgv:latest
docker save -o images/sog-php.tar sog-php:latest
docker save -o images/sog-pgv.tar sog-pgv:latest
```

`docker commit` conserva anche il comando di avvio del container, per questo
`docker-compose-server.yml` non specifica `command:`. La cartella `images/` è in `.gitignore`.

## 5. Esecuzione

### Prerequisiti

- `sogei/.env.docker` con le credenziali (vedi `.env.docker.example`):
  - `GITHUB_TOKEN`: token GitHub con accesso in lettura ai repo `PGKappa/sogei-*` (obbligatorio);
  - `DOCKER_USERNAME` / `DOCKER_PASSWORD`: opzionali, se valorizzati viene fatto `docker login`.

  Il file è in `.gitignore` e in `.dockerignore`.
- Docker con `docker-compose` (v1). Con Docker Compose v2 sostituire con `docker compose` negli script.
- La rete esterna `localstart_default` deve esistere, altrimenti `up` fallisce:
  ```sh
  docker network create localstart_default
  ```
  (viene creata automaticamente avviando l'ambiente locale con `start.sh` da `localstart/`.)
- `.env` di Laravel nella root del repo configurato per il DB di produzione del partner: viene
  incluso nelle immagini.
- Linux: `envsubst` (pacchetto `gettext`).

### Linux / macOS / WSL

```sh
cd sogei
./create.sh
```

### Windows

```powershell
cd sogei
powershell -ExecutionPolicy Bypass -File .\create.ps1
```

Al termine in `sogei/images/` ci sono `sog-php.tar` e `sog-pgv.tar`.

### Verifica locale (opzionale)

```sh
docker ps --filter name=sog-        # sog-php e sog-pgv in esecuzione
docker logs sog-php                 # "end pgv", "Started runsvdir", stato dei servizi
curl -I http://localhost:8083/      # risposta da nginx/Laravel
```

## 6. Deploy sul server

1. Costruire anche l'immagine `sog-viewer` (vedi `README_Aggiorna_Viewer_PARTNER.md`).
2. Copiare sul server `images/*.tar` e `docker-compose-server.yml` (rinominato `docker-compose.yml`),
   come descritto in [`README_NUOVO_PARTNER.md`](../README_NUOVO_PARTNER.md).
3. Sul server:
   ```sh
   docker load -i sog-php.tar
   docker load -i sog-pgv.tar
   docker load -i sog-viewer.tar
   docker-compose up -d
   docker exec -it sog-php php artisan migrate --seed   # solo al primo avvio
   ```

Porte esposte: API `8083`, viewer `8093`.

## 7. Note e problemi noti

- **Contenuto delle immagini**: `.dockerignore` esclude solo `images`, `.git`, `.github`, `.vscode`,
  `localstart`, `rootfs-service`, `localhost`. Tutto il resto — **incluso `.env`** — finisce sia
  nell'immagine PHP sia in quella nginx. Trattare i `.tar` come contenenti credenziali.
- **Permessi su Windows**: `create.ps1` non esegue `imposta-permessi.sh`. Se un container non parte
  per "permission denied" sugli script, verificare che i `.sh` abbiano il bit eseguibile nel repository
  (`git ls-files -s *.sh` → modo `100755`) e che abbiano fine riga LF.
- **Pulizia**: eliminare `images/*.tar` prima di commit/push (sono comunque ignorati da git).
