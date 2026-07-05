# Devstack — Run 7 PHP versions at once on your Mac

**PHP 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.4 — simultaneously.** One Docker stack with
nginx, MySQL 8, phpMyAdmin, trusted local HTTPS, and clean `.test` domains. Your
projects are just **folders** — no per-project Docker files, no config per app.
Built and tested on **Apple Silicon (arm64)**.

![Shell](https://img.shields.io/badge/shell-bash-121011?logo=gnu-bash&logoColor=white)
![Docker](https://img.shields.io/badge/docker-compose-2496ED?logo=docker&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-7.2--8.4-777BB4?logo=php&logoColor=white)
![Platform](https://img.shields.io/badge/macOS-Apple%20Silicon-000000?logo=apple&logoColor=white)
![License: MIT](https://img.shields.io/badge/license-MIT-green)
![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen)

> Drop a project in `www/`, open `https://myapp.82.test` — done. Need to test the
> same app on 7.4? Open `https://myapp.74.test`. Nothing to reconfigure.

<!-- Add a demo GIF here — see "Record a demo" below. It's the single biggest
     thing for adoption. -->
<!-- ![demo](docs/demo.gif) -->

---

## Why this exists

Switching PHP versions with Homebrew is painful, and EOL versions (7.2/7.3) barely
compile on Apple Silicon. Existing tools each miss something for this use case:

| | Devstack | Laravel Sail | Laradock | Valet | DDEV |
|---|:---:|:---:|:---:|:---:|:---:|
| Many PHP versions at the same time | Yes (7) | No (1/project) | Limited | 1 active | 1/project |
| EOL 7.2/7.3 on Apple Silicon | Yes | No | Limited | No | Limited |
| Project = plain folder (no per-project config) | Yes | No | No | Yes | No |
| Trusted HTTPS + `.test` domains out of the box | Yes | No | Limited | Yes | Yes |
| Shared MySQL + phpMyAdmin for everything | Yes | No | Yes | No | Limited |
| One tiny helper CLI | Yes (`stack`) | Yes (`sail`) | No | Yes | Yes |

**The niche it owns:** legacy-to-modern shops that maintain many apps pinned to
different PHP versions and want them all running together, locally, on an M-series Mac.

---

## Quickstart

```bash
# prerequisites: Docker Desktop + Homebrew
git clone https://github.com/mahedihanik/multi-php-devstack.git ~/devstack
cd ~/devstack
cp .env.example .env

# build the images (first time; if a parallel build fails, build one at a time)
docker compose build

# start everything
docker compose up -d

# put the helper on your PATH
ln -sf ~/devstack/bin/stack /opt/homebrew/bin/stack
```

Open the bundled sample on any version:

- `http://localhost:8072/info/` for PHP 7.2 ... `http://localhost:8084/info/` for PHP 8.4
- phpMyAdmin: `http://localhost:8888` (user `root` / `root`)

To get the pretty `https://<name>.NN.test` URLs, do the two one-time steps in
[section 10 (dnsmasq)](DOCUMENTATION.md#10-clean-test-hostnames-dnsmasq) and
[section 11 (mkcert)](DOCUMENTATION.md#11-trusted-https-mkcert).

---

## Access map

| PHP | Pretty URL (HTTPS) | Zero-setup URL |
|-----|--------------------|----------------|
| 7.2 | `https://<proj>.72.test` | `http://localhost:8072/<proj>/` |
| 7.3 | `https://<proj>.73.test` | `http://localhost:8073/<proj>/` |
| 7.4 | `https://<proj>.74.test` | `http://localhost:8074/<proj>/` |
| 8.0 | `https://<proj>.80.test` | `http://localhost:8080/<proj>/` |
| 8.1 | `https://<proj>.81.test` | `http://localhost:8081/<proj>/` |
| 8.2 | `https://<proj>.82.test` | `http://localhost:8082/<proj>/` |
| 8.4 | `https://<proj>.84.test` | `http://localhost:8084/<proj>/` |

---

## The `stack` command

```bash
stack new <ver> <name> [laravel]     # scaffold a project folder + vhost
stack up | down | restart | rebuild | ps | logs [service]
stack php <ver> <project> ...        # e.g. stack php 8.2 myapp -v
stack composer <ver> <project> ...   # e.g. stack composer 7.4 shop install
stack artisan <ver> <project> ...    # e.g. stack artisan 8.2 myapp migrate
stack sh <ver>                       # shell into a PHP container
stack mysql                          # mysql client
```

---

## Add a project

Fastest way — let the scaffolder create the folder and vhost for you:
```bash
stack new 8.2 myapp            # plain PHP project  -> https://myapp.82.test
stack new 7.4 shop laravel     # Laravel (root = /public) on PHP 7.4
```
Then drop your code in `www/myapp/` and open the URL it prints.

Prefer to do it by hand? Copy the template and edit it:
```bash
cp nginx/conf.d/laravel-example.conf.example nginx/conf.d/myapp.local.conf
# edit server_name + root + php version, then:
stack restart
```

Full walkthrough (Composer, `.env`, DB, storage, gotchas): see
**[DOCUMENTATION.md](DOCUMENTATION.md)**.

---

## Record a demo (recommended)

A short terminal GIF massively boosts adoption. Easiest options:
```bash
brew install vhs          # scripted, reproducible GIFs
#   or
brew install asciinema    # record a real session
```
Record `stack up`, hitting several versions, and phpMyAdmin; export to
`docs/demo.gif`, and uncomment the image near the top of this README.

---

## Contributing

Issues and PRs welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Ideas: more PHP
versions, Linux support, optional Redis/Mailpit services.

## License

[MIT](LICENSE) (c) Mahedi Hasan Anik
