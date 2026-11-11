#!/usr/bin/env bash
# Release-ZIP MGD Notification Center aus festem Git-Stand
# Aufruf: tools/build-notification-zip.sh <Tag-oder-Commit> <Ausgabeordner>
set -euo pipefail
REF="${1:?Tag oder Commit}"; OUT="${2:?Ausgabeordner}"
PL=MichaThemeV5NotificationCenter
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
mkdir -p "$OUT" "$TMP/pkg/$PL"
OUT="$(cd "$OUT" && pwd)"
cd "$ROOT"
git archive "$REF:plugins/$PL" | tar -x -C "$TMP/pkg/$PL"
# tests gehören nicht ins Paket
rm -rf "$TMP/pkg/$PL/tests"
VER="$(sed -n 's#.*<Version>\([0-9.]*\)</Version>.*#\1#p' "$TMP/pkg/$PL/info.xml" | head -1)"
[[ "$VER" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Version in info.xml fehlt" >&2; exit 1; }
ZIP="$OUT/MGD-Notification-Center-JTL-Shop-Plugin-$VER.zip"
EPOCH="$(git show -s --format=%ct "${REF}^{commit}")"
# Unix-Rechte mit rein (Ordner 755, Dateien 644), sonst kommt beim Entpacken 700 raus
python3 - "$TMP/pkg" "$ZIP" "$EPOCH" <<'PY'
from pathlib import Path
from datetime import datetime, timezone
import hashlib, stat, sys, zipfile
root, target = Path(sys.argv[1]), Path(sys.argv[2])
stamp = datetime.fromtimestamp(int(sys.argv[3]), timezone.utc).timetuple()[:6]
with zipfile.ZipFile(target, 'w') as archive:
    for path in sorted(root.rglob('*')):
        if path.is_symlink():
            raise SystemExit('Dateiverweis im Paket')
        directory = path.is_dir()
        entry = zipfile.ZipInfo(path.relative_to(root).as_posix() + ('/' if directory else ''), stamp)
        entry.create_system = 3
        entry.external_attr = ((stat.S_IFDIR | 0o755) if directory else (stat.S_IFREG | 0o644)) << 16
        entry.compress_type = zipfile.ZIP_STORED if directory else zipfile.ZIP_DEFLATED
        archive.writestr(entry, b'' if directory else path.read_bytes(), compresslevel=9)
target.with_suffix('.zip.sha256').write_text(hashlib.sha256(target.read_bytes()).hexdigest() + '  ' + target.name + '\n')
PY
# Gegenprobe: entpacken und Rechte zählen
CHK="$TMP/chk"; mkdir -p "$CHK"; (cd "$CHK" && unzip -q "$ZIP")
BAD="$(find "$CHK/$PL" \( -type d ! -perm 755 \) -o \( -type f ! -perm 644 \) | wc -l | tr -d ' ')"
[[ "$BAD" == 0 ]] || { echo "Rechte falsch bei $BAD Einträgen" >&2; exit 1; }
[[ ! -e "$CHK/$PL/tests" ]] || { echo "tests im Paket" >&2; exit 1; }
ls -la "$ZIP"; cat "$ZIP.sha256"
