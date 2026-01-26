#!/bin/bash
set -e

export PATH="/usr/bin:/bin:/usr/local/bin:$PATH"

echo "🔍 Running checks on staged files..."

# Collect staged PHP files into an array using null-terminated strings to handle filenames with spaces
mapfile -d '' STAGED_PHP_FILES < <(git diff --cached --name-only --diff-filter=ACMR -z | grep -z '\.php$')

if [ ${#STAGED_PHP_FILES[@]} -eq 0 ]; then
  echo "⚠️  No staged PHP files detected. Did you forget git add?"
  exit 0
fi

echo "📄 Staged PHP files:"
printf '%s\n' "${STAGED_PHP_FILES[@]}"

# ----------------------------------
# Laravel Pint (formatter)
# ----------------------------------
echo "🎨 Running Laravel Pint..."

vendor/bin/pint "${STAGED_PHP_FILES[@]}"

# Re-stage files in case Pint modified them
# Use a for loop to properly handle each file individually
for file in "${STAGED_PHP_FILES[@]}"; do
  if [[ -n "$file" ]]; then
    git add "$file"
  fi
done

echo "✅ Pint formatting applied"

# ----------------------------------
# PHPStan (static analysis)
# ----------------------------------
echo "🐘 Running PHPStan..."

vendor/bin/phpstan analyse "${STAGED_PHP_FILES[@]}"

echo "✅ PHPStan passed"