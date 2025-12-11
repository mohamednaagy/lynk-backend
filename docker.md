# Docker Deployment Guide

This document describes the Docker-based deployment strategy for the Lynk Laravel API application.

## a) Overview

### Architecture Summary

This setup implements a production-ready, containerized Laravel API with separated concerns:

- **Web Layer**: Nginx reverse proxy (local dev only) → PHP-FPM application container
- **Queue Workers**: Three independent worker groups processing different queue types on separate servers
- **Infrastructure**: Redis for queues, MySQL for database (containerized locally, external in prod)
- **Development Tools**: phpMyAdmin, MailHog, Browserless Chrome (local only)

### Key Principles

1. **One Process Per Container**: Each container runs a single process (nginx, php-fpm, or queue worker)
2. **Worker Separation**: Worker groups are isolated by queue responsibility and can run on different servers
3. **Health Checks Everywhere**: All long-running containers include health checks for monitoring and orchestration
4. **Immutable Images in Production**: Production/pre-production uses immutable images; no bind mounts
5. **Flexible Scaling**: Worker replica counts can be adjusted per server using docker compose scaling

### Queue Groups

**Group 1 - Local Market Core Processing (Server A):**
- `worker-local-market`: local_market, local_market_states, local_market_process
- `worker-order-initiation`: local_market_order_initiation
- `worker-buy-commodities`: buy_commodities_local_market_orders
- `worker-complete-commodities`: complete_commodities_purchased_local_market

**Group 2 - General & Trader Operations (Server B):**
- `worker-default`: default, notifications, local_market_webhooks, create_trader_orders, local_market_eligible_quantities
- `worker-trader-initiation`: trader_order_initiation
- `worker-hold-units`: hold_eligible_units_local_market_orders (with backoff)
- `worker-settlement`: local_market_commodities_settlement

**Group 3 - Supporting Operations & External Integrations (Server C):**
- `worker-inventory-logging`: local_market_order_inventories_units_logging
- `worker-refresh-eligibilities`: refresh_eligibilities
- `worker-order-fees`: apply_order_fees
- `worker-external-integrations`: bursam, unit-inventory, expire_trader_order (with backoff)

## b) File Map

```
docker/
├── laravel/
│   └── Dockerfile                      # Multi-stage PHP 8.2-fpm image with all required extensions
└── compose/
    ├── docker-compose.base.yml         # Base app service (php-fpm), shared by all environments
    ├── docker-compose.local.yml        # Local dev infrastructure (nginx, redis, mysql, tools)
    ├── docker-compose.scheduler.yml    # Laravel task scheduler (run on ONE server only)
    ├── docker-compose.group1.yml       # Worker group 1 (local_market queues)
    ├── docker-compose.group2.yml       # Worker group 2 (default, bursam queues)
    └── docker-compose.group3.yml       # Worker group 3 (expire, inventory queues)
docker.md                                # This documentation file
```

### File Purposes

**docker/laravel/Dockerfile**
- Multi-stage build: composer dependencies + runtime image
- Base: `php:8.2-fpm-alpine`
- Installs: pdo_mysql, mbstring, bcmath, intl, zip, gd, opcache, redis
- Non-root user: `www-data` (uid/gid 1000)
- Production-optimized OPcache configuration
- Exposes port 9000 for FastCGI

**docker/compose/docker-compose.web.yml**
- Defines the core `app` service (php-fpm)
- Shared by all deployment scenarios
- Reads `.env` file for application configuration

**docker/compose/docker-compose.local.yml**
- Local development infrastructure services
- Overrides `app` with bind mount for live code editing
- Provides: nginx (8080), redis (6379), mysql (3306), phpMyAdmin (8081), MailHog (8025), Browserless (3002)
- All services include health checks

**docker/compose/docker-compose.scheduler.yml**
- Defines Laravel's task scheduler service (`php artisan schedule:work`)
- Should be deployed on ONLY ONE server in production to prevent duplicate scheduled jobs
- Can be used in local development or combined with any worker group in production
- Includes health check

**docker/compose/docker-compose.group[1-3].yml**
- Each file defines multiple workers for a specific functional area
- Group 1: 4 workers for local market core processing
- Group 2: 4 workers for general operations and trader processing
- Group 3: 4 workers for supporting operations and external integrations
- Each worker is a separate container with its own queue configuration
- Development mode: uncomment volumes for live code reload
- Production mode: use immutable images (no volumes)

