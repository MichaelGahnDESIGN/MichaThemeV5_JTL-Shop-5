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
PZ="$OUT/MichaThemeV5-plugin-$VER.zip"
# Gesamtpaket: nur die bisherigen Theme-Komponenten, kein eigenständiges Suchplugin.
mkdir -p "$TMP/tp/pkg"
git archive "$REF" templates/MichaThemeV5 plugins/MichaThemeV5NotificationCenter | tar -x -C "$TMP/tp/pkg"
mkdir -p "$TMP/tp/pkg/plugins"
cp -R "$TMP/pl/MichaThemeV5" "$TMP/tp/pkg/plugins/MichaThemeV5"
TZ="$OUT/michatheme-v5-jtl5-$VER.zip"
EPOCH="$(git show -s --format=%ct "$REF")"
# Feste Zeiten und sortierte Pfade machen Wiederholungen desselben Commits bytegleich.
python3 - "$TMP/pl" "$PZ" "$TMP/tp/pkg" "$TZ" "$EPOCH" <<'PY'
from pathlib import Path
from datetime import datetime, timezone
import hashlib, stat, sys, zipfile
stamp = datetime.fromtimestamp(int(sys.argv[5]), timezone.utc).timetuple()[:6]
for source, target in [(sys.argv[1], sys.argv[2]), (sys.argv[3], sys.argv[4])]:
    root = Path(source)
    with zipfile.ZipFile(target, 'w') as archive:
        for path in sorted(root.rglob('*')):
            if path.is_symlink():
                raise SystemExit('Dateiverweis im Paket')
            directory = path.is_dir()
            name = path.relative_to(root).as_posix() + ('/' if directory else '')
            entry = zipfile.ZipInfo(name, stamp)
            entry.create_system = 3
            mode = (stat.S_IFDIR | 0o755) if directory else (stat.S_IFREG | (0o755 if path.stat().st_mode & 0o111 else 0o644))
            entry.external_attr = mode << 16
            entry.compress_type = zipfile.ZIP_STORED if directory else zipfile.ZIP_DEFLATED
            archive.writestr(entry, b'' if directory else path.read_bytes(), compresslevel=9)
    package = Path(target)
    package.with_suffix(package.suffix + '.sha256').write_text(hashlib.sha256(package.read_bytes()).hexdigest() + '  ' + package.name + '\n')
PY
python3 "$ROOT/tools/check-release.py" "$OUT" "$VER" "$REF"
ls -la "$PZ" "$TZ"; cat "$PZ.sha256" "$TZ.sha256"
