# Docker Deployment Guide

This document describes the Docker-based deployment strategy for the Lynk Laravel API application.

## Table of Contents

- [a) Overview](#a-overview)
- [b) Architecture](#b-architecture)
- [c) Environment-Specific Deployment](#c-environment-specific-deployment)
- [d) Environment Configuration](#d-environment-configuration)
- [e) Production Deployment Details](#e-production-deployment-details)
- [f) Health Checks & Monitoring](#f-health-checks--monitoring)
- [g) Environment Encryption](#g-environment-encryption)

## a) Overview

### Architecture Summary

This setup implements a production-ready, containerized Laravel API with separated concerns:

- **Web Layer**: PHP-FPM application container (with optional Nginx reverse proxy)
- **Queue Workers**: 27 independent worker services organized into 3 groups
- **Infrastructure**: Redis for queues, MySQL for database (containerized locally, external in production)
- **Development Tools**: phpMyAdmin, MailHog, Browserless Chrome (local only)
- **Monitoring**: Nightwatch service for debugging and observability

### Key Principles

1. **Profile-Based Deployment**: Services organized into Docker Compose profiles for flexible deployment
2. **One Process Per Container**: Each container runs a single process (nginx, php-fpm, or queue worker)
3. **Runtime Scaling**: Worker replica counts controlled via `--scale` flag at deployment time
4. **Worker Separation**: Worker groups isolated by responsibility and can run on different servers
5. **Health Checks**: All long-running containers include health checks for monitoring
6. **Immutable Images**: Production uses immutable Docker images with no bind mounts

## b) Architecture

### Deployment Profiles

Services are organized into Docker Compose profiles for environment-specific deployment:

| Profile | Purpose | Services |
|---------|---------|----------|
| `web` | PHP-FPM application | 1 service (required for all deployments) |
| `local` | Development infrastructure | nginx, redis, mysql, phpMyAdmin, MailHog, Browserless |
| `observability` | Monitoring & debugging | nightwatch |
| `group1` | Local market core processing | 8 queue worker services |
| `group2` | General & trader operations | 12 queue worker services |
| `group3` | Supporting & external integrations | 6 queue worker services |

**Total**: 27 independent queue worker services across 3 groups

### Worker Groups Distribution

**Group 1 - Local Market Core Processing (Server A in production):**
- Commodities settlement: `local_market_commodities_settlement`
- Eligible quantities tracking: `local_market_eligible_quantities`
- Order inventories logging: `local_market_order_inventories_units_logging`
- Order initiation: `local_market_order_initiation`
- Trader order initiation: `trader_order_initiation`
- Complete commodities: `complete_commodities_purchased_local_market`
- Buy commodities: `buy_commodities_local_market_orders`
- Apply order fees: `apply_order_fees`

**Group 2 - General & Trader Operations (Server B in production):**
- Market states: `local_market_states`
- Market process: `local_market_process`
- Eligible quantities: `local_market_eligible_quantities`
- Hold eligible units: `hold_eligible_units_local_market_orders`
- Complete commodities: `complete_commodities_purchased_local_market`
- Refresh eligibilities: `refresh_eligibilities`
- Notifications: `notifications`
- Create trader orders: `create_trader_orders`
- Default queue: `default`
- Bursam integration: `bursam`
- Complete purchasing: `complete_purchasing_local_market_orders`
- Message queue: `mq`

**Group 3 - Supporting Operations & External Integrations (Server C in production):**
- Webhooks: `local_market_webhooks`
- Expire trader orders: `expire_trader_order`
- Generate units: `unit-inventory`
- Buy commodities: `buy_commodities_local_market_orders`
- Notifications: `notifications`
- Bursam integration: `bursam`

### File Structure

```
docker/
├── laravel/
│   └── Dockerfile           # Multi-stage PHP 8.2-fpm image
└── nginx/
    └── default.conf         # Nginx configuration with health endpoint
docker-compose.yml           # Single compose file with all services
.dockerignore               # Excludes unnecessary files from build context
docker.md                   # This documentation
```

### Component Details

**docker/laravel/Dockerfile**
- Multi-stage build: composer dependencies + runtime image
- Base: `php:8.2-fpm-alpine`
- Extensions: pdo_mysql, mbstring, bcmath, intl, zip, gd, opcache, redis
- Non-root user: `www-data` (uid/gid 1000)
- Production-optimized OPcache configuration
- Exposes port 9000 for FastCGI

**docker/nginx/default.conf**
- Reverse proxy to PHP-FPM (app:9000)
- Health check endpoint: `/healthz`
- Request timeout: 300 seconds
- Max body size: 100MB

**docker-compose.yml**
- All services defined with profile tags
- No hardcoded replica counts
- Service dependencies with health checks
- Networks and volumes configured

**.dockerignore**
- Excludes git, node_modules, vendor, logs, cache
- Reduces Docker build time and image size

## c) Environment-Specific Deployment

### Local Development Environment

**Purpose**: Development with all services running locally at minimal scale

**Build the image (requires encrypted environment):**
```bash
# Create encrypted environment for local dev
php artisan env:encrypt
# This creates .env.encrypted in your project directory

# Build with encrypted environment
LARAVEL_ENV_ENCRYPTION_KEY="your-local-key" \
DOCKER_BUILDKIT=1 docker build \
  -t app-php-fpm:latest \
  -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .
```

**Start all services:**
```bash
docker compose --profile web --profile local --profile group1 --profile group2 --profile group3 up -d
```

**Access points:**
- Application: http://localhost
- Nginx health: http://localhost/healthz
- phpMyAdmin: http://localhost:8081
- MailHog: http://localhost:8025
- Browserless: http://localhost:3002

**Note**: All workers run at scale 1 (default) for resource efficiency.

**Stop all services:**
```bash
docker compose down
```

---

### Sandbox Environment

**Purpose**: Single-server testing environment with production-scale workers

**Build the image (with encrypted environment):**
```bash
# Ensure .env.encrypted exists in project directory
# Create it with: php artisan env:encrypt

# Build with encrypted environment
LARAVEL_ENV_ENCRYPTION_KEY="your-sandbox-encryption-key" \
DOCKER_BUILDKIT=1 docker build \
  -t app-php-fpm:sandbox \
  -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .

# Or use docker-compose (requires export)
export ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)"
export LARAVEL_ENV_ENCRYPTION_KEY="your-sandbox-key"
docker-compose build app
```

**Start command:**
```bash
docker compose --profile web --profile local --profile group1 --profile group2 --profile group3 up -d \
  --scale local-market-states-worker=8 \
  --scale local-market-webhooks-worker=5 \
  --scale local-market-process-worker=8 \
  --scale local-market-expire-trader-order-worker=8 \
  --scale local-market-commodities-settlement-worker=8 \
  --scale local-market-eligible-quantities-worker-group1=8 \
  --scale local-market-eligible-quantities-worker-group2=8 \
  --scale local-market-order-inventories-units-logging=5 \
  --scale local-market-order-initiation=5 \
  --scale trader-order-initiation=5 \
  --scale local-market-generate-units=1 \
  --scale hold-eligible-local-order-units-worker=5 \
  --scale complete-commodities-purchased-local-market-worker-group1=5 \
  --scale complete-commodities-purchased-local-market-worker-group2=5 \
  --scale buy-commodities-local-market-orders-worker-group1=5 \
  --scale buy-commodities-local-market-orders-worker-group3=5 \
  --scale refresh-eligibilities-worker=1 \
  --scale notifications-worker-group2=5 \
  --scale notifications-worker-group3=5 \
  --scale create-trader-orders-worker=8 \
  --scale default-worker=8 \
  --scale bursam-worker-group2=1 \
  --scale bursam-worker-group3=1 \
  --scale apply-order-fees-worker=1 \
  --scale complete-purchasing-local-market-orders-worker=5 \
  --scale message-queue-worker=2
```

**Summary**: 27 services, 131 total replicas

---

### Pre-Production Environment (3 Servers)

**Architecture**: Distributed across 3 servers behind a load balancer

#### Server A (Pre-Prod) - Web + Group 1

```bash
cd /path/to/lynk-backend

# Ensure .env.encrypted exists in project directory

# Build with encrypted environment (inline - no export needed)
LARAVEL_ENV_ENCRYPTION_KEY="your-preprod-encryption-key" \
DOCKER_BUILDKIT=1 docker build \
  -t app-php-fpm:preprod \
  -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .

# Deploy workers
docker compose --profile web --profile group1 up -d \
  --scale local-market-commodities-settlement-worker=8 \
  --scale local-market-eligible-quantities-worker-group1=8 \
  --scale local-market-order-inventories-units-logging=5 \
  --scale local-market-order-initiation=5 \
  --scale trader-order-initiation=5 \
  --scale complete-commodities-purchased-local-market-worker-group1=5 \
  --scale buy-commodities-local-market-orders-worker-group1=5 \
  --scale apply-order-fees-worker=1
```

**Group 1**: 8 services, 42 replicas

#### Server B (Pre-Prod) - Web + Group 2

```bash
cd /path/to/lynk-backend

# Ensure .env.encrypted exists in project directory

# Build with encrypted environment (inline - no export needed)
LARAVEL_ENV_ENCRYPTION_KEY="your-preprod-encryption-key" \
DOCKER_BUILDKIT=1 docker build \
  -t app-php-fpm:preprod \
  -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .

# Deploy workers
docker compose --profile web --profile group2 up -d \
  --scale local-market-states-worker=8 \
  --scale local-market-process-worker=8 \
  --scale local-market-eligible-quantities-worker-group2=8 \
  --scale hold-eligible-local-order-units-worker=5 \
  --scale complete-commodities-purchased-local-market-worker-group2=5 \
  --scale refresh-eligibilities-worker=1 \
  --scale notifications-worker-group2=5 \
  --scale create-trader-orders-worker=8 \
  --scale default-worker=8 \
  --scale bursam-worker-group2=1 \
  --scale complete-purchasing-local-market-orders-worker=5 \
  --scale message-queue-worker=2
```

**Group 2**: 12 services, 64 replicas

#### Server C (Pre-Prod) - Web + Group 3

```bash
cd /path/to/lynk-backend

# Ensure .env.encrypted exists in project directory

# Build with encrypted environment (inline - no export needed)
LARAVEL_ENV_ENCRYPTION_KEY="your-preprod-encryption-key" \
DOCKER_BUILDKIT=1 docker build \
  -t app-php-fpm:preprod \
  -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .

# Deploy workers
docker compose --profile web --profile group3 up -d \
  --scale local-market-webhooks-worker=5 \
  --scale local-market-expire-trader-order-worker=8 \
  --scale local-market-generate-units=1 \
  --scale buy-commodities-local-market-orders-worker-group3=5 \
  --scale notifications-worker-group3=5 \
  --scale bursam-worker-group3=1
```

**Group 3**: 6 services, 25 replicas

---

### Production Environment (3 Servers)

**Architecture**: Same as pre-production - distributed across 3 servers

#### Server A (Production) - Web + Group 1

```bash
cd /path/to/lynk-backend

# Ensure .env.encrypted exists in project directory

# Build with encrypted environment (inline - no export needed)
LARAVEL_ENV_ENCRYPTION_KEY="your-production-encryption-key" \
DOCKER_BUILDKIT=1 docker build \
  -t app-php-fpm:production \
  -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .

# Deploy workers
docker compose --profile web --profile group1 up -d \
  --scale local-market-commodities-settlement-worker=8 \
  --scale local-market-eligible-quantities-worker-group1=8 \
  --scale local-market-order-inventories-units-logging=5 \
  --scale local-market-order-initiation=5 \
  --scale trader-order-initiation=5 \
  --scale complete-commodities-purchased-local-market-worker-group1=5 \
  --scale buy-commodities-local-market-orders-worker-group1=5 \
  --scale apply-order-fees-worker=1
```

#### Server B (Production) - Web + Group 2

```bash
cd /path/to/lynk-backend

# Ensure .env.encrypted exists in project directory

# Build with encrypted environment (inline - no export needed)
LARAVEL_ENV_ENCRYPTION_KEY="your-production-encryption-key" \
DOCKER_BUILDKIT=1 docker build \
  -t app-php-fpm:production \
  -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .

# Deploy workers
docker compose --profile web --profile group2 up -d \
  --scale local-market-states-worker=8 \
  --scale local-market-process-worker=8 \
  --scale local-market-eligible-quantities-worker-group2=8 \
  --scale hold-eligible-local-order-units-worker=5 \
  --scale complete-commodities-purchased-local-market-worker-group2=5 \
  --scale refresh-eligibilities-worker=1 \
  --scale notifications-worker-group2=5 \
  --scale create-trader-orders-worker=8 \
  --scale default-worker=8 \
  --scale bursam-worker-group2=1 \
  --scale complete-purchasing-local-market-orders-worker=5 \
  --scale message-queue-worker=2
```

#### Server C (Production) - Web + Group 3

```bash
cd /path/to/lynk-backend

# Ensure .env.encrypted exists in project directory

# Build with encrypted environment (inline - no export needed)
LARAVEL_ENV_ENCRYPTION_KEY="your-production-encryption-key" \
DOCKER_BUILDKIT=1 docker build \
  -t app-php-fpm:production \
  -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .

# Deploy workers
docker compose --profile web --profile group3 up -d \
  --scale local-market-webhooks-worker=5 \
  --scale local-market-expire-trader-order-worker=8 \
  --scale local-market-generate-units=1 \
  --scale buy-commodities-local-market-orders-worker-group3=5 \
  --scale notifications-worker-group3=5 \
  --scale bursam-worker-group3=1
```

---

### Environment Summary

| Environment | Deployment | Group 1 | Group 2 | Group 3 | Total Replicas |
|-------------|------------|---------|---------|---------|----------------|
| Local       | Single server | 8 | 12 | 6 | 26 |
| Sandbox     | Single server | 42 | 64 | 25 | 131 |
| Pre-Prod    | 3 servers | 42 | 64 | 25 | 131 |
| Production  | 3 servers | 42 | 64 | 25 | 131 |

### Worker Scale Reference

**Group 1 Workers (42 replicas total):**
- `local-market-commodities-settlement-worker`: 8
- `local-market-eligible-quantities-worker-group1`: 8
- `local-market-order-inventories-units-logging`: 5
- `local-market-order-initiation`: 5
- `trader-order-initiation`: 5
- `complete-commodities-purchased-local-market-worker-group1`: 5
- `buy-commodities-local-market-orders-worker-group1`: 5
- `apply-order-fees-worker`: 1

**Group 2 Workers (64 replicas total):**
- `local-market-states-worker`: 8
- `local-market-process-worker`: 8
- `local-market-eligible-quantities-worker-group2`: 8
- `hold-eligible-local-order-units-worker`: 5
- `complete-commodities-purchased-local-market-worker-group2`: 5
- `refresh-eligibilities-worker`: 1
- `notifications-worker-group2`: 5
- `create-trader-orders-worker`: 8
- `default-worker`: 8
- `bursam-worker-group2`: 1
- `complete-purchasing-local-market-orders-worker`: 5
- `message-queue-worker`: 2

**Group 3 Workers (25 replicas total):**
- `local-market-webhooks-worker`: 5
- `local-market-expire-trader-order-worker`: 8
- `local-market-generate-units`: 1
- `buy-commodities-local-market-orders-worker-group3`: 5
- `notifications-worker-group3`: 5
- `bursam-worker-group3`: 1

## d) Environment Configuration

### Local Development (.env)

Configure your `.env` file with containerized service hostnames:

```env
APP_NAME=Lynk
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=lynk_local
DB_USERNAME=root
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

For production deployments with external managed services:

```env
APP_NAME=Lynk
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.lynk.example.com

DB_CONNECTION=mysql
DB_HOST=10.0.1.50                    # External OCI MySQL
DB_PORT=3306
DB_DATABASE=lynk_production
DB_USERNAME=lynk_user
DB_PASSWORD=<secure-password>

REDIS_HOST=10.0.2.30                 # External Redis
REDIS_PASSWORD=<redis-password>
REDIS_PORT=6379
REDIS_CLIENT=phpredis

QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@lynk.example.com
MAIL_PASSWORD=<mail-password>
MAIL_ENCRYPTION=tls
```

**Production Notes:**
- Use external/managed MySQL and Redis services
- Disable `APP_DEBUG`
- Set strong passwords for all services
- Configure proper `APP_URL` for your load balancer
- Environment files are encrypted and stored as GitHub secrets (see Section g)

## e) Production Deployment Details

### Prerequisites (Each Server)

1. Docker 20.10+ and Docker Compose 2.0+ installed
2. `.env` file configured with production credentials
3. Docker image built or pulled from registry

### Load Balancer Configuration

Configure your load balancer to distribute traffic across all 3 servers:

- Server A: `<server-a-ip>:9000` (FastCGI)
- Server B: `<server-b-ip>:9000` (FastCGI)
- Server C: `<server-c-ip>:9000` (FastCGI)

**Using Nginx as Reverse Proxy:**

If using nginx on each production server (instead of direct FastCGI):

```bash
# Add the local profile to get nginx
docker compose --profile web --profile local --profile group1 up -d
```

Then configure load balancer to port 80 on each server.

### Database Migrations

**Important**: Run migrations in your CI/CD pipeline before deploying containers.

Manual migration (if needed):

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

### Laravel Scheduler

The scheduler should run on exactly ONE server to prevent duplicate jobs:

```bash
# Run as a separate container on Server A (example)
docker run -d --name laravel-scheduler \
  --env-file .env \
  --network lynk-backend_laravel \
  app-php-fpm:latest \
  php artisan schedule:work
```

### Scaling Workers Dynamically

Adjust worker replicas based on queue depth and load:

```bash
# View current replicas
docker compose ps

# Adjust specific worker scaling
docker compose --profile group1 up -d \
  --scale local-market-order-initiation=10 \
  --scale trader-order-initiation=7

# List all available worker services
docker compose --profile group1 config --services
```

**Scaling Strategies:**
- **High-volume queues** (states, process, settlement): 8 replicas
- **Medium-volume queues** (notifications, trader orders): 5 replicas
- **Low-volume queues** (refresh, fees, bursam): 1 replica

Monitor queue depths using Laravel Horizon or Redis CLI to determine optimal scaling.

### Zero-Downtime Deployments

For rolling updates:

1. Build new image with version tag: `app-php-fpm:v2.0`
2. Run migrations in CI/CD pipeline
3. Update image reference in docker-compose.yml (optional)
4. Rolling restart per server:
   - Server A: `docker compose --profile web --profile group1 up -d --pull always`
   - Wait for health checks to pass
   - Server B: repeat
   - Server C: repeat

## f) Health Checks & Monitoring

### Service Health Checks

All services include built-in health checks:

| Service | Health Check | Interval |
|---------|--------------|----------|
| app | `php -r "exit(0);"` | 30s |
| nginx | `wget -qO- http://localhost/healthz` | 30s |
| redis | `redis-cli ping` | 30s |
| mysql | `mysqladmin ping` | 10s |
| workers | `php -r "exit(0);"` | 30s |
| phpmyadmin | `wget -qO- http://localhost` | 30s |
| mailhog | `wget -qO- http://localhost:8025` | 30s |
| browserless | `wget -qO- http://localhost:3000` | 30s |

### Manual Health Checks

**Nginx:**
```bash
curl http://localhost/healthz
# Expected: OK
```

**Redis:**
```bash
docker compose exec redis redis-cli ping
# Expected: PONG
```

**MySQL:**
```bash
docker compose exec mysql mysqladmin ping -u root -p<password>
# Expected: mysqld is alive
```

**Check all service statuses:**
```bash
docker compose ps
```

### Viewing Logs

**All services:**
```bash
docker compose logs -f
```

**Specific service:**
```bash
docker compose logs -f app
docker compose logs -f local-market-order-initiation
docker compose logs -f nginx
```

**Last 100 lines:**
```bash
docker compose logs --tail=100 app
```

**Laravel application logs (inside container):**
```bash
docker compose exec app tail -f storage/logs/laravel.log
```

### Testing Queue Processing

Dispatch a test job:

```bash
docker compose exec app php artisan tinker

# In tinker
>>> dispatch(function() { \Log::info('Queue test successful'); })->onQueue('default');
>>> exit

# Check worker logs
docker compose logs -f default-worker
```

### Monitoring Resource Usage

```bash
# All containers
docker stats

# Specific containers
docker stats laravel-app local-market-states-worker
```

### Common Commands

```bash
# Restart a service
docker compose restart app

# Stop all services
docker compose down

# Remove all containers and volumes
docker compose down -v

# View service configuration
docker compose config

# List all service names
docker compose config --services

# View running containers
docker compose ps -a
```

## g) Environment Encryption

### Overview

The application uses Laravel's built-in environment encryption (`env:encrypt`) with Docker BuildKit secrets for secure environment management. This section covers two approaches:

1. **Build-Time Decryption** (Recommended for Docker deployments) - Decrypt during image build
2. **CI/CD Decryption** (Current SSH-based deployments) - Decrypt before deployment

### Approach 1: Build-Time Decryption with Docker BuildKit (Recommended)

This approach decrypts environment files during Docker image build using BuildKit secrets, ensuring encryption keys never appear in image layers.

#### Creating Encrypted Environments

```bash
# 1. Prepare your environment file
# Edit .env with your environment-specific values

# 2. Encrypt the file
php artisan env:encrypt

# Output example:
# Environment successfully encrypted.
# Encryption key: 3UVsEgGVK36XN82KKeyLFMhvosbZN1aF

# This creates .env.encrypted in your project directory

# 3. Store in GitHub Secrets
# Navigate to: Settings → Secrets and variables → Actions
# Add two secrets:
#   - PROD_ENV_ENCRYPTION_KEY: 3UVsEgGVK36XN82KKeyLFMhvosbZN1aF
#   - PROD_ENV_ENCRYPTED_CONTENT: (paste the raw content of .env.encrypted file)

# 4. Clean up plaintext environment file (keep encrypted version)
rm -f .env
```

#### Building Docker Images with Encrypted Environments

**IMPORTANT**: You must pass the encrypted environment content as a build argument. The encrypted content is passed as text via `--build-arg ENCRYPTED_ENV_CONTENT`.

**Method 1: Using Inline Environment Variable (No Export - Recommended)**

```bash
# Ensure .env.encrypted exists in your project directory

# Set encryption key inline (no export needed)
LARAVEL_ENV_ENCRYPTION_KEY="your-32-char-encryption-key" \
DOCKER_BUILDKIT=1 docker build \
  --file docker/laravel/Dockerfile \
  --tag app-php-fpm:production \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .

# Verify .env exists in image
docker run --rm app-php-fpm:production sh -c 'test -f .env && echo "✅ .env exists" || echo "❌ .env missing"'

# Test the application can start
docker run --rm app-php-fpm:production php artisan --version
```

**Method 2: Using Export (Traditional)**

```bash
# Ensure .env.encrypted exists in your project directory

# Export environment variable
export LARAVEL_ENV_ENCRYPTION_KEY="your-32-char-encryption-key"

# Build with BuildKit secrets
DOCKER_BUILDKIT=1 docker build \
  --file docker/laravel/Dockerfile \
  --tag app-php-fpm:production \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .
```

**Method 3: Using File-Based Secret (Most Secure)**

```bash
# Ensure .env.encrypted exists in your project directory

# Save encryption key to a temporary file
echo "your-32-char-encryption-key" > /tmp/encryption.key

# Build using file-based secret (key never in command line history)
DOCKER_BUILDKIT=1 docker build \
  --file docker/laravel/Dockerfile \
  --tag app-php-fpm:production \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,src=/tmp/encryption.key \
  .

# Clean up key file
rm /tmp/encryption.key
```

**Using docker-compose:**

```bash
# Ensure .env.encrypted exists in your project directory

# Set environment variables
export ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)"
export LARAVEL_ENV_ENCRYPTION_KEY="your-32-char-key"

# Build with encrypted environment
docker-compose build app

# Run the application
docker-compose --profile web up -d

# Or combine with other profiles
docker-compose --profile web --profile group1 up -d
```

#### GitHub Actions CI/CD Integration

Example workflow for Docker-based deployments:

```yaml
name: Build and Deploy Docker Image (Production)

on:
  workflow_dispatch:
  push:
    branches:
      - production

env:
  ENVIRONMENT: production

jobs:
  build-and-deploy:
    runs-on: ubuntu-latest
    environment: production

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Create encrypted environment file
        run: |
          # Restore encrypted environment file from GitHub secret
          echo "${{ secrets.PROD_ENV_ENCRYPTED_CONTENT }}" > .env.encrypted

      - name: Build Docker image with encrypted environment
        run: |
          # Build with BuildKit secrets
          docker buildx build \
            --file docker/laravel/Dockerfile \
            --tag app-php-fpm:production-${{ github.sha }} \
            --tag app-php-fpm:production-latest \
            --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
            --secret id=laravel_env_key,env=PROD_ENV_ENCRYPTION_KEY \
            --load \
            .
        env:
          PROD_ENV_ENCRYPTION_KEY: ${{ secrets.PROD_ENV_ENCRYPTION_KEY }}

      - name: Verify image
        run: |
          docker run --rm app-php-fpm:production-latest \
            sh -c 'test -f .env && echo "✅ .env file exists" || (echo "❌ .env missing" && exit 1)'

          docker run --rm app-php-fpm:production-latest \
            php artisan --version

      - name: Save and deploy image
        run: |
          docker save app-php-fpm:production-latest | gzip > app-image.tar.gz
          # Transfer to servers and deploy
```

#### Local Development

For local development, you must also use encrypted environment:

```bash
# Create encrypted environment
php artisan env:encrypt
# This creates .env.encrypted in your project directory

# Build with encrypted environment
LARAVEL_ENV_ENCRYPTION_KEY="your-local-key" \
DOCKER_BUILDKIT=1 docker build \
  -f docker/laravel/Dockerfile \
  -t app-php-fpm:local \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  .
```

**Note**: All builds require encrypted environment content passed as a build argument.

#### Security Benefits

✅ **BuildKit Secrets Approach**:
- Encryption keys mounted at `/run/secrets/` - never persisted in image layers
- Keys don't appear in `docker history` or build cache
- Encrypted content can be in build cache (still encrypted - secure)
- No secrets in final image
- Supports key rotation without rebuilding infrastructure

#### Troubleshooting Build-Time Decryption

**Error: "Encryption key secret not found"**

```bash
# Ensure secret is passed with correct ID
DOCKER_BUILDKIT=1 docker build \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY \
  ...

# Verify environment variable is set
echo $LARAVEL_ENV_ENCRYPTION_KEY
# Should output your 32-character key
```

**Error: "Decryption failed - .env file not created"**

```bash
# Test decryption locally first
php artisan env:decrypt --env=production --key="your-key"

# Verify the encrypted file exists
ls -lh .env.encrypted
```

**Error: "COPY failed: file not found"**

```bash
# Ensure the encrypted file exists in your project directory
ls -lh .env.encrypted

# If missing, create it
php artisan env:encrypt --env=production
```

---

### Approach 2: CI/CD Decryption (Current SSH Deployments)

This approach is used for current SSH-based deployments where Docker is not used for production.

#### GitHub Secrets Configuration

Each environment requires two secrets in GitHub:

1. Navigate to: **Repository → Settings → Environments** → Select environment
2. Add two secrets:
   - `ENV_ENCRYPTION_KEY` - 32-character encryption key
   - `ENV_ENCRYPTED_CONTENT` - Entire encrypted file content (not base64 encoded)

#### Deployment Flow (SSH-based)

The current CI/CD workflow handles decryption:

1. Checkout code
2. Write `ENV_ENCRYPTED_CONTENT` to temporary file
3. Install PHP and composer
4. Decrypt: `php artisan env:decrypt --env={environment} --force`
5. SSH to servers and deploy
6. Restart supervisor services

**Security Benefits:**
- No environment files in repository
- Encryption keys only in GitHub secrets
- Environment decrypted on deployment server only

---

### Key Rotation

Rotate encryption keys every 90 days:

```bash
# 1. Retrieve current encrypted content from GitHub secrets
# (Copy from GitHub UI or use gh CLI)
echo "$GITHUB_SECRET_PROD_ENV_ENCRYPTED_CONTENT" | base64 -d > .env.encrypted

# 2. Decrypt with current key
export LARAVEL_ENV_ENCRYPTION_KEY="current-key"
php artisan env:decrypt --env=production --force

# 3. Generate new key and re-encrypt
NEW_KEY=$(openssl rand -base64 32 | head -c 32)
php artisan env:encrypt --env=production --key="$NEW_KEY" --force
echo "New encryption key: $NEW_KEY"

# 4. Update GitHub Secrets
# Navigate to: Settings → Secrets → Actions
# Update PROD_ENV_ENCRYPTION_KEY with $NEW_KEY
# Update PROD_ENV_ENCRYPTED_CONTENT with content of .env.encrypted file

# 5. Clean up plaintext file (keep encrypted version for Docker builds)
rm -f .env.production
unset LARAVEL_ENV_ENCRYPTION_KEY NEW_KEY
```

### Local Testing of Encrypted Environments

Test decryption locally before deploying:

```bash
# Test with encryption key
export LARAVEL_ENV_ENCRYPTION_KEY="your-key"
php artisan env:decrypt --env=production
cat .env

# Clean up
rm .env
unset LARAVEL_ENV_ENCRYPTION_KEY
```

### Migration Path to Docker Builds

**Current State (SSH-based):**
- Environment files managed via GitHub secrets
- Decrypted during CI/CD before SSH deployment
- Git pull + composer install + supervisor restart

**Future State (Docker-based):**
- Encrypted environments in GitHub secrets (same)
- Decrypted during Docker image build (new)
- Immutable Docker images with baked-in `.env`
- Container orchestration deployment

**Transition Steps:**
1. ✅ Implement build-time decryption in Dockerfile (complete)
2. Test Docker builds with encrypted environments in dev/sandbox
3. Update CI/CD workflows to build Docker images
4. Deploy to preprod using Docker images
5. Migrate production to Docker-based deployment
6. Deprecate SSH-based deployment workflow

---

## Quick Reference

### Common Commands by Environment

**Local Development:**
```bash
# Start
docker compose --profile web --profile local --profile group1 --profile group2 --profile group3 up -d

# Stop
docker compose down

# Rebuild (non-encrypted for local dev)
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile . && docker compose up -d

# Or use docker-compose
docker-compose build app && docker-compose --profile web --profile local up -d
```

**Sandbox:**
```bash
# Ensure .env.encrypted exists in project directory

# Build with encrypted environment
LARAVEL_ENV_ENCRYPTION_KEY="sandbox-key" \
DOCKER_BUILDKIT=1 docker build -t app-php-fpm:sandbox -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY .

# See Section c) for full command with all --scale flags
docker compose --profile web --profile local --profile group1 --profile group2 --profile group3 up -d \
  --scale local-market-states-worker=8 \
  # ... (22 more --scale flags)
```

**Production (Server A):**
```bash
# Ensure .env.encrypted exists in project directory

# Build with encrypted environment first (inline - no export needed)
LARAVEL_ENV_ENCRYPTION_KEY="production-key" \
DOCKER_BUILDKIT=1 docker build -t app-php-fpm:production -f docker/laravel/Dockerfile \
  --build-arg ENCRYPTED_ENV_CONTENT="$(cat .env.encrypted)" \
  --secret id=laravel_env_key,env=LARAVEL_ENV_ENCRYPTION_KEY .

# See Section c) for full command with all --scale flags
docker compose --profile web --profile group1 up -d \
  --scale local-market-commodities-settlement-worker=8 \
  # ... (7 more --scale flags)
```

### Troubleshooting

**Issue**: Services not starting
```bash
# Check logs
docker compose logs app

# Check health status
docker compose ps

# Restart services
docker compose restart
```

**Issue**: Workers not processing jobs
```bash
# Check worker logs
docker compose logs -f default-worker

# Check Redis connection
docker compose exec redis redis-cli ping

# Restart workers
docker compose restart default-worker
```

**Issue**: Database connection errors
```bash
# Check MySQL is running
docker compose ps mysql

# Test connection
docker compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Support

For issues or questions:
- Check logs: `docker compose logs -f`
- Verify health: `docker compose ps`
- Review this documentation
- Contact DevOps team
