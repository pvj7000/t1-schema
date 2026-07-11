#!/usr/bin/env bash
#
# Build a WordPress-ready distribution zip for t1 Schema.
# Exclusions are read from .distignore at the repo root.
#
# Usage (from repo root):
#   ./bin/build-zip.sh
#
# Output:
#   dist/t1-schema-{version}.zip

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="t1-schema"
DIST_DIR="${ROOT}/dist"
STAGING="$(mktemp -d)"
EXCLUDES="$(mktemp)"

cleanup() {
	rm -rf "${STAGING}" "${EXCLUDES}"
}
trap cleanup EXIT

cd "${ROOT}"

VERSION="$(grep -m1 'Version:' t1-schema.php | awk '{print $3}')"
if [[ -z "${VERSION}" ]]; then
	echo "error: could not read plugin version from t1-schema.php" >&2
	exit 1
fi

echo "→ Building admin assets (v${VERSION})…"
cd "${ROOT}/admin"
if [[ -f package-lock.json ]]; then
	npm ci
else
	npm install
fi
npm run build

echo "→ Staging plugin files…"
cd "${ROOT}"
grep -v '^[[:space:]]*#' .distignore | grep -v '^[[:space:]]*$' > "${EXCLUDES}"
rsync -a --exclude-from="${EXCLUDES}" ./ "${STAGING}/${SLUG}/"

mkdir -p "${DIST_DIR}"
ZIP_PATH="${DIST_DIR}/${SLUG}-${VERSION}.zip"
rm -f "${ZIP_PATH}"

cd "${STAGING}"
zip -rq "${ZIP_PATH}" "${SLUG}"

echo "✓ Created ${ZIP_PATH} ($(du -h "${ZIP_PATH}" | awk '{print $1}'))"
