#!/bin/bash
# Zero-Downtime Deployment Script with Automatic Rollback
# This script implements a rolling deployment strategy for Docker Compose

set -euo pipefail

# Configuration
COMPOSE_FILE="docker-compose.yml"
DEPLOYMENT_DIR="/var/www/lynk-backend"
HEALTH_CHECK_TIMEOUT=300  # 5 minutes
HEALTH_CHECK_INTERVAL=10  # 10 seconds
MIN_HEALTHY_PERCENTAGE=70 # At least 70% of containers must be healthy
SCALE_DOWN_WAIT_TIME=30   # Wait 30 seconds after scaling down before final check

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Function to get current image tag
get_current_image() {
    docker images --format "{{.Repository}}:{{.Tag}}" | grep "^app-php-fpm:" | grep -v "latest" | head -n 1 || echo ""
}

# Function to backup current deployment state
backup_deployment_state() {
    log_info "Backing up current deployment state..."

    # Save current image tag
    CURRENT_IMAGE=$(get_current_image)
    echo "$CURRENT_IMAGE" > /tmp/last_known_good_image.txt

    # Save list of running containers
    docker compose ps --format json > /tmp/last_deployment_state.json 2>/dev/null || true

    log_success "Deployment state backed up: $CURRENT_IMAGE"
}

# Function to check container health
check_container_health() {
    local container_name=$1
    local health_status=$(docker inspect --format='{{.State.Health.Status}}' "$container_name" 2>/dev/null || echo "none")

    # If no health check defined, check if container is running
    if [ "$health_status" = "none" ]; then
        docker inspect --format='{{.State.Status}}' "$container_name" 2>/dev/null | grep -q "running" && echo "healthy" || echo "unhealthy"
    else
        echo "$health_status"
    fi
}

# Function to get all service containers
get_all_containers() {
    docker compose ps --format json | jq -r '.Name' 2>/dev/null || docker compose ps -q
}

# Function to check overall deployment health
check_deployment_health() {
    log_info "Checking deployment health..."

    local total_containers=0
    local healthy_containers=0
    local unhealthy_containers=0

    # Get all containers
    while IFS= read -r container; do
        if [ -n "$container" ]; then
            total_containers=$((total_containers + 1))
            health=$(check_container_health "$container")

            if [ "$health" = "healthy" ]; then
                healthy_containers=$((healthy_containers + 1))
                log_success "Container $container is healthy"
            else
                unhealthy_containers=$((unhealthy_containers + 1))
                log_warning "Container $container is unhealthy (status: $health)"
                # Show recent logs for unhealthy container
                docker logs --tail=20 "$container" 2>&1 | head -10
            fi
        fi
    done < <(get_all_containers)

    if [ $total_containers -eq 0 ]; then
        log_error "No containers found!"
        return 1
    fi

    local health_percentage=$((healthy_containers * 100 / total_containers))

    log_info "Health Status: $healthy_containers/$total_containers healthy ($health_percentage%)"

    if [ $health_percentage -ge $MIN_HEALTHY_PERCENTAGE ]; then
        log_success "Deployment is healthy ($health_percentage% >= $MIN_HEALTHY_PERCENTAGE%)"
        return 0
    else
        log_error "Deployment is unhealthy ($health_percentage% < $MIN_HEALTHY_PERCENTAGE%)"
        log_warning "Some containers may still be starting - this is normal during deployment"
        return 1
    fi
}

# Function to wait for containers to be healthy
wait_for_healthy_deployment() {
    log_info "Waiting for containers to become healthy (timeout: ${HEALTH_CHECK_TIMEOUT}s)..."

    local elapsed=0

    while [ $elapsed -lt $HEALTH_CHECK_TIMEOUT ]; do
        if check_deployment_health; then
            log_success "All containers are healthy after ${elapsed}s"
            return 0
        fi

        log_info "Waiting ${HEALTH_CHECK_INTERVAL}s before next check... (${elapsed}/${HEALTH_CHECK_TIMEOUT}s elapsed)"
        sleep $HEALTH_CHECK_INTERVAL
        elapsed=$((elapsed + HEALTH_CHECK_INTERVAL))
    done

    log_error "Timeout reached after ${HEALTH_CHECK_TIMEOUT}s - containers did not become healthy"
    return 1
}

# Function to perform rollback
perform_rollback() {
    log_error "INITIATING AUTOMATIC ROLLBACK..."

    if [ ! -f /tmp/last_known_good_image.txt ]; then
        log_error "No backup image found - cannot rollback!"
        return 1
    fi

    ROLLBACK_IMAGE=$(cat /tmp/last_known_good_image.txt)

    if [ -z "$ROLLBACK_IMAGE" ]; then
        log_error "Backup image is empty - cannot rollback!"
        return 1
    fi

    log_info "Rolling back to previous image: $ROLLBACK_IMAGE"

    # Stop current containers
    log_info "Stopping current containers..."
    docker compose down || true

    # Tag the rollback image as latest
    docker tag "$ROLLBACK_IMAGE" app-php-fpm:latest || {
        log_error "Failed to tag rollback image"
        return 1
    }

    # Start containers with rollback image
    log_info "Starting containers with rollback image..."
    docker compose --profile web --profile group1 --profile group2 --profile group3 --profile dev-services --profile observability up -d \
        --scale local-market-states-worker=8 \
        --scale local-market-webhooks-worker=5 \
        --scale local-market-process-worker=8 \
        --scale local-market-expire-trader-order-worker=8 \
        --scale local-market-commodities-settlement-worker=8 \
        --scale local-market-eligible-quantities-worker=8 \
        --scale local-market-order-inventories-units-logging=5 \
        --scale local-market-order-initiation=5 \
        --scale trader-order-initiation=5 \
        --scale local-market-generate-units=1 \
        --scale hold-eligible-local-order-units-worker=5 \
        --scale complete-commodities-purchased-local-market-worker=5 \
        --scale buy-commodities-local-market-orders-worker=5 \
        --scale refresh-eligibilities-worker=1 \
        --scale notifications-worker=5 \
        --scale create-trader-orders-worker=8 \
        --scale default-worker=8 \
        --scale bursam-worker=1 \
        --scale apply-order-fees-worker=1 \
        --scale complete-purchasing-local-market-orders-worker=5 \
        --scale message-queue-worker=2

    # Give containers time to start
    sleep 15

    # Verify rollback health
    if check_deployment_health; then
        log_success "ROLLBACK SUCCESSFUL - Service restored to previous version"
        return 0
    else
        log_error "ROLLBACK FAILED - Manual intervention required!"
        return 1
    fi
}

