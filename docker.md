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

### Deployment Profiles

The application uses Docker Compose profiles to organize services into logical deployment groups:

- **web**: PHP-FPM application (required for all scenarios)
- **local**: Development infrastructure (nginx, redis, mysql, dev tools)
- **observability**: Monitoring tools (nightwatch)
- **group1**: 8 workers for local market core processing
- **group2**: 13 workers for general & trader operations
- **group3**: 6 workers for supporting operations & external integrations

**Total**: 27 independent queue worker services across 3 worker groups

### Benefits of Profile-Based Architecture

1. **Simplified Deployment**: Use `--profile` flag instead of multiple `-f compose-file.yml` arguments
2. **Flexible Scaling**: Each worker can be scaled independently using `--scale worker-name=N`
3. **Clear Separation**: Services are logically grouped by function and deployment target
4. **Environment-Specific**: Easily enable/disable service groups per environment
5. **Single Source of Truth**: One `docker-compose.yml` file for all deployment scenarios
6. **Production Ready**: No hardcoded replica counts - all scaling done at runtime

### Queue Groups

**Group 1 - Local Market Core Processing (Server A):**
- Commodities settlement: `local_market_commodities_settlement`
- Eligible quantities tracking: `local_market_eligible_quantities`
- Order inventories logging: `local_market_order_inventories_units_logging`
- Order initiation: `local_market_order_initiation`
- Trader order initiation: `trader_order_initiation`
- Complete commodities: `complete_commodities_purchased_local_market`
- Buy commodities: `buy_commodities_local_market_orders`
- Apply order fees: `apply_order_fees`

**Group 2 - General & Trader Operations (Server B):**
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

**Group 3 - Supporting Operations & External Integrations (Server C):**
- Webhooks: `local_market_webhooks`
- Expire trader orders: `expire_trader_order`
- Generate units: `unit-inventory`
- Buy commodities: `buy_commodities_local_market_orders`
- Notifications: `notifications`
- Bursam integration: `bursam`

## b) File Map

```
docker/
├── laravel/
│   └── Dockerfile                      # Multi-stage PHP 8.2-fpm image with all required extensions
├── nginx/
│   └── default.conf                    # Nginx configuration with PHP-FPM proxy and health endpoint
└── compose/
    ├── README.md                       # Migration guide from old compose files to profiles
    ├── docker-compose.web.yml          # [DEPRECATED] Use docker-compose.yml with --profile web
    ├── docker-compose.local.yml        # [DEPRECATED] Use docker-compose.yml with --profile local
    ├── docker-compose.group1.yml       # [DEPRECATED] Use docker-compose.yml with --profile group1
    ├── docker-compose.group2.yml       # [DEPRECATED] Use docker-compose.yml with --profile group2
    ├── docker-compose.group3.yml       # [DEPRECATED] Use docker-compose.yml with --profile group3
    ├── docker-compose.mailhog.yml      # [DEPRECATED] Use docker-compose.yml with --profile local
    └── docker-compose.nightwatch.yml   # [DEPRECATED] Use docker-compose.yml with --profile observability
docker-compose.yml                       # Consolidated compose file with all services and profiles
.dockerignore                            # Excludes unnecessary files from Docker build context
docker.md                                # This documentation file
```

### Profile Organization

**docker-compose.yml** (Single consolidated file)
All services are organized into profiles for flexible deployment:

**Profile: `web`**
- Core `app` service (PHP-FPM)
- Required for all deployment scenarios
- Serves HTTP requests via FastCGI

**Profile: `local`**
- Local development infrastructure services
- nginx (80), redis (6379), mysql (3306)
- Development tools: phpMyAdmin (8081), MailHog (8025), Browserless (3002)
- All services include health checks
- Use for local development only

**Profile: `observability`**
- Nightwatch monitoring service
- Provides a long-running container for debugging and monitoring
- Independent profile for better separation of concerns

**Profile: `group1`** - Local Market Core Processing
- 8 worker services for local market core processing queues
- Workers: settlement, eligible quantities, inventories logging, order initiation, trader initiation, commodities completion, buy commodities, order fees
- Deploy on Server A in production

