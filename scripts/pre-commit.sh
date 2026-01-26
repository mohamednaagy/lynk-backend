#!/bin/bash

set -e

echo " Running checks on staged files..."

STAGED_PHP_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' || true)

if [ -z "$STAGED_PHP_FILES" ]; then
  echo " No PHP files staged. Skipping checks."
  exit 0
fi

echo " Staged PHP files:"
echo "$STAGED_PHP_FILES"

# ----------------------------------
# PHPStan
# ----------------------------------
echo " Running PHPStan..."

vendor/bin/phpstan analyse $STAGED_PHP_FILES

echo " PHPStan passed"

echo " All checks passed. Proceeding with commit."