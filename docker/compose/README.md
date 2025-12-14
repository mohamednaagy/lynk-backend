# Docker Compose Files - DEPRECATED

**As of 2025-12-14**, these individual compose files have been deprecated and consolidated into a single `docker-compose.yml` file at the repository root.

## Migration to Profiles

All services from these separate files are now organized into profiles in `/docker-compose.yml`:

| Old Files | New Profile | Command |
|-----------|-------------|---------|
| `docker-compose.web.yml` | `web` | `docker compose --profile web up -d` |
| `docker-compose.local.yml` | `local` | `docker compose --profile local up -d` |
| `docker-compose.mailhog.yml` | `local` | (included in local profile) |
| `docker-compose.nightwatch.yml` | `local` | (included in local profile) |
| `docker-compose.group1.yml` | `group1` | `docker compose --profile group1 up -d` |
| `docker-compose.group2.yml` | `group2` | `docker compose --profile group2 up -d` |
| `docker-compose.group3.yml` | `group3` | `docker compose --profile group3 up -d` |

## Examples

### Local Development
```bash
# Old way
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.local.yml \
               up -d

# New way
docker compose --profile web --profile local up -d
```

### Production Server A (web + group1)
```bash
# Old way
docker compose -f docker/compose/docker-compose.web.yml \
               -f docker/compose/docker-compose.group1.yml \
               up -d

# New way
docker compose --profile web --profile group1 up -d
```

### Scaling Workers
```bash
# Old way
docker compose -f docker/compose/docker-compose.group1.yml \
               up -d --scale worker-name=3

# New way
docker compose --profile group1 up -d --scale local-market-order-initiation=3
```

## Backup Files

All original files have been backed up with `.bak` extensions in this directory for reference. These can be safely deleted after verifying the new setup works correctly.

## Documentation

See `/docker.md` for complete documentation on the new profile-based approach.
