# SalesFlow CRM

SalesFlow CRM is a modular-monolith Laravel application for managing leads, customer relationships, opportunities, sales pipelines, activities and performance reporting. Delivery is intentionally phased; see [PROJECT_PHASES.md](PROJECT_PHASES.md) for the exact checkpoint status.

## Current checkpoint

Phase 1 establishes Laravel 12, Livewire 3, Flux UI, Fortify, Sanctum, PostgreSQL, Redis, Horizon, Reverb and the Docker runtime. CRM modules are added only after the current phase is accepted.

## Stack

- PHP 8.5 (project requires PHP 8.3+) / Laravel 12
- Blade / Livewire 3 / Flux UI Free / Alpine.js / Tailwind CSS 4 / Vite
- PostgreSQL 17 / Redis 7
- Fortify / Sanctum / Horizon / Reverb
- Pest / Pint / Larastan
- Nginx / Mailpit / MinIO / Docker Compose

## Start with Docker

Prerequisites: Docker Desktop with WSL 2 integration enabled (Windows) or Docker Engine + Compose on Linux.

```bash
cp .env.example .env
docker compose build app
docker compose run --rm --user "$(id -u):$(id -g)" app php artisan key:generate
docker compose up -d --build
docker compose exec app php artisan migrate --seed
docker compose ps
```

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

The runtime image contains built frontend assets. If running `npm run build` interactively is required, use a Node container until a dedicated development override is added:

```bash
docker run --rm -u 1000:1000 -v "$PWD:/app" -w /app node:22-alpine sh -lc "npm install && npm run build"
```

## Architecture

- [Architecture, ERD and delivery estimate](docs/architecture.md)
- [Permission matrix](docs/permissions.md)
- [Database decisions](docs/database.md)
- [API foundation](docs/api.md)
- [Deployment notes](docs/deployment.md)

## Docker services

The Compose topology includes `app`, `nginx`, `postgres`, `redis`, `horizon`, `scheduler`, `reverb`, `mailpit` and `minio`. App processes share one PHP image, while Nginx uses the immutable Vite output copied from the asset build stage.

## License

MIT. Flux UI is used through its free package only; no Flux Pro source or components are included.
