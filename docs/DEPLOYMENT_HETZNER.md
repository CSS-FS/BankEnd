# Laravel Production Deployment on Hetzner

This repository now ships with a production-ready Docker setup for the target server layout below:

- Ubuntu on Hetzner
- Application path: `/var/www/flocksense-backend`
- PostgreSQL running directly on the server
- Docker services:
  - `app`: PHP 8.4 FPM
  - `nginx`: reverse proxy on port `80`
- External Docker network: `appnet`

## 1. Server bootstrap

Install Docker Engine and the Compose plugin on the server, then create the application directory:

```bash
mkdir -p /var/www/flocksense-backend
docker network create appnet
```

Create the production environment file content for GitHub Actions as the `ENV_PRODUCTION_VALUES` repository secret. The workflow will write it to `/var/www/flocksense-backend/.env` on each deploy. At minimum:

```dotenv
APP_NAME=FlockSense
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-server-domain-or-ip
TRUSTED_PROXIES=*

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

If your public site is served over HTTPS through a reverse proxy, `APP_URL` must also use `https://`. This application now trusts forwarded proxy headers, so Laravel can correctly generate secure asset and login URLs from inside Docker.

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

The application database user must also be able to use and create objects in schema `public`. Run this once on the server as the `postgres` superuser, replacing the placeholders with your real database and role names:

```sql
ALTER DATABASE your_database OWNER TO your_username;
\c your_database
GRANT CONNECT ON DATABASE your_database TO your_username;
GRANT USAGE, CREATE ON SCHEMA public TO your_username;
ALTER SCHEMA public OWNER TO your_username;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO your_username;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO your_username;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO your_username;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO your_username;
```

If the role is intentionally restricted and you do not want it to own the database, the minimum requirement for Laravel migrations is `USAGE, CREATE` on schema `public` plus normal DML privileges on the application tables.

Generate an application key once on the server after the first code sync:

```bash
cd /var/www/flocksense-backend
docker compose run --rm --user root app composer install --no-dev --prefer-dist --optimize-autoloader
docker compose run --rm app php artisan key:generate
```

## 2. Container layout

- [`Dockerfile`](/Users/techling/Code/Personal/flocksense/BankEnd/Dockerfile) builds a PHP 8.4 FPM image with the required PostgreSQL and Laravel extensions.
- [`docker-compose.yml`](/Users/techling/Code/Personal/flocksense/BankEnd/docker-compose.yml) runs `app` and `nginx` on the shared external network.
- [`docker/nginx/default.conf`](/Users/techling/Code/Personal/flocksense/BankEnd/docker/nginx/default.conf) serves Laravel from `/public` and forwards PHP requests to `app:9000`.
- [`deploy/deploy.sh`](/Users/techling/Code/Personal/flocksense/BankEnd/deploy/deploy.sh) performs the full production rollout.

## 3. CI/CD flow

The GitHub Actions workflow is in [`deploy.yml`](/Users/techling/Code/Personal/flocksense/BankEnd/.github/workflows/deploy.yml).

Required secrets:

- `ENV_PRODUCTION_VALUES`
- `SERVER_HOST`
- `SERVER_USER`
- `SERVER_SSH_KEY`
- `SERVER_PORT`

Workflow behavior:

- builds frontend assets in GitHub Actions
- copies the repository to `/var/www/flocksense-backend`
- writes `/var/www/flocksense-backend/.env` from `ENV_PRODUCTION_VALUES`
- runs the remote deploy script
- installs Composer dependencies
- uses the prebuilt frontend bundle and falls back to server-side asset build only if needed
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

With the current production Compose mapping, access points are:

- Admin login: `http://your-server-domain-or-ip:8000/login`
- Admin dashboard: `http://your-server-domain-or-ip:8000/admin/dashboard`
- Health check: `http://your-server-domain-or-ip:8000/api/health`
