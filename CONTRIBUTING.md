# Contributing

Thanks for your interest. This project aims to be the simplest way to run many
PHP versions at once for local dev on macOS.

## Ways to help
- **Bug reports** — include: macOS version, chip (Intel/Apple Silicon),
  Docker version, the PHP version involved, and the exact command plus output.
- **Feature ideas** — open an issue first so we can discuss scope.
- **Pull requests** — small, focused PRs are easiest to review.

## Good first issues / ideas
- Add/adjust PHP versions (e.g. 8.3)
- Linux (non-macOS) support notes
- Extend `stack new` (e.g. `--symfony`, or auto-create the database)
- Optional services: Redis, Mailpit, Meilisearch
- CI that builds every PHP image on a schedule

## Dev setup
```bash
git clone https://github.com/mahedihanik/multi-php-devstack.git ~/devstack
cd ~/devstack && cp .env.example .env
docker compose build && docker compose up -d
```

## Guidelines
- Keep the "project = plain folder" philosophy — avoid requiring per-project
  Docker files.
- Don't commit secrets or data. `.env`, `nginx/certs/`, `data/`, and
  project-specific vhosts are gitignored — keep it that way.
- Match the existing style in `docker-compose.yml` and the nginx vhosts.
- Update `README.md` / `DOCUMENTATION.md` when behavior changes.

## Testing a change
After editing configs: `stack rebuild` (or `docker compose up -d --build`),
then verify each affected version serves correctly and connects to MySQL via the
`www/info/` sample.
