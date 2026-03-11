# Laravel Production Deployment on Hetzner

This repository now ships with a production-ready Docker setup for the target server layout below:

- Ubuntu on Hetzner
- Application path: `/var/www/flocksense-backend`
- PostgreSQL running directly on the server
- Docker services:
  - `app`: PHP 8.2 FPM
  - `nginx`: reverse proxy on port `80`
- External Docker network: `appnet`

## 1. Server bootstrap

Install Docker Engine and the Compose plugin on the server, then create the application directory:

```bash
mkdir -p /var/www/flocksense-backend
docker network create appnet
```

Create the production environment file at `/var/www/flocksense-backend/.env`. At minimum:

```dotenv
APP_NAME=FlockSense
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-domain-or-ip

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=host.docker.internal
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
FILESYSTEM_DISK=local
```

`DB_HOST=127.0.0.1` will not work here because Laravel runs inside Docker. From the `app` container, `127.0.0.1` points to the container itself, not the Hetzner host machine. This repository maps `host.docker.internal` to Docker's host gateway for the `app` service, so the container can reach PostgreSQL installed directly on the server.

PostgreSQL must also accept connections from Docker containers:

```conf
# postgresql.conf
listen_addresses = '*'
```

```conf
# pg_hba.conf
host    all    all    172.17.0.0/16    md5
```

Adjust the subnet if your Docker bridge network uses a different CIDR on the server, then restart PostgreSQL.

Generate an application key once on the server after the first code sync:

```bash
cd /var/www/flocksense-backend
docker compose run --rm --user root app composer install --no-dev --prefer-dist --optimize-autoloader
docker compose run --rm app php artisan key:generate
```

## 2. Container layout

- [`Dockerfile`](/Users/techling/Code/Personal/flocksense/BankEnd/Dockerfile) builds a PHP 8.2 FPM image with the required PostgreSQL and Laravel extensions.
- [`docker-compose.yml`](/Users/techling/Code/Personal/flocksense/BankEnd/docker-compose.yml) runs `app` and `nginx` on the shared external network.
- [`docker/nginx/default.conf`](/Users/techling/Code/Personal/flocksense/BankEnd/docker/nginx/default.conf) serves Laravel from `/public` and forwards PHP requests to `app:9000`.
- [`deploy/deploy.sh`](/Users/techling/Code/Personal/flocksense/BankEnd/deploy/deploy.sh) performs the full production rollout.

## 3. CI/CD flow

The GitHub Actions workflow is in [`deploy.yml`](/Users/techling/Code/Personal/flocksense/BankEnd/.github/workflows/deploy.yml).

Required secrets:

- `SERVER_HOST`
- `SERVER_USER`
- `SERVER_SSH_KEY`
- `SERVER_PORT`

Workflow behavior:

- copies the repository to `/var/www/flocksense-backend`
- runs the remote deploy script
- installs Composer dependencies
- builds Vite assets
- rebuilds and restarts containers
- runs migrations with `--force`
- refreshes Laravel caches
- verifies `/api/health`

## 4. Health endpoint

`GET /api/health` now reports:

- database connectivity
- cache round-trip status
- storage path writability and disk usage

Use it for uptime checks and post-deploy verification.
