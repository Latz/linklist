#!/usr/bin/env bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

if ! command -v trivy >/dev/null 2>&1; then
    echo "ERROR: trivy not found on PATH (install: https://trivy.dev)" >&2
    exit 1
fi

echo "Running Trivy filesystem scan..."
trivy fs --config "$PROJECT_DIR/trivy.yaml" \
    --format template --template "@$SCRIPT_DIR/trivy-compact.tpl" \
    "$PROJECT_DIR"
