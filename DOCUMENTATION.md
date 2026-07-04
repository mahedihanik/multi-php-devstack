# Devstack — Multi‑PHP Local Development Environment
### Complete step‑by‑step documentation

**Machine:** Apple Silicon (arm64) Mac · **Built:** July 2026
**Location:** `~/devstack`

This document records, in order, everything that was set up: a Docker‑based
environment running **7 PHP versions at once** (7.2 – 8.4) with a shared
MySQL 8.0, phpMyAdmin, Node (via fnm), clean `.test` hostnames, trusted HTTPS,
and a real Laravel project (`wagebook`) running on it.

---

## Table of contents
1. [What was already on the machine](#1-what-was-already-on-the-machine)
2. [Design decisions](#2-design-decisions)
3. [Directory structure](#3-directory-structure)
4. [The PHP images (7.2–8.4)](#4-the-php-images-7284)
5. [docker-compose stack](#5-docker-compose-stack)
6. [nginx routing](#6-nginx-routing)
7. [Building & starting](#7-building--starting)
8. [The `stack` helper command](#8-the-stack-helper-command)
9. [Node.js via fnm](#9-nodejs-via-fnm)
10. [Clean `.test` hostnames (dnsmasq)](#10-clean-test-hostnames-dnsmasq)
11. [Trusted HTTPS (mkcert)](#11-trusted-https-mkcert)
12. [HTTP → HTTPS redirect](#12-http--https-redirect)
13. [Running the wagebook Laravel project](#13-running-the-wagebook-laravel-project)
14. [Daily usage cheat sheet](#14-daily-usage-cheat-sheet)
15. [Adding a new project](#15-adding-a-new-project)
16. [Troubleshooting & gotchas](#16-troubleshooting--gotchas)
17. [Outstanding manual steps](#17-outstanding-manual-steps)

---

## 1. What was already on the machine
- **Homebrew** ✓
- **Docker Desktop** ✓ (had to be *started* — the daemon wasn't running)
- **No** PHP, MySQL, Composer, or Node yet (clean slate)

Verified native `arm64` builds exist for all PHP images (7.2–8.4), so no
emulation is needed.

---

## 2. Design decisions
| Question | Choice | Why |
|---|---|---|
| How to manage 7 PHP versions | **Docker, one shared stack** | EOL versions (7.2/7.3) "just work"; matches prod; run all at once |
| Do projects get dockerized individually | **No** | Projects are plain folders in `www/`; nginx routes to the right PHP |
| Location | `~/devstack` | self‑contained |
| Node | **fnm** | fast, switch Node per project |
| Database | **MySQL 8.0** | modern, compatible with legacy apps |

Key idea: **one nginx + one MySQL + 7 PHP‑FPM containers.** A project is just a
folder; the PHP version is chosen by the URL you use.

---

## 3. Directory structure
```
~/devstack/
├── docker-compose.yml          # all services
├── .env                        # MySQL creds + timezone
├── php/
│   ├── Dockerfile              # one image, reused per version via build arg
│   └── conf.d/zz-devstack.ini  # shared PHP settings (upload size, xdebug off…)
├── nginx/
│   ├── conf.d/                 # one vhost per version + per-project vhosts
│   └── certs/                  # mkcert TLS cert + key
├── www/                        # ← your projects live here
│   ├── info/                   # sample project (phpinfo + DB check)
│   └── wagebook/               # the Laravel app
├── data/mysql/                 # persistent MySQL data (survives restarts)
├── bin/stack                   # helper CLI (symlinked to /opt/homebrew/bin/stack)
├── README.md
└── DOCUMENTATION.md            # this file
```

---

## 4. The PHP images (7.2–8.4)

A **single `php/Dockerfile`** is reused for every version via a build arg. It
uses `mlocati/php-extension-installer`, which handles extension/version/arch
differences uniformly across 7.2 → 8.4:

```dockerfile
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-fpm
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
      pdo_mysql mysqli gd zip intl bcmath mbstring opcache exif pcntl soap xdebug @composer
WORKDIR /var/www/html
```

Every version therefore includes: **Composer**, common extensions, and
**Xdebug** (installed but `mode=off` by default — see `php/conf.d/zz-devstack.ini`).

Versions built: **7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.4** (no 8.3).

> ⚠️ **Gotcha:** building all 7 in parallel exhausts memory and randomly fails
> (`EOF` / killed). Fix: build failing versions **one at a time**
> (`docker compose build php81`).

---

## 5. docker-compose stack

Services (`~/devstack/docker-compose.yml`):
- `php72 … php84` — 7 PHP‑FPM containers, each built from the shared Dockerfile
- `nginx` — routes requests to the right PHP version
- `mysql` — MySQL 8.0, data persisted to `./data/mysql`
- `phpmyadmin` — web DB admin

Credentials live in `~/devstack/.env`:
```
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=app
MYSQL_USER=app
MYSQL_PASSWORD=app
TZ=UTC
```

---

## 6. nginx routing

Each version has a vhost in `nginx/conf.d/phpNN.conf` with three server blocks:
1. **port 80** → 301 redirect to HTTPS (for `*.NN.test`)
2. **port 443** → serves the project over TLS
3. **`localhost:80NN`** → plain‑HTTP no‑DNS fallback

Access map:

| PHP | HTTPS hostname | Plain‑HTTP fallback |
|-----|----------------|---------------------|
| 7.2 | `https://<proj>.72.test` | `http://localhost:8072/<proj>/` |
| 7.3 | `https://<proj>.73.test` | `http://localhost:8073/<proj>/` |
| 7.4 | `https://<proj>.74.test` | `http://localhost:8074/<proj>/` |
| 8.0 | `https://<proj>.80.test` | `http://localhost:8080/<proj>/` |
| 8.1 | `https://<proj>.81.test` | `http://localhost:8081/<proj>/` |
| 8.2 | `https://<proj>.82.test` | `http://localhost:8082/<proj>/` |
| 8.4 | `https://<proj>.84.test` | `http://localhost:8084/<proj>/` |

Other services: **phpMyAdmin** `http://localhost:8888` · **MySQL** `127.0.0.1:3306`

---

## 7. Building & starting

```bash
# 1. Start Docker Desktop (must be running)
open -a Docker

# 2. Build the 7 PHP images (build one at a time if parallel fails)
cd ~/devstack && docker compose build

# 3. Start everything
docker compose up -d
```

Verified: every version reports the correct `PHP x.y.z` and connects to
`MySQL 8.0.46` (tested via the `info` sample project).

---

## 8. The `stack` helper command

`~/devstack/bin/stack`, symlinked to `/opt/homebrew/bin/stack` (on PATH):

```bash
stack up                       # start everything
stack down                     # stop
stack restart                  # restart containers
stack rebuild                  # rebuild PHP images + restart
stack ps                       # status
stack logs [service]           # tail logs

stack php <ver> <project> ...      # run php in a project    e.g. stack php 8.2 wagebook -v
stack composer <ver> <project> ... # composer               e.g. stack composer 7.4 shop install
stack artisan <ver> <project> ...  # laravel artisan        e.g. stack artisan 8.2 wagebook migrate
stack sh <ver>                     # shell into a PHP container
stack mysql                        # mysql client as root
```
(`<ver>` accepts `8.2` or `82`.)

---

## 9. Node.js via fnm

```bash
brew install fnm
# added to ~/.zshrc:
eval "$(fnm env --use-on-cd --version-file-strategy=recursive)"
fnm install --lts        # installed Node v24 LTS
```
Node runs **on the host** (not in the PHP containers). `--use-on-cd` auto‑switches
Node version when a project has a `.node-version`/`.nvmrc` file.

---

## 10. Clean `.test` hostnames (dnsmasq)

Makes every `*.test` domain resolve to `127.0.0.1` with no `/etc/hosts` edits.

```bash
brew install dnsmasq
# config written:
#   /opt/homebrew/etc/dnsmasq.d/devstack-test.conf  ->  address=/.test/127.0.0.1
#   (conf-dir line added to /opt/homebrew/etc/dnsmasq.conf)
```

**Manual step (needs sudo — run in a real Terminal):**
```bash
sudo brew services start dnsmasq
sudo mkdir -p /etc/resolver
echo "nameserver 127.0.0.1" | sudo tee /etc/resolver/test
sudo dscacheutil -flushcache && sudo killall -HUP mDNSResponder
```
✅ Done and verified — `info.82.test` etc. resolve to `127.0.0.1`. Survives reboots.

---

## 11. Trusted HTTPS (mkcert)

```bash
brew install mkcert nss
# one cert covering every version's wildcard + localhost:
cd ~/devstack/nginx/certs
mkcert -cert-file devstack.pem -key-file devstack-key.pem \
  "*.72.test" "*.73.test" "*.74.test" "*.80.test" "*.81.test" "*.82.test" "*.84.test" \
  localhost 127.0.0.1
```
- Cert + key are mounted into nginx at `/etc/nginx/certs/` (port 443 exposed).
- Valid until **2028**; wildcards are **one level deep** (`a.82.test` ✓, `a.b.82.test` ✗).

**Manual step (needs sudo — run in a real Terminal), to trust the CA in browsers:**
```bash
mkcert -install
```
Then **fully quit and reopen the browser**.

> If the browser says "Not secure", it's almost always because `mkcert -install`
> hasn't been run — verify with:
> `security find-certificate -c "mkcert" /Library/Keychains/System.keychain`

---

## 12. HTTP → HTTPS redirect

Every `.test` vhost returns **301 → https** on port 80; the `localhost:PORT`
fallbacks stay plain HTTP (no TLS on those ports by design).

Verified:
- `http://wagebook.82.test` → `301` → `https://wagebook.82.test` → `200`
- `http://localhost:8082/info/` → stays `200` (plain HTTP)

---

## 13. Running the wagebook Laravel project

**Project:** `wagebook-hrm` (Laravel 9, requires PHP ^8.2), from Azure DevOps.

### Steps performed
```bash
# 1. Clone into the www folder (auth via Azure DevOps PAT)
cd ~/devstack/www
git clone https://…/wagebook-hrm wagebook

# 2. Install dependencies at PHP 8.2
#    (had to disable Composer's advisory blocker for old dompdf/browsershot)
docker compose exec -T -w /var/www/html/wagebook php82 sh -c \
  'composer config policy.advisories.block false && composer install --no-interaction'
#    then restored composer.json so git tree stayed clean

# 3. nginx vhost -> wagebook.82.test -> /public  (nginx/conf.d/wagebook.conf)

# 4. Create the database
stack mysql   ->   CREATE DATABASE wagebook; GRANT ALL ON wagebook.* TO 'app'@'%';

# 5. .env (local, gitignored) — key points:
APP_URL=http://wagebook.82.test
DB_HOST=mysql
DB_DATABASE=wagebook
DB_USERNAME=app
DB_PASSWORD=app
#    then: php artisan key:generate

# 6. Import the full DB dump (NOT fresh migrations — see gotcha)
docker exec -i devstack-mysql-1 mysql -uroot -proot wagebook \
  < "~/Downloads/asl_employee_db_new_latest (26).sql"     # 126 tables

# 7. Create the storage dirs that are gitignored, make writable
mkdir -p storage/framework/{sessions,views,cache/data} storage/logs storage/app/public bootstrap/cache
chmod -R 777 storage bootstrap/cache

# 8. Clear caches
docker compose exec -T -w /var/www/html/wagebook php82 sh -c \
  'php artisan config:clear; php artisan cache:clear; php artisan view:clear'
```

✅ Result: `https://wagebook.82.test` → "Sign In - WageBook" (HTTP 200, PHP 8.2.32).

### wagebook‑specific gotchas
- **Do NOT run migrations from scratch** — the migration set is order‑broken
  (foreign keys reference `users.emp_id` before it exists). Use a **DB dump**.
- `AppServiceProvider::boot()` runs **DB queries on every request/command**
  (PayrollSettings, EmployeeProvidentFunds, EmployeeDocuments) — nothing works
  until the DB is populated.
- Composer fails under the modern advisory blocker → `composer config
  policy.advisories.block false`.
- `storage/framework/*` subdirs are gitignored → recreate after clone.

---

## 14. Daily usage cheat sheet

```bash
stack up                 # morning: start the stack
stack ps                 # what's running
stack down               # end of day: stop (data persists)

# wagebook
open https://wagebook.82.test
stack artisan 8.2 wagebook migrate:status
stack composer 8.2 wagebook install

# database
open http://localhost:8888          # phpMyAdmin (root/root or app/app)
stack mysql                          # CLI
```

---

## 15. Adding a new project

**Plain PHP project:**
1. Put code in `~/devstack/www/<name>/`
2. Open `https://<name>.82.test` (or any version), or `http://localhost:8082/<name>/`

**Laravel/Symfony project** (web root is `/public`):
1. Put code in `~/devstack/www/<name>/`
2. Copy `nginx/conf.d/wagebook.conf` → `<name>.conf`, change `server_name`,
   `root .../public`, and `php82` → desired version
3. `stack restart`
4. `stack composer <ver> <name> install`, set up `.env` (`DB_HOST=mysql`),
   create/import the DB
5. Open `https://<name>.82.test`

HTTPS works automatically for any new `*.NN.test` host — no cert regeneration.

---

## 16. Troubleshooting & gotchas

| Symptom | Cause / Fix |
|---|---|
| `Cannot connect to the Docker daemon` | Docker Desktop not running → `open -a Docker` |
| PHP image build fails randomly (`EOF`) | Parallel build OOM → build one at a time |
| `bind: address already in use` | Another project uses the same host port → change its host port or `stack down` |
| Browser "Not secure" on `*.test` | `mkcert -install` not run, or browser not restarted |
| "Not secure" on `localhost:PORT` | Expected — those are plain HTTP by design; use the `.test` hostname |
| Laravel 500 `Failed to open stream: .../sessions` | `storage/framework/*` missing → `mkdir -p` + `chmod -R 777 storage bootstrap/cache` |
| Laravel DB errors at boot | DB empty/not imported, or `.env` `DB_HOST` wrong (should be `mysql`) |
| Composer refuses to install old packages | `composer config policy.advisories.block false` |
| `git` not found inside container | Run git on the **host** (`cd ~/devstack/www/<proj> && git …`) |

---

## 17. Outstanding manual steps

These require your admin password, so they must be run by you in a **real
Terminal** (not the `!` prefix, which can't prompt for a password):

1. **Trust the HTTPS CA** (fixes "Not secure"):
   ```bash
   mkcert -install
   ```
   then fully quit & reopen your browser.

2. (Already done) dnsmasq activation — see §10, in case of a fresh setup.

### Security reminder
An Azure DevOps **Personal Access Token** was pasted into the chat during setup.
**Rotate/revoke it** (Azure DevOps → avatar → Personal access tokens). Future
clones/pulls will use the macOS Keychain after authenticating once in a terminal.

---

*End of documentation.*
