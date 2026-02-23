# Deployment Scripts & Actions

## Overview

This directory contains the unified deployment script used by all environment workflows. The `.github/actions/` directory contains reusable composite actions that eliminate duplication across workflows.

## Architecture

```
.github/
├── actions/
│   ├── setup-ssh/           # SSH key configuration
│   ├── fetch-pr-info/       # PR creator/merger/reviewer lookup
│   ├── slack-notify/        # Deployment Slack notification (jq-based)
│   ├── deploy-to-server/    # Docker image transfer + deployment
│   └── post-deploy/         # Post-deploy artisan commands
├── scripts/
│   ├── README.md            # This file
│   └── deploy.sh            # Unified zero-downtime deployment script
└── workflows/
    ├── _build.yml           # Reusable build workflow (Docker image + .env)
    ├── deploy-to-dev.yml    # Dev deployment + regression tests
    ├── deploy-to-sandbox.yml
    ├── deploy-to-preprod.yml
    └── deploy-to-production.yml
```

## deploy.sh

A unified zero-downtime deployment script for all environments. Replaces the previous `deploy-with-rollback.sh` (dev/sandbox) and `deploy-preprod.sh` (preprod/production).

### Usage

```bash
./deploy.sh                                    # All profiles (dev/sandbox)
./deploy.sh 'web,group1,observability'         # Specific profiles (preprod/prod per-server)
```

### How It Works

1. **Backup** - Captures current Docker image tag and deployment state
2. **Scale Up** - Doubles container count (old + new running simultaneously)
3. **Health Check** - Monitors for up to 5 minutes, requires 70%+ healthy
4. **Scale Down** - Removes old containers, scales to target numbers
5. **Verify** - Final health check with 3 retry attempts
6. **Rollback** - Automatic if health checks fail at any stage

### Configuration

```bash
HEALTH_CHECK_TIMEOUT=300      # Max wait for health (seconds)
HEALTH_CHECK_INTERVAL=10      # Check interval (seconds)
MIN_HEALTHY_PERCENTAGE=70     # Required healthy percentage
SCALE_DOWN_WAIT_TIME=30       # Stabilization wait after scale-down
STORAGE_WARNING_THRESHOLD=70  # Disk usage warning threshold (%)
```

### Profile-Based Scaling

The script automatically determines container scale based on active profiles:

| Profile | Key Workers |
|---------|------------|
| `group1` | commodities-settlement, eligible-quantities, order-inventories, trader-order-initiation |
| `group2` | states, process, hold-eligible, create-trader-orders, default |
| `group3` | webhooks, expire-trader-order, generate-units |

The `schedule` profile is auto-added when `group3` is active.

## Composite Actions

### setup-ssh
Configures SSH key and `~/.ssh/config` with `StrictHostKeyChecking=no` and `ConnectTimeout=30`. All downstream SSH/SCP commands inherit this config automatically.

### fetch-pr-info
Queries GitHub API for PR creator, merger, and approved reviewers given a commit SHA.

### slack-notify
Builds Slack notification payloads using `jq` (instead of fragile `sed` replacements). Properly handles special characters in PR titles and commit messages.

### deploy-to-server
Transfers Docker image and files to server, loads image, tags as latest, and executes the deployment script. Supports both direct deploy (dev/sandbox) and staging directory (preprod/prod) patterns.

### post-deploy
Runs artisan commands inside the app container. Supports both direct container name (`lynk-backend-app`) and profile-based discovery (`docker compose --profile web ps -q app`).

## Troubleshooting

### Deployment Fails Immediately
- Check Docker image loaded correctly
- Verify `docker-compose.yml` is valid
- Ensure services have health checks defined

### Rollback Fails
- Manual intervention required
- Check `/tmp/last_known_good_image.txt` for previous version
- Manually tag and deploy last known good image

### Containers Not Becoming Healthy
- Increase `HEALTH_CHECK_TIMEOUT` in `deploy.sh`
- Review container logs for startup issues
- Verify health check command is correct

### Health Check Fails During Scale-Down
The script includes 30s stabilization wait + 3 retry attempts (15s apart) = ~75s grace period for containers to become healthy after scale-down.

## References

- [Blue-Green Deployments Guide](https://thomasbandt.com/blue-green-deployments)
- [Docker Rollout Tool](https://github.com/wowu/docker-rollout)
- [Zero-Downtime with Docker Compose](https://jmh.me/blog/zero-downtime-docker-compose-deploy)
- [Rolling Updates Best Practices](https://reintech.io/blog/zero-downtime-deployments-docker-compose-rolling-updates)
