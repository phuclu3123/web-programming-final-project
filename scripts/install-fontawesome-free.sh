#!/usr/bin/env bash
# Vendor pinned Font Awesome Free, including its official LICENSE, from npm.
# Run from any directory in Git Bash: bash scripts/install-fontawesome-free.sh
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

if ! command -v npm >/dev/null 2>&1; then
  echo "npm not found. Install Node.js LTS first." >&2
  exit 1
fi

version=6.7.2
archive="$(npm pack "@fortawesome/fontawesome-free@${version}" --silent --pack-destination "$tmp")"
if [[ ! -f "$tmp/$archive" ]]; then
  echo "Could not download Font Awesome Free ${version} from npm." >&2
  exit 1
fi

tar -xzf "$tmp/$archive" -C "$tmp" package/css/all.min.css package/webfonts package/LICENSE.txt
base="$root/assets/vendor/fontawesome"
mkdir -p "$base/css" "$base/webfonts"
cp "$tmp/package/css/all.min.css" "$base/css/all.min.css"
cp "$tmp/package/LICENSE.txt" "$base/LICENSE.txt"
cp "$tmp"/package/webfonts/* "$base/webfonts/"

for required in "$base/css/all.min.css" "$base/webfonts/fa-solid-900.woff2" "$base/webfonts/fa-brands-400.woff2" "$base/LICENSE.txt"; do
  if [[ ! -s "$required" ]]; then
    echo "Missing vendored asset: $required" >&2
    exit 1
  fi
done

echo "Font Awesome Free ${version} installed locally in assets/vendor/fontawesome/"
echo "Commit CSS, webfonts and LICENSE.txt alongside PHP view changes."