## c) Build & Run (Local Development)

### Prerequisites

- Docker 20.10+ and Docker Compose 2.0+
- Git repository cloned
- `.env` file configured (copy from `.env.example`)

### Build the Base Image

From the repository root:

```bash
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .
```

### Start Base Application + Local Infrastructure

Start the app, nginx, redis, mysql, and dev tools:

```bash
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.local.yml \
               up -d
```

Access points:
- Application: http://localhost:8080
- Nginx health: http://localhost:8080/healthz
- phpMyAdmin: http://localhost:8081
- MailHog: http://localhost:8025
- Browserless: http://localhost:3002

### Start Worker Groups (Local)

Enable volumes for local development by editing the group compose files:

1. Uncomment the `volumes` section in `docker-compose.group[1-3].yml`
2. Start one or more groups:

**Group 1 only:**
```bash
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.local.yml \
               -f docker/compose/docker-compose.group1.yml \
               up -d
```

**All groups:**
```bash
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.local.yml \
               -f docker/compose/docker-compose.group1.yml \
               -f docker/compose/docker-compose.group2.yml \
               -f docker/compose/docker-compose.group3.yml \
               up -d
```

### Scale Worker Groups Locally

To run more replicas of a specific group:

```bash
docker compose -f docker/compose/docker-compose.group1.yml up -d --scale worker-group1=4
```

Or edit the `deploy.replicas` value in the compose file and restart.

### Stop All Services

```bash
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.local.yml \
               -f docker/compose/docker-compose.group1.yml \
               -f docker/compose/docker-compose.group2.yml \
               -f docker/compose/docker-compose.group3.yml \
               down
```

## d) Environment Encryption & Management

### Overview

This application uses Laravel's built-in environment encryption (`env:encrypt`) to securely manage environment variables. Each environment has its own encrypted file stored in the repository, with the encryption key stored as a GitHub environment secret.

### Encrypted Environment Storage

**All environment files are stored as GitHub secrets** - nothing is committed to the repository.

Each environment requires **two secrets** configured in GitHub:

1. **Repository → Settings → Environments** → Select environment (dev/sandbox/preprod/production)
2. Add two secrets:
   - **`ENV_ENCRYPTION_KEY`** - The 32-character encryption key
   - **`ENV_ENCRYPTED_CONTENT`** - The entire encrypted environment file content

**Important:**
- Never commit `.env` or `.env.*.encrypted` files to the repository
- All environment files (plain and encrypted) are blocked by `.gitignore`
- Encrypted content is only stored in GitHub secrets

### Creating and Storing Encrypted Environments

To create or update encrypted environment files:

```bash
# 1. Prepare your .env file for the environment
cp .env.example .env.dev
# Edit .env.dev with environment-specific values

# 2. Encrypt the file
php artisan env:encrypt --env=dev

# Output example:
# Environment successfully encrypted.
# Encryption key: 3UVsEgGVK36XN82KKeyLFMhvosbZN1aF

# 3. Copy the encrypted file content
cat .env.dev.encrypted
# Copy the entire output (it's a JSON string)

# 4. Store BOTH secrets in GitHub
# Go to: Settings → Environments → dev → Secrets

# Add ENV_ENCRYPTION_KEY:
# Value: 3UVsEgGVK36XN82KKeyLFMhvosbZN1aF

# Add ENV_ENCRYPTED_CONTENT:
# Value: (paste the entire content from step 3)

# 5. Clean up local files (don't commit them!)
rm .env.dev .env.dev.encrypted
```

Repeat for each environment (sandbox, preprod, production).

