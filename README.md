# SalesFlow CRM

SalesFlow CRM is a modular-monolith Laravel application for managing leads, customer relationships, opportunities, sales pipelines, activities and performance reporting. Delivery is intentionally phased; see [PROJECT_PHASES.md](PROJECT_PHASES.md) for the exact checkpoint status.

## Current checkpoint

Phase 2 has implemented Departments, Users, the five-role RBAC catalog, backend data scopes, audit logs and realtime audit updates. The project is stopped at checkpoint P2-08 for authorization acceptance before Lead development begins.

## Stack

- PHP 8.5 (project requires PHP 8.3+) / Laravel 12
- Blade / Livewire 3 / Flux UI Free / Alpine.js / Tailwind CSS 4 / Vite
- PostgreSQL 17 / Redis 7
- Fortify / Sanctum / Horizon / Reverb
- Pest / Pint / Larastan
- Nginx / Mailpit / MinIO / Docker Compose

## Start local development with Docker

Prerequisites: Docker Desktop with WSL 2 integration enabled (Windows) or Docker Engine + Compose on Linux.

```bash
cp .env.example .env
docker compose build app
docker compose run --rm --user "$(id -u):$(id -g)" app php artisan key:generate
docker compose up -d --build
docker compose exec app php artisan migrate --seed
docker compose ps
```

`compose.override.yaml` is loaded automatically for local development. It bind-mounts the source code into PHP/Nginx, enables timestamp-aware OPcache and starts Vite at `http://localhost:5173`, so changes work as follows:

- PHP, routes, Livewire and Blade: available immediately; refresh the browser if the page does not reload itself.
- Tailwind CSS and JavaScript: updated by Vite HMR without rebuilding the PHP or Nginx image.
- Internal navigation uses Livewire Navigate with hover prefetch, so Dashboard and Departments switch without a full browser reload.
- Composer or system dependency changes: run `docker compose build app nginx` and recreate the containers.
- NPM dependency changes: restart `vite`; it runs `npm ci` only when `package-lock.json` has changed.

For normal daily development, use only:

```bash
docker compose up -d
docker compose logs -f vite
```

Do not add `--build` after editing application code. The immutable, production-like stack can still be started without the local override by using `docker compose -f compose.yaml up -d --build`.

Application endpoints:

- CRM: http://localhost
- Health: http://localhost/up
- Mailpit: http://localhost:8025
- MinIO console: http://localhost:9001
- Horizon: http://localhost/horizon (local environment only during Phase 1)

Demo account after seeding:

```text
Email: admin@salesflow.test
Password: SalesFlow@123
```

This credential is development-only. Production users must be provisioned securely and secrets must be injected through the deployment environment.

## Connect with DBeaver

Use the PostgreSQL driver with these development settings:

```text
Host: localhost
Port: 15432
Database: salesflow
Username: salesflow
Password: change-me
SSL mode: disable
```

`DB_PORT=5432` is the internal Docker port used by Laravel. `DB_FORWARD_PORT=15432` is the host port used by DBeaver.

## Quality checks

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app composer audit --no-interaction
```

The runtime image contains built frontend assets. The local override runs Vite automatically; a production asset build can still be verified with:

```bash
docker compose exec vite npm run build
```

## Architecture

- [Architecture, ERD and delivery estimate](docs/architecture.md)
- [Permission matrix](docs/permissions.md)
- [Database decisions](docs/database.md)
- [API foundation](docs/api.md)
- [Deployment notes](docs/deployment.md)

## Docker services

The Compose topology includes `app`, `nginx`, `vite`, `postgres`, `redis`, `horizon`, `scheduler`, `reverb`, `mailpit` and `minio`. App processes share one PHP image. Local development uses bind mounts and Vite HMR; the base Compose file keeps the immutable built assets for deployment-like runs.

## License

MIT. Flux UI is used through its free package only; no Flux Pro source or components are included.