**Profile: `group2`** - General & Trader Operations
- 13 worker services for general operations and trader processing
- Workers: market states, market process, eligible quantities, hold units, commodities completion, refresh eligibilities, notifications, trader orders, default, bursam, purchasing completion, message queue
- Deploy on Server B in production

**Profile: `group3`** - Supporting Operations & External Integrations
- 6 worker services for supporting operations and external integrations
- Workers: webhooks, expire trader orders, generate units, buy commodities, notifications, bursam
- Deploy on Server C in production

### File Purposes

**docker/laravel/Dockerfile**
- Multi-stage build: composer dependencies + runtime image
- Base: `php:8.2-fpm-alpine`
- Installs: pdo_mysql, mbstring, bcmath, intl, zip, gd, opcache, redis
- Non-root user: `www-data` (uid/gid 1000)
- Production-optimized OPcache configuration
- Exposes port 9000 for FastCGI

**docker-compose.yml**
- Consolidated compose file with all services
- Services organized by profiles: web, local, observability, group1, group2, group3
- Replicas controlled via `--scale` flag at runtime (no hardcoded values)
- Single source of truth for all deployment scenarios

**.dockerignore**
- Excludes unnecessary files from Docker build context
- Prevents git history, node_modules, vendor, logs, and cache from being copied into images
- Significantly reduces image build time and size

## c) Migrating from Old Docker Setup

If you're coming from the old docker-compose setup (before December 2025), here's what changed:

### Old Setup (Before)
```bash
# Old way - using multiple compose files
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.local.yml \
               up -d

# Scaling workers
docker compose -f docker/compose/docker-compose.group1.yml \
               up -d --scale worker-name=3
```

### New Setup (Current)
```bash
# New way - using profiles
docker compose --profile web --profile local up -d

# Scaling workers
docker compose --profile group1 up -d --scale local-market-order-initiation=3
```

### Key Differences

1. **Single File**: All services now in one `docker-compose.yml` instead of 7 separate files
2. **Profiles Not Files**: Use `--profile` flag instead of `-f compose-file.yml`
3. **No Hardcoded Replicas**: Worker counts set at runtime via `--scale`, not in compose file
4. **Renamed Workers**: Some workers have group suffixes (e.g., `notifications-worker-group2`)
5. **New Profiles**: Added `observability` profile for nightwatch (previously in `local`)
6. **Standard Ports**: Nginx now on port 80 (was 8080)

### Migration Checklist

- [ ] Update deployment scripts to use `--profile` instead of `-f`
- [ ] Update CI/CD pipelines with new profile-based commands
- [ ] Review worker scaling requirements and update `--scale` flags
- [ ] Update monitoring to use new service names with group suffixes
- [ ] Test local development setup with new profile approach
- [ ] Verify production deployment works with new profiles

For detailed migration examples, see `docker/compose/README.md`.

## d) Environment-Specific Deployment Commands

This section provides ready-to-use commands for deploying the application in different environments with production-grade worker scaling.

### Local Development Environment

**Purpose**: Development with all services running locally at minimal scale (1 replica per worker)

**Build the image:**
```bash
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .
```

**Start all services:**
```bash
docker compose --profile web --profile local --profile group1 --profile group2 --profile group3 up -d
```

Access points:
- Application: http://localhost
- Nginx health: http://localhost/healthz
- phpMyAdmin: http://localhost:8081
- MailHog: http://localhost:8025
- Browserless: http://localhost:3002

**Note**: Local development runs all workers at scale 1 (default) for resource efficiency.

**Stop all services:**
```bash
docker compose down
```

### Sandbox Environment

**Purpose**: Single-server testing environment with all worker groups running together at production scale

**Command:**
```bash
docker compose --profile web --profile observability --profile group1 --profile group2 --profile group3 up -d \
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

**Total Workers**: 27 services with 142 total replicas across all groups

### Pre-Production Environment (3 Servers)

**Architecture**: Distributed deployment across 3 servers behind a load balancer

#### Server A (Pre-Prod) - Web + Group 1 Workers

```bash
# Build image
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .

