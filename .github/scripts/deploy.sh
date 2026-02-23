#!/bin/bash
# Zero-Downtime Deployment Script with Automatic Rollback
# Unified script for all environments (dev, sandbox, preprod, production)
# Supports profile-based deployments across multiple servers
#
# Usage: ./deploy.sh [profiles]
#   profiles: Comma-separated list of Docker Compose profiles (default: all profiles for dev/sandbox)
#   Examples:
#     ./deploy.sh                                    # Dev/sandbox: all profiles
#     ./deploy.sh 'web,group1,observability'         # Preprod/prod: specific server profiles

set -euo pipefail

# Configuration
PROFILES="${1:-web,group1,group2,group3,dev-services,observability,schedule}"
DEPLOYMENT_DIR="/var/www/backend-pipeline"
HEALTH_CHECK_TIMEOUT=300  # 5 minutes
HEALTH_CHECK_INTERVAL=10  # 10 seconds
MIN_HEALTHY_PERCENTAGE=70 # At least 70% of containers must be healthy
SCALE_DOWN_WAIT_TIME=30   # Wait 30 seconds after scaling down before final check
STORAGE_WARNING_THRESHOLD=70 # Warn if disk usage exceeds this percentage

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info()    { echo -e "${BLUE}[INFO] $1${NC}"; }
log_success() { echo -e "${GREEN}[SUCCESS] $1${NC}"; }
log_warning() { echo -e "${YELLOW}[WARNING] $1${NC}"; }
log_error()   { echo -e "${RED}[ERROR] $1${NC}"; }

# Convert comma-separated profiles to --profile flags
get_profile_flags() {
    local profiles="$PROFILES"
    # Auto-add schedule profile for group3
    if [[ "$profiles" == *"group3"* ]] && [[ "$profiles" != *"schedule"* ]]; then
        profiles="${profiles},schedule"
        log_info "Added 'schedule' profile because 'group3' is present" >&2
    fi
    echo "$profiles" | tr ',' '\n' | sed 's/^/--profile /' | tr '\n' ' '
}

PROFILE_FLAGS=$(get_profile_flags)

# Function to get scale flags based on active profiles
get_scale_flags() {
    local scale_flags=""

    if [[ "$PROFILES" == *"group1"* ]]; then
        scale_flags="$scale_flags --scale local-market-commodities-settlement-worker=8"
        scale_flags="$scale_flags --scale local-market-eligible-quantities-worker=4"
        scale_flags="$scale_flags --scale local-market-order-inventories-units-logging=5"
        scale_flags="$scale_flags --scale local-market-order-initiation=1"
        scale_flags="$scale_flags --scale trader-order-initiation=5"
        scale_flags="$scale_flags --scale complete-commodities-purchased-local-market-worker=3"
        scale_flags="$scale_flags --scale buy-commodities-local-market-orders-worker=3"
        scale_flags="$scale_flags --scale apply-order-fees-worker=1"
    fi

    if [[ "$PROFILES" == *"group2"* ]]; then
        scale_flags="$scale_flags --scale local-market-states-worker=8"
        scale_flags="$scale_flags --scale local-market-process-worker=8"
        scale_flags="$scale_flags --scale hold-eligible-local-order-units-worker=5"
        scale_flags="$scale_flags --scale refresh-eligibilities-worker=1"
        scale_flags="$scale_flags --scale notifications-worker=3"
        scale_flags="$scale_flags --scale create-trader-orders-worker=8"
        scale_flags="$scale_flags --scale default-worker=8"
        scale_flags="$scale_flags --scale bursam-worker=1"
        scale_flags="$scale_flags --scale complete-purchasing-local-market-orders-worker=5"
        scale_flags="$scale_flags --scale message-queue-worker=2"
        scale_flags="$scale_flags --scale local-market-eligible-quantities-worker=4"
        scale_flags="$scale_flags --scale complete-commodities-purchased-local-market-worker=2"
    fi

    if [[ "$PROFILES" == *"group3"* ]]; then
        scale_flags="$scale_flags --scale local-market-webhooks-worker=5"
        scale_flags="$scale_flags --scale local-market-expire-trader-order-worker=8"
        scale_flags="$scale_flags --scale local-market-generate-units=1"
        scale_flags="$scale_flags --scale notifications-worker=2"
        scale_flags="$scale_flags --scale buy-commodities-local-market-orders-worker=2"
        scale_flags="$scale_flags --scale bursam-worker=1"
    fi

    echo "$scale_flags"
}

