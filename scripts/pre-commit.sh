#!/bin/bash
set -e

export PATH="/usr/bin:/bin:/usr/local/bin:$PATH"

echo "🔍 Running checks on staged files..."

# Collect staged PHP files into an array using null-terminated strings to handle filenames with spaces
STAGED_PHP_FILES=()
while IFS= read -r -d '' file; do
    STAGED_PHP_FILES+=("$file")
done < <(git diff --cached --name-only --diff-filter=ACMR -z | grep -z '\.php$')

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
git add "${STAGED_PHP_FILES[@]}"

echo "✅ Pint formatting applied"

# ----------------------------------
# PHPStan (static analysis)
# ----------------------------------
echo "🐘 Running PHPStan..."

vendor/bin/phpstan analyse "${STAGED_PHP_FILES[@]}"

echo "✅ PHPStan passed"
