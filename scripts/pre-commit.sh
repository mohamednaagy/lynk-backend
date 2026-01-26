#!/bin/bash
set -e

export PATH="/usr/bin:/bin:/usr/local/bin:$PATH"

echo "🔍 Running checks on staged files..."

STAGED_PHP_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' || true)

if [ -z "$STAGED_PHP_FILES" ]; then
  echo "⚠️  No staged PHP files detected. Did you forget git add?"
  exit 0
fi

echo "📄 Staged PHP files:"
echo "$STAGED_PHP_FILES"

# ----------------------------------
# Laravel Pint (formatter)
# ----------------------------------
echo "🎨 Running Laravel Pint..."

vendor/bin/pint $STAGED_PHP_FILES

# Re-stage files in case Pint modified them
echo "$STAGED_PHP_FILES" | xargs git add

echo "✅ Pint formatting applied"

# ----------------------------------
# PHPStan (static analysis)
# ----------------------------------
echo "🐘 Running PHPStan..."

vendor/bin/phpstan analyse $STAGED_PHP_FILES

echo "✅ PHPStan passed"