# Start app + Group 1 workers with production scaling
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

**Group 1 Total**: 8 services, 42 replicas

#### Server B (Pre-Prod) - Web + Group 2 Workers

```bash
# Build image
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .

# Start app + Group 2 workers with production scaling
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

**Group 2 Total**: 12 services, 64 replicas

#### Server C (Pre-Prod) - Web + Group 3 Workers

```bash
# Build image
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .

# Start app + Group 3 workers with production scaling
docker compose --profile web --profile group3 up -d \
  --scale local-market-webhooks-worker=5 \
  --scale local-market-expire-trader-order-worker=8 \
  --scale local-market-generate-units=1 \
  --scale buy-commodities-local-market-orders-worker-group3=5 \
  --scale notifications-worker-group3=5 \
  --scale bursam-worker-group3=1
```

**Group 3 Total**: 6 services, 25 replicas

### Production Environment (3 Servers)

**Architecture**: Same as pre-production - distributed deployment across 3 servers behind a load balancer

#### Server A (Production) - Web + Group 1 Workers

```bash
# Build image (or pull from registry)
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .

# Start app + Group 1 workers with production scaling
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

#### Server B (Production) - Web + Group 2 Workers

```bash
# Build image (or pull from registry)
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .

# Start app + Group 2 workers with production scaling
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

#### Server C (Production) - Web + Group 3 Workers

```bash
# Build image (or pull from registry)
docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .

# Start app + Group 3 workers with production scaling
docker compose --profile web --profile group3 up -d \
  --scale local-market-webhooks-worker=5 \
  --scale local-market-expire-trader-order-worker=8 \
  --scale local-market-generate-units=1 \
  --scale buy-commodities-local-market-orders-worker-group3=5 \
  --scale notifications-worker-group3=5 \
  --scale bursam-worker-group3=1
```

### Worker Replica Summary by Environment

| Environment | Group 1 Replicas | Group 2 Replicas | Group 3 Replicas | Total Replicas |
|-------------|------------------|------------------|------------------|----------------|
| Local       | 8 (scale 1)      | 12 (scale 1)     | 6 (scale 1)      | 26             |
| Sandbox     | 42               | 64               | 25               | 131            |
| Pre-Prod    | 42               | 64               | 25               | 131            |
| Production  | 42               | 64               | 25               | 131            |

### Worker Scale Reference by Group

**Group 1 Workers:**
- `local-market-commodities-settlement-worker`: 8 replicas
- `local-market-eligible-quantities-worker-group1`: 8 replicas
- `local-market-order-inventories-units-logging`: 5 replicas
- `local-market-order-initiation`: 5 replicas
- `trader-order-initiation`: 5 replicas
- `complete-commodities-purchased-local-market-worker-group1`: 5 replicas
- `buy-commodities-local-market-orders-worker-group1`: 5 replicas
- `apply-order-fees-worker`: 1 replica

**Group 2 Workers:**
- `local-market-states-worker`: 8 replicas
- `local-market-process-worker`: 8 replicas
- `local-market-eligible-quantities-worker-group2`: 8 replicas
- `hold-eligible-local-order-units-worker`: 5 replicas
- `complete-commodities-purchased-local-market-worker-group2`: 5 replicas
- `refresh-eligibilities-worker`: 1 replica
- `notifications-worker-group2`: 5 replicas
- `create-trader-orders-worker`: 8 replicas
- `default-worker`: 8 replicas
- `bursam-worker-group2`: 1 replica
- `complete-purchasing-local-market-orders-worker`: 5 replicas
- `message-queue-worker`: 2 replicas

**Group 3 Workers:**
- `local-market-webhooks-worker`: 5 replicas
- `local-market-expire-trader-order-worker`: 8 replicas
- `local-market-generate-units`: 1 replica
- `buy-commodities-local-market-orders-worker-group3`: 5 replicas
- `notifications-worker-group3`: 5 replicas
- `bursam-worker-group3`: 1 replica

### Deployment Notes

1. **Local Development**: No scaling needed, all workers default to 1 replica
2. **Sandbox**: All groups on one server, use all --scale flags together
3. **Pre-Prod/Prod**: Each server runs app + one worker group with specific scaling
4. **Load Balancer**: Configure to distribute traffic across all 3 servers on port 9000 (FastCGI)
5. **Monitoring**: Use `docker compose ps` to verify all replicas are running healthy

## e) Environment Encryption & Management

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

## f) Environment Configuration

### Local Development (.env)

For local development, configure your `.env` file with containerized service hostnames:

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

## g) Additional Production Deployment Information

### Production Deployment Strategy

In production, deploy the application across three servers behind a classic load balancer:

- **Server A**: Base app + Worker Group 1 (8 services, 42 replicas)
- **Server B**: Base app + Worker Group 2 (12 services, 64 replicas)
- **Server C**: Base app + Worker Group 3 (6 services, 25 replicas)

Each server runs the base `app` service to handle HTTP requests via the load balancer, plus its designated worker group with production-scale replicas.

**For complete deployment commands with all --scale flags, see Section d) Environment-Specific Deployment Commands above.**

### Prerequisites (Each Server)

1. Docker 20.10+ and Docker Compose 2.0+ installed
2. Application code deployed (git clone or CI/CD)
3. `.env` file configured with production credentials (see Section f)
4. Image built and tagged: `docker build -t app-php-fpm:latest -f docker/laravel/Dockerfile .`
   - OR pull from a container registry if using CI/CD

### Quick Start Commands

**Server A (Production):**
```bash
cd /path/to/lynk-backend
# See Section d) for full command with all --scale flags
docker compose --profile web --profile group1 up -d \
  --scale local-market-commodities-settlement-worker=8 \
  # ... (see Section d for complete command)