# Function to get scale-up flags (2x normal for rolling deployment)
get_scale_up_flags() {
    local scale_flags=""

    if [[ "$PROFILES" == *"group1"* ]]; then
        scale_flags="$scale_flags --scale local-market-commodities-settlement-worker=16"
        scale_flags="$scale_flags --scale local-market-eligible-quantities-worker=8"
        scale_flags="$scale_flags --scale local-market-order-inventories-units-logging=10"
        scale_flags="$scale_flags --scale local-market-order-initiation=2"
        scale_flags="$scale_flags --scale trader-order-initiation=10"
        scale_flags="$scale_flags --scale complete-commodities-purchased-local-market-worker=6"
        scale_flags="$scale_flags --scale buy-commodities-local-market-orders-worker=6"
        scale_flags="$scale_flags --scale apply-order-fees-worker=2"
    fi

    if [[ "$PROFILES" == *"group2"* ]]; then
        scale_flags="$scale_flags --scale local-market-states-worker=16"
        scale_flags="$scale_flags --scale local-market-process-worker=16"
        scale_flags="$scale_flags --scale hold-eligible-local-order-units-worker=10"
        scale_flags="$scale_flags --scale refresh-eligibilities-worker=2"
        scale_flags="$scale_flags --scale notifications-worker=6"
        scale_flags="$scale_flags --scale create-trader-orders-worker=16"
        scale_flags="$scale_flags --scale default-worker=16"
        scale_flags="$scale_flags --scale bursam-worker=2"
        scale_flags="$scale_flags --scale complete-purchasing-local-market-orders-worker=10"
        scale_flags="$scale_flags --scale message-queue-worker=4"
        scale_flags="$scale_flags --scale local-market-eligible-quantities-worker=8"
        scale_flags="$scale_flags --scale complete-commodities-purchased-local-market-worker=4"
    fi

    if [[ "$PROFILES" == *"group3"* ]]; then
        scale_flags="$scale_flags --scale local-market-webhooks-worker=10"
        scale_flags="$scale_flags --scale local-market-expire-trader-order-worker=16"
        scale_flags="$scale_flags --scale local-market-generate-units=2"
        scale_flags="$scale_flags --scale notifications-worker=4"
        scale_flags="$scale_flags --scale buy-commodities-local-market-orders-worker=4"
        scale_flags="$scale_flags --scale bursam-worker=2"
    fi

    echo "$scale_flags"
}

# Function to get current image tag
get_current_image() {
    local running_image
    running_image=$(docker compose -p lynk-backend $PROFILE_FLAGS ps -q 2>/dev/null | head -1 | xargs -r docker inspect --format='{{.Config.Image}}' 2>/dev/null || echo "")

    if [ -n "$running_image" ] && [ "$running_image" != "app-php-fpm:latest" ]; then
        echo "$running_image"
        return
    fi

    docker images --format "{{.Repository}}:{{.Tag}}" | grep "^app-php-fpm:" | grep -v "latest" | head -n 1 || echo ""
}

# Function to backup current deployment state
backup_deployment_state() {
    log_info "Backing up current deployment state..."
    CURRENT_IMAGE=$(get_current_image)
    echo "$CURRENT_IMAGE" > /tmp/last_known_good_image.txt
    docker compose -p lynk-backend $PROFILE_FLAGS ps --format json > /tmp/last_deployment_state.json 2>/dev/null || true
    log_success "Deployment state backed up: $CURRENT_IMAGE"
}

# Function to check container health
check_container_health() {
    local container_name=$1
    local health_status
    health_status=$(docker inspect --format='{{.State.Health.Status}}' "$container_name" 2>/dev/null || echo "none")

    if [ "$health_status" = "none" ]; then
        docker inspect --format='{{.State.Status}}' "$container_name" 2>/dev/null | grep -q "running" && echo "healthy" || echo "unhealthy"
    else
        echo "$health_status"
    fi
}

# Function to get all service containers
get_all_containers() {
    docker compose -p lynk-backend $PROFILE_FLAGS ps --format json | jq -r '.Name' 2>/dev/null || docker compose -p lynk-backend $PROFILE_FLAGS ps -q
}

# Function to check overall deployment health
check_deployment_health() {
    log_info "Checking deployment health..."

    local total_containers=0
    local healthy_containers=0

    while IFS= read -r container; do
        if [ -n "$container" ]; then
            total_containers=$((total_containers + 1))
            health=$(check_container_health "$container")

            if [ "$health" = "healthy" ]; then
                healthy_containers=$((healthy_containers + 1))
                log_success "Container $container is healthy"
            else
                log_warning "Container $container is unhealthy (status: $health)"
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

    docker compose -p lynk-backend $PROFILE_FLAGS down || true
    docker tag "$ROLLBACK_IMAGE" app-php-fpm:latest || {
        log_error "Failed to tag rollback image"
        return 1
    }

    SCALE_FLAGS=$(get_scale_flags)
    log_info "Starting containers with rollback image..."
    docker compose -p lynk-backend $PROFILE_FLAGS up -d $SCALE_FLAGS

    sleep 15

    if check_deployment_health; then
        log_success "ROLLBACK SUCCESSFUL - Service restored to previous version"
        return 0
    else
        log_error "ROLLBACK FAILED - Manual intervention required!"
        return 1
    fi
}