# Function to perform rolling deployment
perform_rolling_deployment() {
    log_info "Starting rolling deployment..."

    cd "$DEPLOYMENT_DIR" || {
        log_error "Failed to change to deployment directory: $DEPLOYMENT_DIR"
        exit 1
    }

    # Backup current state
    backup_deployment_state

    log_info "Current running containers:"
    docker compose ps --format 'table {{.Name}}\t{{.Status}}' || true

    # Start new containers alongside old ones (scale up)
    log_info "Scaling up new containers..."
    docker compose --profile web --profile group1 --profile group2 --profile group3 --profile dev-services --profile observability up -d --no-recreate \
        --scale local-market-states-worker=16 \
        --scale local-market-webhooks-worker=10 \
        --scale local-market-process-worker=16 \
        --scale local-market-expire-trader-order-worker=16 \
        --scale local-market-commodities-settlement-worker=16 \
        --scale local-market-eligible-quantities-worker=16 \
        --scale local-market-order-inventories-units-logging=10 \
        --scale local-market-order-initiation=10 \
        --scale trader-order-initiation=10 \
        --scale local-market-generate-units=2 \
        --scale hold-eligible-local-order-units-worker=10 \
        --scale complete-commodities-purchased-local-market-worker=10 \
        --scale buy-commodities-local-market-orders-worker=10 \
        --scale refresh-eligibilities-worker=2 \
        --scale notifications-worker=10 \
        --scale create-trader-orders-worker=16 \
        --scale default-worker=16 \
        --scale bursam-worker=2 \
        --scale apply-order-fees-worker=2 \
        --scale complete-purchasing-local-market-orders-worker=10 \
        --scale message-queue-worker=4 || {
        log_error "Failed to scale up new containers"
        return 1
    }

    # Wait for new containers to be healthy
    if ! wait_for_healthy_deployment; then
        log_error "New containers failed health checks - rolling back..."
        perform_rollback
        exit 1
    fi

    # Scale down to target numbers (removes old containers)
    log_info "Scaling down to target numbers (removing old containers)..."
    docker compose --profile web --profile group1 --profile group2 --profile group3 --profile dev-services --profile observability up -d \
        --scale local-market-states-worker=8 \
        --scale local-market-webhooks-worker=5 \
        --scale local-market-process-worker=8 \
        --scale local-market-expire-trader-order-worker=8 \
        --scale local-market-commodities-settlement-worker=8 \
        --scale local-market-eligible-quantities-worker=8 \
        --scale local-market-order-inventories-units-logging=5 \
        --scale local-market-order-initiation=5 \
        --scale trader-order-initiation=5 \
        --scale local-market-generate-units=1 \
        --scale hold-eligible-local-order-units-worker=5 \
        --scale complete-commodities-purchased-local-market-worker=5 \
        --scale buy-commodities-local-market-orders-worker=5 \
        --scale refresh-eligibilities-worker=1 \
        --scale notifications-worker=5 \
        --scale create-trader-orders-worker=8 \
        --scale default-worker=8 \
        --scale bursam-worker=1 \
        --scale apply-order-fees-worker=1 \
        --scale complete-purchasing-local-market-orders-worker=5 \
        --scale message-queue-worker=2

    # Wait for containers to stabilize after scaling down
    log_info "Waiting ${SCALE_DOWN_WAIT_TIME}s for containers to stabilize after scale-down..."
    sleep $SCALE_DOWN_WAIT_TIME

    # Give containers additional time to complete startup if needed
    log_info "Performing final health verification..."
    local final_check_attempts=3
    local attempt=1

    while [ $attempt -le $final_check_attempts ]; do
        log_info "Final health check attempt $attempt/$final_check_attempts..."

        if check_deployment_health; then
            log_success "Final health check passed!"
            break
        fi

        if [ $attempt -lt $final_check_attempts ]; then
            log_warning "Health check attempt $attempt failed, waiting 15s before retry..."
            sleep 15
        else
            log_error "Final health check failed after $final_check_attempts attempts - rolling back..."
            perform_rollback
            exit 1
        fi

        attempt=$((attempt + 1))
    done

    # Clean up old containers
    log_info "Cleaning up old containers..."
    docker compose ps -a --filter "status=exited" -q | xargs -r docker rm 2>/dev/null || true

    log_success "Rolling deployment completed successfully!"

    # Show final state
    log_info "Final deployment state:"
    docker compose ps --format 'table {{.Name}}\t{{.Status}}'
}

# Main execution
main() {
    log_info "========================================="
    log_info "Zero-Downtime Deployment with Rollback"
    log_info "========================================="

    # Perform rolling deployment
    if perform_rolling_deployment; then
        log_success "DEPLOYMENT SUCCESSFUL"
        exit 0
    else
        log_error "DEPLOYMENT FAILED"
        exit 1
    fi
}

# Run main function
main