```

**Server B (Production):**
```bash
cd /path/to/lynk-backend
# See Section d) for full command with all --scale flags
docker compose --profile web --profile group2 up -d \
  --scale local-market-states-worker=8 \
  # ... (see Section d for complete command)
```

**Server C (Production):**
```bash
cd /path/to/lynk-backend
# See Section d) for full command with all --scale flags
docker compose --profile web --profile group3 up -d \
  --scale local-market-webhooks-worker=5 \
  # ... (see Section d for complete command)
```

### Load Balancer Configuration

Configure your classic load balancer to distribute traffic across the servers. Each server runs the `app` service (PHP-FPM on port 9000):

- Server A: `<server-a-ip>:9000` (FastCGI)
- Server B: `<server-b-ip>:9000` (FastCGI)
- Server C: `<server-c-ip>:9000` (FastCGI)

**Using Nginx on Production Servers:**
If you prefer to use nginx as a reverse proxy on each production server (instead of direct FastCGI from the load balancer):

```bash
# Include the local profile to get nginx (without mysql/redis if using external services)
docker compose --profile web --profile group1 up -d nginx
```

Note: The `local` profile includes nginx, mysql, redis, and dev tools. In production, you may want to run nginx separately or use only the nginx service from the compose file while excluding mysql/redis (which should be external managed services).

### Scheduler Service (Run on ONE Server Only)

Laravel's task scheduler should run on exactly one server to prevent duplicate scheduled jobs.

**Note**: The current docker-compose.yml does not include a scheduler service. If you need to run the Laravel scheduler (`php artisan schedule:work`), you can either:

1. Run it manually in a separate container:
```bash
docker run -d --name laravel-scheduler \
  --env-file .env \
  --network lynk-backend_laravel \
  app-php-fpm:latest \
  php artisan schedule:work
```

2. Or add a scheduler profile to docker-compose.yml following the same pattern as the worker profiles.

**Important**: Only run the scheduler on one server to prevent duplicate scheduled jobs.

### Database Migrations

**Important**: Database migrations should be handled in your CI/CD pipeline **before** deploying the new container images to production servers.

If you need to run migrations manually on a server:

```bash
# Run migrations in the app container
docker compose exec app php artisan migrate --force

