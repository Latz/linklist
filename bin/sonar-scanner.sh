#!/usr/bin/env bash
set -euo pipefail

# Read config from sonar-project.properties
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
PROJECT_KEY=$(grep 'sonar.projectKey=' "$SCRIPT_DIR/sonar-project.properties" | cut -d= -f2 | tr -d '[:space:]')
ORGANIZATION=$(grep 'sonar.organization=' "$SCRIPT_DIR/sonar-project.properties" | cut -d= -f2 | tr -d '[:space:]')
SONAR_HOST=$(grep 'sonar.host.url=' "$SCRIPT_DIR/sonar-project.properties" | cut -d= -f2 | tr -d '[:space:]')

# Load .env file if it exists (for local development)
if [[ -f "$PROJECT_ROOT/.env" ]]; then
  set +u
  # shellcheck disable=SC1090
  source "$PROJECT_ROOT/.env"
  set -u
fi

# Token is read from SONAR_TOKEN environment variable (not stored in file for security)

REPORT_DIR="$SCRIPT_DIR/reports"
OUTPUT_FILE="$REPORT_DIR/sonar-report.json"
DOWNLOAD=true

# Parse arguments
for arg in "$@"; do
  case "$arg" in
    --download) DOWNLOAD=true ;;
    --no-download) DOWNLOAD=false ;;
    --output=*) OUTPUT_FILE="${arg#--output=}" ;;
    *) echo "Unknown option: $arg"; exit 1 ;;
  esac
done

mkdir -p "$REPORT_DIR"

# Verify SONAR_TOKEN environment variable is set
if [[ -z "${SONAR_TOKEN:-}" ]]; then
  echo "Error: SONAR_TOKEN environment variable not set"
  exit 1
fi

# Run unit tests and collect coverage
echo "Running PHP unit tests with coverage..."
PEST_EXIT=0
php "$PROJECT_ROOT/vendor/bin/pest" \
  --coverage-clover "$REPORT_DIR/coverage.xml" \
  || PEST_EXIT=$?

echo "Running JS unit tests with coverage..."
JS_EXIT=0
npm --prefix "$PROJECT_ROOT" run test:js:coverage \
  || JS_EXIT=$?

# Don't scan against code with known-failing tests.
if [[ "$PEST_EXIT" -ne 0 || "$JS_EXIT" -ne 0 ]]; then
  echo ""
  echo "Error: tests failed (PHP exit $PEST_EXIT, JS exit $JS_EXIT) — skipping SonarCloud scan."
  exit 1
fi

# Run analysis
echo "Running SonarCloud analysis..."
SCANNER_EXIT=0
sonar-scanner \
  -Dproject.settings="$SCRIPT_DIR/sonar-project.properties" \
  -Dsonar.token="${SONAR_TOKEN}" \
  || SCANNER_EXIT=$?

# Download report if requested (always runs, even when quality gate fails)
if [[ "$DOWNLOAD" = true ]]; then
  echo ""
  echo "Downloading report to $OUTPUT_FILE..."
  curl -s -u "${SONAR_TOKEN}:" \
    "$SONAR_HOST/api/issues/search?componentKeys=${PROJECT_KEY}&resolved=false&ps=500&organization=${ORGANIZATION}" \
    -o "$OUTPUT_FILE"
  echo "Report saved to $OUTPUT_FILE"

  # Convert JSON report to Markdown
  MD_FILE="${OUTPUT_FILE%.json}.md"
  echo "Converting report to $MD_FILE..."

  TOTAL=$(jq '.total // 0' "$OUTPUT_FILE")
  DATE=$(date '+%Y-%m-%d %H:%M')

  {
    echo "# SonarCloud Report — $PROJECT_KEY"
    echo "_Generated: $DATE — $TOTAL open issue(s)_"
    echo ""

    if [[ "$TOTAL" -eq 0 ]]; then
      echo "No open issues found."
    else
      jq -r '
        .issues[] |
        "### \(.severity) — \(.type)\n" +
        "- **File:** \(.component | sub("^[^:]+:"; ""))\n" +
        "- **Line:** \(.textRange.startLine // "N/A")\n" +
        "- **Rule:** \(.rule)\n" +
        "- **Message:** \(.message)\n" +
        "- **Effort:** \(.effort // "N/A")\n"
      ' "$OUTPUT_FILE"
    fi
  } > "$MD_FILE"

  echo "Markdown saved to $MD_FILE"

  if [[ "$TOTAL" -eq 0 ]]; then
    echo "SonarQube found no open issues."
  else
    echo "SonarQube found $TOTAL open issue(s) — see $MD_FILE."
  fi
fi

echo ""
echo "Done. View results at $SONAR_HOST/dashboard?id=${PROJECT_KEY}"

exit "${SCANNER_EXIT:-0}"
