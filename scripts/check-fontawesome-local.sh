#!/usr/bin/env bash
# Verify locally installed Font Awesome assets and their HTTP availability.
# Usage: bash scripts/check-fontawesome-local.sh [http://127.0.0.1:8003]
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
base="$root/assets/vendor/fontawesome"
origin="${1:-http://127.0.0.1:8003}"
origin="${origin%/}"

missing=0
for rel in css/all.min.css webfonts/fa-solid-900.woff2 webfonts/fa-brands-400.woff2 LICENSE.txt; do
  if [[ ! -s "$base/$rel" ]]; then
    echo "MISSING: assets/vendor/fontawesome/$rel" >&2
    missing=1
  fi
done
if (( missing )); then
  echo 'Font Awesome assets have not been installed (or installation is incomplete).' >&2
  echo 'Run: bash scripts/install-fontawesome-free.sh' >&2
  exit 1
fi

echo 'PASS: Font Awesome CSS, webfonts and license exist locally.'
if ! command -v curl >/dev/null 2>&1; then
  echo 'curl is unavailable. Use Chrome Network to verify HTTP 200 for CSS and woff2.' >&2
  exit 0
fi

for rel in css/all.min.css webfonts/fa-solid-900.woff2 webfonts/fa-brands-400.woff2; do
  url="$origin/assets/vendor/fontawesome/$rel"
  status="$(curl --max-time 7 -L -s -o /dev/null -w '%{http_code}' "$url" || true)"
  if [[ "$status" != 200 ]]; then
    echo "FAIL: HTTP ${status:-000} $url" >&2
    echo 'Start PHP server, check local asset path and try again.' >&2
    exit 1
  fi
  echo "PASS: HTTP 200 $url"
done

# Protect against router fallback returning an HTML page with HTTP 200 for a missing asset.
css_content_type="$(curl --max-time 7 -Ls -o /dev/null -w '%{content_type}' "$origin/assets/vendor/fontawesome/css/all.min.css" || true)"
if [[ "$css_content_type" == *text/html* ]]; then
  echo "FAIL: CSS URL returns HTML ($css_content_type), not a stylesheet." >&2
  exit 1
fi
echo 'PASS: Font Awesome assets can be served by CouponHub.'
