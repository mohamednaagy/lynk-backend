# Zero-Downtime Deployment Scripts

## Overview

This directory contains scripts for implementing zero-downtime deployments with automatic rollback capabilities for Docker Compose applications.

## deploy-with-rollback.sh

A comprehensive deployment script that implements a **rolling deployment strategy** to achieve zero-downtime updates with automatic rollback on failure.

### How It Works

The script follows a carefully orchestrated deployment process:

1. **Backup Current State**
   - Captures the current Docker image tag
   - Saves deployment state for potential rollback

2. **Rolling Update (Scale Up)**
   - Doubles the number of containers (old + new running simultaneously)
   - New containers start alongside existing ones
   - No service interruption during this phase

3. **Health Check Verification**
   - Monitors container health for up to 5 minutes
   - Checks both Docker health checks and container running status
   - Requires 80% of containers to be healthy to proceed

4. **Scale Down (Remove Old)**
   - Once new containers are verified healthy
   - Scales down to target numbers
   - Removes old containers gracefully

5. **Automatic Rollback**
   - Triggers if health checks fail at any stage
   - Stops new containers
   - Restarts previous version
   - Verifies rollback succeeded

### Configuration

Key parameters can be adjusted at the top of the script:

```bash
HEALTH_CHECK_TIMEOUT=300      # Max time to wait for health (seconds)
HEALTH_CHECK_INTERVAL=10      # Check every N seconds
MIN_HEALTHY_PERCENTAGE=80     # Required healthy percentage
```

### Features

- **Zero Downtime**: Always maintains running containers
- **Automatic Rollback**: Reverts to previous version on failure
- **Health Verification**: Comprehensive health checking
- **Detailed Logging**: Color-coded output for easy monitoring
- **Safe Deployment**: Multiple verification steps

### Exit Codes

- `0`: Deployment successful
- `1`: Deployment failed (rollback may have been performed)

### GitHub Actions Integration

The script is automatically transferred to deployment servers and executed by the GitHub Actions workflow:

```yaml
- name: Deploy containers with zero-downtime
  run: |
    ssh server "/tmp/deploy-with-rollback.sh"
```

## Best Practices

### Health Checks

Ensure your services have proper health checks defined in `docker-compose.yml`:

```yaml
services:
  app:
    healthcheck:
      test: ["CMD", "php", "-r", "exit(0);"]
      interval: 30s
      timeout: 5s
      retries: 3
```

### Resource Planning

During rolling updates, container count temporarily doubles. Ensure your server has:
- Sufficient memory for 2x containers
- Available CPU resources
- Adequate network bandwidth

### Monitoring

The script provides detailed output including:
- Container health status
- Deployment progress
- Rollback triggers
- Final deployment state

### Testing Recommendations

Before production deployment:
1. Test the script in a staging environment
2. Verify health checks work correctly
3. Simulate failure scenarios to test rollback
4. Monitor resource usage during scaled-up phase

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

- Increase `HEALTH_CHECK_TIMEOUT`
- Review container logs for startup issues
- Verify health check command is correct

## References

This implementation is based on Docker Compose zero-downtime deployment best practices:

- [Blue-Green Deployments Guide](https://thomasbandt.com/blue-green-deployments)
- [Docker Rollout Tool](https://github.com/wowu/docker-rollout)
- [Zero-Downtime with Docker Compose](https://jmh.me/blog/zero-downtime-docker-compose-deploy)
- [Rolling Updates Best Practices](https://reintech.io/blog/zero-downtime-deployments-docker-compose-rolling-updates)