# Function to clean up old Docker images
cleanup_old_images() {
    log_info "Cleaning up old Docker images..."
    local latest_image_id
    latest_image_id=$(docker images --format '{{.ID}}' app-php-fpm:latest 2>/dev/null || echo "")

    if [ -z "$latest_image_id" ]; then
        log_warning "No latest image found, skipping image cleanup"
        return
    fi

    local old_images
    old_images=$(docker images --format '{{.ID}} {{.Repository}}:{{.Tag}}' app-php-fpm | grep -v "^${latest_image_id}" | awk '{print $1}' | sort -u)

    if [ -z "$old_images" ]; then
        log_info "No old images to clean up"
        return
    fi

    local removed=0 failed=0
    for image_id in $old_images; do
        if docker rmi "$image_id" 2>/dev/null; then
            removed=$((removed + 1))
        else
            failed=$((failed + 1))
        fi
    done
    log_success "Image cleanup complete: $removed removed, $failed skipped (still in use)"
}

# Function to check storage health
check_storage_health() {
    log_info "Checking storage health..."
    local warnings=0

    while IFS= read -r line; do
        local usage mount
        usage=$(echo "$line" | awk '{print $5}' | tr -d '%')
        mount=$(echo "$line" | awk '{print $6}')
        if [ "$usage" -ge "$STORAGE_WARNING_THRESHOLD" ]; then
            log_warning "STORAGE WARNING: $mount is at ${usage}% usage (threshold: ${STORAGE_WARNING_THRESHOLD}%)"
            warnings=$((warnings + 1))
        fi
    done < <(df -h 2>/dev/null | tail -n +2 | grep -vE '^(tmpfs|devtmpfs|overlay|shm)')

    local docker_usage
    docker_usage=$(docker system df --format '{{.Type}}\t{{.Size}}\t{{.Reclaimable}}' 2>/dev/null || echo "")
    if [ -n "$docker_usage" ]; then
        log_info "Docker storage usage:"
        echo "$docker_usage" | while IFS= read -r line; do log_info "  $line"; done
    fi

    if [ "$warnings" -gt 0 ]; then
        log_warning "RELEASE WARNING: $warnings filesystem(s) above ${STORAGE_WARNING_THRESHOLD}% usage"
    else
        log_success "Storage health OK: all filesystems below ${STORAGE_WARNING_THRESHOLD}%"
    fi
}

# Function to perform rolling deployment
perform_rolling_deployment() {
    log_info "Starting rolling deployment..."
    log_info "Profiles: $PROFILES"

    cd "$DEPLOYMENT_DIR" || {
        log_error "Failed to change to deployment directory: $DEPLOYMENT_DIR"
        exit 1
    }

    backup_deployment_state

    log_info "Current running containers:"
    docker compose -p lynk-backend $PROFILE_FLAGS ps --format 'table {{.Name}}\t{{.Status}}' || true

    SCALE_FLAGS=$(get_scale_flags)
    SCALE_UP_FLAGS=$(get_scale_up_flags)

    # Scale up: new containers alongside old ones
    log_info "Scaling up new containers..."
    docker compose -p lynk-backend $PROFILE_FLAGS up -d --no-recreate $SCALE_UP_FLAGS || {
        log_error "Failed to scale up new containers"
        return 1
    }

    if ! wait_for_healthy_deployment; then
        log_error "New containers failed health checks - rolling back..."
        perform_rollback
        exit 1
    fi

    # Scale down to target
    log_info "Scaling down to target numbers (removing old containers)..."
    docker compose -p lynk-backend $PROFILE_FLAGS up -d $SCALE_FLAGS

    log_info "Waiting ${SCALE_DOWN_WAIT_TIME}s for containers to stabilize..."
    sleep $SCALE_DOWN_WAIT_TIME

    # Final health verification with retries
    log_info "Performing final health verification..."
    local final_check_attempts=3 attempt=1
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

    # Cleanup
    docker compose -p lynk-backend $PROFILE_FLAGS ps -a --filter "status=exited" -q | xargs -r docker rm 2>/dev/null || true
    cleanup_old_images
    check_storage_health

    log_success "Rolling deployment completed successfully!"
    log_info "Final deployment state:"
    docker compose -p lynk-backend $PROFILE_FLAGS ps --format 'table {{.Name}}\t{{.Status}}'
}

# Main execution
main() {
    log_info "========================================="
    log_info "Zero-Downtime Deployment with Rollback"
    log_info "========================================="
    log_info "Profiles: $PROFILES"

    if perform_rolling_deployment; then
        log_success "DEPLOYMENT SUCCESSFUL"
        exit 0
    else
        log_error "DEPLOYMENT FAILED"
        exit 1
    fi
}

main
