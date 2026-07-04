# devstack — multi-PHP dev environment

Runs PHP **7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.4** simultaneously, sharing one
nginx, one MySQL 8.0, and phpMyAdmin. Projects are plain folders in `www/` —
nothing gets added to your project code.

## Daily use

```bash
stack up        # start everything
stack ps        # see what's running
stack down      # stop
```

## Adding a project

1. Put the code in `~/devstack/www/<name>/`  (e.g. `~/devstack/www/shop/`)
2. Open it on whichever PHP version you want:

| Access | PHP |
|--------|-----|
| http://shop.72.test  or  http://localhost:8072/shop/ | 7.2 |
| http://shop.73.test  or  http://localhost:8073/shop/ | 7.3 |
| http://shop.74.test  or  http://localhost:8074/shop/ | 7.4 |
| http://shop.80.test  or  http://localhost:8080/shop/ | 8.0 |
| http://shop.81.test  or  http://localhost:8081/shop/ | 8.1 |
| http://shop.82.test  or  http://localhost:8082/shop/ | 8.2 |
| http://shop.84.test  or  http://localhost:8084/shop/ | 8.4 |

The `.test` hostnames need the dnsmasq step below (one-time). The
`localhost:PORT` URLs work immediately with no DNS setup.

### Laravel / Symfony projects
Their web root is `/public`. Edit the matching `nginx/conf.d/phpNN.conf`,
change `root /var/www/html/$sitename;` to `root /var/www/html/$sitename/public;`
then `stack restart`.

## Composer / CLI per version

```bash
stack composer 7.4 shop install      # composer inside PHP 7.4
stack php 8.2 shop -v                 # php CLI at 8.2 in that project
stack artisan 8.1 shop migrate        # laravel
stack sh 8.0                          # shell into the 8.0 container
```

## Database

- Host from your Mac: `127.0.0.1:3306`
- Host from inside a project (in PHP): `mysql:3306`
- Root user/pass: `root` / `root`  (see `.env`)
- Prewired db/user: `app` / `app` / `app`
- phpMyAdmin: http://localhost:8888
- CLI: `stack mysql`

## Sharing this MySQL with a separate dockerized project

If you have another project with its own `docker-compose.yml` (e.g. a
dockerized Laravel app), you can drop its own db container and use this one.

**Option A — via host:** in the project's Laravel `.env`
```
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=mylaravel
DB_USERNAME=app
DB_PASSWORD=app
```
(add `extra_hosts: ["host.docker.internal:host-gateway"]` to its app service
if the host isn't resolvable.)

**Option B — join this network** (then `DB_HOST=mysql`):
```yaml
services:
  app:
    networks: [default, devstack]
networks:
  devstack:
    external: true
    name: devstack_devstack
```

Create the db first:
```bash
stack mysql
# then: CREATE DATABASE mylaravel; GRANT ALL ON mylaravel.* TO 'app'@'%'; FLUSH PRIVILEGES;
```

Note: sharing means the project now depends on devstack being up. Switch
`DB_HOST` back to its own db service to make it self-contained again.

## Clean `.test` hostnames (one-time, optional)
See the dnsmasq section in the setup notes, or just use the `localhost:PORT`
URLs which need no setup.

## Files
- `docker-compose.yml` — all services
- `php/Dockerfile` — one image built per version (composer + common extensions + xdebug)
- `php/conf.d/zz-devstack.ini` — shared PHP settings (upload size, memory, xdebug off)
- `nginx/conf.d/phpNN.conf` — one vhost per version
- `www/` — your projects
- `data/mysql/` — persistent database files