# Cache configuration and routes
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

### Scaling Worker Groups in Production

Production scaling is defined in **Section d) Environment-Specific Deployment Commands** with recommended replica counts for each worker.

**To adjust scaling dynamically:**

```bash
# View current replica counts
docker compose ps

# Scale specific workers (example)
docker compose --profile group1 up -d \
  --scale local-market-order-initiation=10 \
  --scale trader-order-initiation=7

# View all available worker service names
docker compose --profile group1 config --services
```

**Scaling Strategies:**
1. **Horizontal Scaling**: Adjust `--scale` values per worker based on queue depth and processing time
2. **Vertical Scaling**: Allocate more CPU/memory to specific servers in your infrastructure
3. **Queue Monitoring**: Monitor queue depths to determine which workers need more replicas

**Recommended Production Scaling** (see Section d for full details):
- High-volume queues (states, process, settlement): 8 replicas
- Medium-volume queues (notifications, trader orders): 5 replicas
- Low-volume queues (refresh, fees, bursam): 1 replica

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

## h) Health Checks & Logs

### Health Check Endpoints

All services include health checks. Check status with:

```bash
# Check status of all running services
docker compose ps

# Check status of specific profile services
docker compose --profile web --profile local ps
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

### Viewing Logs

**All services:**
```bash
# All running services
docker compose logs -f

# Specific profile services
docker compose --profile web --profile local logs -f
```

**Specific service:**
```bash
# App logs
docker compose logs -f app

# Specific worker logs
docker compose logs -f local-market-order-initiation

# Nginx logs
docker compose logs -f nginx

# Redis logs
docker compose logs -f redis
```

**Last 100 lines:**
```bash
docker compose logs --tail=100 app
```

**Laravel application logs** (inside container):
```bash
docker compose exec app tail -f storage/logs/laravel.log
```

### Testing Queue Processing

Dispatch a test job to verify workers are processing:

```bash
# Execute tinker in the app container
docker compose exec app php artisan tinker

# In tinker, dispatch a test job
>>> dispatch(function() { \Log::info('Queue test successful'); })->onQueue('default');
>>> exit

# Check default-worker logs (handles 'default' queue in group2)
docker compose logs -f default-worker
```

Expected output in logs: Job processed successfully.

### Monitoring Container Resource Usage

```bash
# CPU and memory usage
docker stats

# Specific containers
docker stats laravel-app worker-group1
```

## i) Changelog

### 2025-12-30 - Nginx Port and Observability Profile Updates
- **Changed**: Nginx port mapping from 8080 to standard port 80 for production readiness
- **Refactored**: Moved nightwatch service from `local` profile to new `observability` profile
- **Improved**: Better separation of concerns between development tools and monitoring services
- **Updated**: Documentation to reflect port changes and new profile structure

### 2025-12-14 - Docker Compose Profiles Refactoring
- **Refactored**: Consolidated all 7 separate compose files into single `docker-compose.yml`
- **Added**: Profile-based service organization (web, local, observability, group1, group2, group3)
- **Added**: `.dockerignore` file to exclude unnecessary files from Docker build context
- **Added**: Migration guide in `docker/compose/README.md` for transitioning from old compose files
- **Changed**: Deployment uses `--profile` flag instead of multiple `-f` flags
- **Removed**: Hardcoded `deploy.replicas` values - now controlled via `--scale` flag at runtime
- **Renamed**: Duplicate worker services across groups with suffixes for clarity
  - Example: `notifications-worker-group2`, `notifications-worker-group3`
  - Example: `buy-commodities-local-market-orders-worker-group1`, `buy-commodities-local-market-orders-worker-group3`
- **Simplified**: Single source of truth for all deployment scenarios
- **Updated**: All documentation commands to use profile-based approach
- **Optimized**: Docker build context with .dockerignore reduces build time and image size
- **Benefit**: Simplified deployment, better organization, runtime flexibility for replica scaling
- **Deprecated**: All files in `docker/compose/` directory (kept for reference only)

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

## j) Changelog (Historical)

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
