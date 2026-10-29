#!/usr/bin/env bash
# Baut Plugin-ZIP (mit Template-Paket + Prüfsummen) und Template-ZIP aus einem festen Git-Stand.
# Aufruf: tools/build-plugin-zip.sh <Tag-oder-Commit> <Version> <Ausgabeordner>
set -euo pipefail
REF="${1:?Tag oder Commit}"; VER="${2:?Version x.y.z}"; OUT="${3:?Ausgabeordner}"
[[ "$VER" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Version muss x.y.z sein" >&2; exit 1; }
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
mkdir -p "$OUT" "$TMP/pl" "$TMP/tp"
OUT="$(cd "$OUT" && pwd)"
cd "$ROOT"
# Plugin
mkdir -p "$TMP/pl/MichaThemeV5"
git archive "$REF:plugins/MichaThemeV5" | tar -x -C "$TMP/pl/MichaThemeV5"
# Template-Paket im Plugin
mkdir -p "$TMP/pl/MichaThemeV5/template-package/MichaThemeV5"
git archive "$REF:templates/MichaThemeV5" | tar -x -C "$TMP/pl/MichaThemeV5/template-package/MichaThemeV5"
php "$ROOT/tools/make-integrity.php" "$TMP/pl/MichaThemeV5/template-package/MichaThemeV5" "$VER"
PZ="$OUT/MichaThemeV5-plugin-$VER.zip"; rm -f "$PZ"
(cd "$TMP/pl" && zip -q -r -X "$PZ" MichaThemeV5)
# Template-ZIP (templates + plugins wie bisher)
mkdir -p "$TMP/tp/pkg"
git archive "$REF" templates plugins | tar -x -C "$TMP/tp/pkg"
TZ="$OUT/michatheme-v5-jtl5-$VER.zip"; rm -f "$TZ"
(cd "$TMP/tp/pkg" && zip -q -r -X "$TZ" templates plugins)
for z in "$PZ" "$TZ"; do (cd "$OUT" && shasum -a 256 "$(basename "$z")" > "$(basename "$z").sha256"); done
ls -la "$PZ" "$TZ"; cat "$PZ.sha256" "$TZ.sha256"