**Security Note:** The encrypted content is safe to store in GitHub secrets (it's already encrypted), but storing it there instead of the repository provides an extra layer of security and allows for rotation without code commits.

### Deployment Flow with Encryption

The deployment workflow handles decryption automatically:

```
1. Checkout code from repository
2. Write ENV_ENCRYPTED_CONTENT secret to .env.{environment}.encrypted file
3. Install PHP and composer dependencies
4. Decrypt using ENV_ENCRYPTION_KEY: php artisan env:decrypt --env={environment} --force
5. Build Docker image (with decrypted .env included)
6. Transfer image to servers
7. Deploy containers
```

The `.env` file is baked into the Docker image during build, so servers don't need:
- Git repository access
- Access to encryption keys or encrypted files
- Access to GitHub secrets
- Source code on disk

**Security Benefits:**
- No environment files in repository (not even encrypted ones)
- Encryption keys never leave GitHub secrets
- Images are self-contained with all configuration

### Key Rotation

To rotate encryption keys (recommended every 90 days):

```bash
# 1. Get current environment and decrypt it
# (You'll need the current ENV_ENCRYPTION_KEY from GitHub secrets)
export LARAVEL_ENV_ENCRYPTION_KEY="<current-key>"

# Create the encrypted file temporarily (use ENV_ENCRYPTED_CONTENT from secrets)
echo "<paste-current-encrypted-content>" > .env.production.encrypted
php artisan env:decrypt --env=production --force

# 2. Generate new key and re-encrypt
NEW_KEY=$(openssl rand -base64 32 | head -c 32)
php artisan env:encrypt --env=production --key="$NEW_KEY"

echo "New key: $NEW_KEY"
# Save this key!

# 3. Update BOTH GitHub Secrets
# Go to: Settings → Environments → production → Secrets

# Update ENV_ENCRYPTION_KEY:
# Value: (paste NEW_KEY from above)

# Update ENV_ENCRYPTED_CONTENT:
cat .env.production.encrypted
# Copy output and paste as secret value

# 4. Clean up local files
rm .env .env.production.encrypted
unset LARAVEL_ENV_ENCRYPTION_KEY
```

### Local Testing with Encrypted Environments

To test decryption locally:

```bash
# Set the encryption key
export LARAVEL_ENV_ENCRYPTION_KEY="<your-key>"

# Decrypt the file
php artisan env:decrypt --env=dev

# Verify .env was created
cat .env

# Clean up
rm .env
unset LARAVEL_ENV_ENCRYPTION_KEY
```

## e) Environment Configuration

### Local Development (.env)

For local development, configure your `.env` file with containerized service hostnames:

```env
APP_NAME=Lynk
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis

QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

### Production/Pre-Production (.env)

For production deployments with external managed services (OCI MySQL, external Redis):

```env
APP_NAME=Lynk
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.lynk.example.com

DB_CONNECTION=mysql
DB_HOST=10.0.1.50                    # External OCI MySQL hostname/IP
DB_PORT=3306
DB_DATABASE=lynk_production
DB_USERNAME=lynk_user
DB_PASSWORD=<secure-password>

REDIS_HOST=10.0.2.30                 # External Redis hostname/IP
REDIS_PASSWORD=<redis-password>
REDIS_PORT=6379
REDIS_CLIENT=phpredis

QUEUE_CONNECTION=redis

# Production mail settings
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@lynk.example.com
MAIL_PASSWORD=<mail-password>
MAIL_ENCRYPTION=tls
```

**Important for Production:**
- Use external/managed MySQL and Redis services
- Disable `APP_DEBUG`
- Set strong passwords for all services
- Configure proper `APP_URL` for your load balancer
- Do NOT use bind mounts (volumes) in worker group compose files
- Ensure `.env` file is present on each server but NOT committed to git

## f) Running in Pre-Production / Production (3 Servers)

### Production Deployment Strategy

In production, deploy the application across three servers behind a classic load balancer:

- **Server A**: Base app + Worker Group 1 (4 workers for local market core processing)
- **Server B**: Base app + Worker Group 2 (4 workers for general & trader operations)
- **Server C**: Base app + Worker Group 3 (4 workers for supporting operations & external integrations)

Each server runs the base `app` service to handle HTTP requests via the load balancer, plus its designated worker group (4 worker containers per group).

### Prerequisites (Each Server)

1. Docker and Docker Compose installed
2. Application code deployed (git clone or CI/CD)
3. `.env` file configured with production credentials
4. Image built and tagged: `docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .`
   - OR pull from a container registry if using CI/CD

### Server A: Base App + Group 1 Workers

SSH to Server A and run:

```bash
cd /path/to/lynk-backend

# Build image (or pull from registry)
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .

# Start base app + worker group 1
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.group1.yml \
               up -d
```

### Server B: Base App + Group 2 Workers

SSH to Server B and run:

```bash
cd /path/to/lynk-backend

docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .

docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.group2.yml \
               up -d
```

### Server C: Base App + Group 3 Workers

SSH to Server C and run:

```bash
cd /path/to/lynk-backend

docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .

docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.group3.yml \
               up -d
```

### Load Balancer Configuration

Configure your classic load balancer to distribute traffic across:
- Server A: `<server-a-ip>:9000` (php-fpm) or use nginx container
- Server B: `<server-b-ip>:9000` (php-fpm) or use nginx container
- Server C: `<server-c-ip>:9000` (php-fpm) or use nginx container

**Note**: In production, you may want to run nginx on each server. To do so, extract the nginx service from `docker-compose.local.yml` into a separate `docker-compose.nginx.yml` and use it on each server.

### Scheduler Service (Run on ONE Server Only)

Laravel's task scheduler should run on exactly one server to prevent duplicate scheduled jobs.

To enable the scheduler on Server A (for example), include the scheduler compose file:

```bash
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.scheduler.yml \
               -f docker/compose/docker-compose.group1.yml \
               up -d
```

**Important**: Only run the scheduler on one server. Do NOT include `docker-compose.scheduler.yml` on Server B and Server C.

### Database Migrations

**Important**: Database migrations should be handled in your CI/CD pipeline **before** deploying the new container images to production servers.

If you need to run migrations manually on a server:

```bash
# Run migrations in the app container
docker compose -f docker/compose/docker-compose.web.yml \
               exec app php artisan migrate --force

# Cache configuration and routes
docker compose -f docker/compose/docker-compose.web.yml \
               exec app php artisan config:cache
docker compose -f docker/compose/docker-compose.web.yml \
               exec app php artisan route:cache
```

### Scaling Worker Groups in Production

Each worker group has 4 dedicated worker containers. To scale a specific worker horizontally:

**Scale individual workers using `--scale` flag:**

```bash
# Scale the local market worker to 3 instances
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.group1.yml \
               up -d --scale worker-local-market=3

# Scale multiple workers in Group 1
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.group1.yml \
               up -d \
               --scale worker-local-market=3 \
               --scale worker-order-initiation=2 \
               --scale worker-buy-commodities=2 \
               --scale worker-complete-commodities=2
```

**Vertical Scaling**: To handle higher load, allocate more CPU/memory to specific servers or adjust queue worker timeout/tries values in the compose files.

### Blue-Green Deployment / Zero-Downtime Updates

For zero-downtime updates:

1. Build new image with version tag: `docker build -t app-php-fpm:v2.0 -f docker/laravel/Dockerfile .`
2. Run migrations in CI/CD pipeline (before deployment)
3. Update image reference in compose files: `image: app-php-fpm:v2.0`
4. Rolling restart per server:
   - Server A: `docker compose -f ... up -d` (pulls new image, recreates containers)
   - Wait for health checks to pass
   - Server B: repeat
   - Server C: repeat

## g) Health Checks & Logs

### Health Check Endpoints

All services include health checks. Check status with:

```bash
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.local.yml \
               ps
```

**Service Health Checks:**

| Service | Health Check Command | Endpoint |
|---------|---------------------|----------|
| app | `php -r "exit(0);"` | N/A |
| scheduler | `php -r "exit(0);"` | N/A |
| nginx | `wget -qO- http://localhost/healthz` | http://localhost:8080/healthz |
| redis | `redis-cli ping` | N/A |
| mysql | `mysqladmin ping -h localhost -u root -p...` | N/A |
| worker-group[1-3] | `php -r "exit(0);"` | N/A |
| phpmyadmin | `wget -qO- http://localhost` | N/A |
| mailhog | `wget -qO- http://localhost:8025` | N/A |
| browserless | `wget -qO- http://localhost:3000` | N/A |

### Manual Health Checks

**Nginx:**
```bash
curl http://localhost:8080/healthz
# Expected: OK
```

**Redis:**
```bash
docker compose -f docker/compose/docker-compose.local.yml exec redis redis-cli ping
# Expected: PONG
```

**MySQL:**
```bash
docker compose -f docker/compose/docker-compose.local.yml exec mysql mysqladmin ping -u root -p<password>
# Expected: mysqld is alive
```

### Viewing Logs

**All services:**
```bash
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.local.yml \
               logs -f
```

**Specific service:**
```bash
# App logs
docker compose -f docker/compose/docker-compose.web.yml logs -f app

# Worker group 1 logs
docker compose -f docker/compose/docker-compose.group1.yml logs -f worker-group1

# Nginx logs
docker compose -f docker/compose/docker-compose.local.yml logs -f nginx

# Redis logs
docker compose -f docker/compose/docker-compose.local.yml logs -f redis
```

**Last 100 lines:**
```bash
docker compose -f docker/compose/docker-compose.web.yml logs --tail=100 app
```

**Laravel application logs** (inside container):
```bash
docker compose -f docker/compose/docker-compose.web.yml exec app tail -f storage/logs/laravel.log
```

### Testing Queue Processing

Dispatch a test job to verify workers are processing:

```bash
# Execute tinker in the app container
docker compose -f docker/compose/docker-compose.web.yml exec app php artisan tinker

# In tinker, dispatch a test job
>>> dispatch(function() { \Log::info('Queue test successful'); })->onQueue('default');
>>> exit

# Check worker-group2 logs (handles 'default' queue)
docker compose -f docker/compose/docker-compose.group2.yml logs -f worker-group2
```

Expected output in logs: Job processed successfully.

### Monitoring Container Resource Usage

```bash
# CPU and memory usage
docker stats

# Specific containers
docker stats laravel-app worker-group1
```

## h) Changelog

### 2025-12-10 - Environment Encryption Implementation
- **Added**: Laravel env:encrypt integration for secure environment management
- **Security**: All environment files stored as GitHub secrets (not in repository)
- **Updated**: All deployment workflows to write encrypted content from secrets before decryption
- **Removed**: Git pull steps from deployment workflows (images are now self-contained)
- **Updated**: `.gitignore` to block all `.env.*` files (including encrypted)
- **Security**: Environment variables baked into Docker images at build time
- **Benefit**: Servers no longer need Git repository access, encryption keys, or environment files
- **Documentation**: Added comprehensive environment encryption guide (section d)
- **GitHub Secrets**: Each environment requires `ENV_ENCRYPTION_KEY` and `ENV_ENCRYPTED_CONTENT`

## i) Changelog (Historical)

### 2025-11-16 - Worker Groups Reorganization
- **Updated**: Reorganized worker groups based on actual production queue configuration
- **Group 1** (4 workers): Local market core processing
  - worker-local-market: local_market, local_market_states, local_market_process
  - worker-order-initiation: local_market_order_initiation
  - worker-buy-commodities: buy_commodities_local_market_orders
  - worker-complete-commodities: complete_commodities_purchased_local_market
- **Group 2** (4 workers): General & trader operations
  - worker-default: default, notifications, local_market_webhooks, create_trader_orders, local_market_eligible_quantities
  - worker-trader-initiation: trader_order_initiation
  - worker-hold-units: hold_eligible_units_local_market_orders (with --backoff=120)
  - worker-settlement: local_market_commodities_settlement
- **Group 3** (4 workers): Supporting operations & external integrations
  - worker-inventory-logging: local_market_order_inventories_units_logging
  - worker-refresh-eligibilities: refresh_eligibilities
  - worker-order-fees: apply_order_fees
  - worker-external-integrations: bursam, unit-inventory, expire_trader_order (with --backoff=120)
- **Preserved**: All original --tries, --backoff, and queue configurations from old setup
- **Updated**: Documentation to reflect actual worker distribution across servers

### 2025-11-16 - Scheduler Separation & Migrator Removal
- **Added**: Created separate `docker-compose.scheduler.yml` for Laravel task scheduler
  - Allows scheduler to be deployed independently on a single server
  - Prevents accidental duplicate scheduler instances
- **Removed**: Migrator service from `docker-compose.base.yml`
  - Database migrations now handled in CI/CD pipeline (before deployment)
  - Manual migration commands documented for emergency use
- **Updated**: Documentation to reflect new scheduler deployment strategy
- **Updated**: Health checks table to include scheduler service

### 2025-11-16 - Initial Implementation
- Created multi-stage Dockerfile based on `php:8.2-fpm-alpine`
- Implemented base compose file (`docker-compose.base.yml`) with core app service
- Added local development infrastructure (`docker-compose.local.yml`): nginx, redis, mysql, phpmyadmin, mailhog, browserless
- Created three worker group compose files for separated queue processing (12 total workers across 3 groups)
- Implemented health checks on all long-running containers
- Configured production-optimized OPcache settings
- Documented deployment strategy for 3-server production setup with external MySQL and Redis
- Removed old docker-compose.yml and docker/ directory implementation
