#!/usr/bin/env python3
"""Prüft beide fertigen JTL-Pakete gegen denselben festen Git-Stand."""
import hashlib
import io
import json
from pathlib import Path, PurePosixPath
import re
import stat
import subprocess
import sys
import tarfile
import xml.etree.ElementTree as ET
import zipfile


def require(condition, message):
    if not condition:
        raise SystemExit(message)


def read_zip(path):
    files = {}
    with zipfile.ZipFile(path) as archive:
        entries = archive.infolist()
        require(len(entries) == len({item.filename for item in entries}), 'Doppelte ZIP-Pfade')
        require(sum(item.file_size for item in entries) < 64 * 1024 * 1024, 'Paket unerwartet groß')
        for item in entries:
            name = item.filename
            require(not name.startswith('/') and '\\' not in name and '..' not in PurePosixPath(name).parts, 'Unsicherer ZIP-Pfad')
            mode = stat.S_IFMT(item.external_attr >> 16)
            require(mode in (stat.S_IFREG, stat.S_IFDIR), 'Unerwarteter Dateityp im ZIP')
            if not item.is_dir():
                files[name] = archive.read(item)
        require(archive.testzip() is None, 'ZIP-CRC fehlerhaft')
    digest = hashlib.sha256(path.read_bytes()).hexdigest()
    require(path.with_suffix('.zip.sha256').read_text().strip() == digest + '  ' + path.name, 'Paket-Prüfsumme falsch')
    return files, digest


def main():
    require(len(sys.argv) == 4, 'Aufruf: check-release.py Ausgabeordner Version Git-Stand')
    output, version, ref = Path(sys.argv[1]), sys.argv[2], sys.argv[3]
    require(re.fullmatch(r'\d+\.\d+\.\d+', version), 'Ungültige Version')
    root = Path(__file__).resolve().parents[1]
    paths = ['templates/MichaThemeV5', 'plugins/MichaThemeV5', 'plugins/MichaThemeV5NotificationCenter']
    source = {}
    data = subprocess.check_output(['git', 'archive', ref, *paths], cwd=root)
    with tarfile.open(fileobj=io.BytesIO(data)) as archive:
        for entry in archive:
            require(entry.isdir() or entry.isfile(), 'Dateiverweis im Git-Paket')
            if entry.isfile():
                source[entry.name] = archive.extractfile(entry).read()
    theme = {name.removeprefix(paths[0] + '/'): content for name, content in source.items() if name.startswith(paths[0] + '/')}
    plugin = {name.removeprefix('plugins/'): content for name, content in source.items() if name.startswith(paths[1] + '/')}
    bundle = 'MichaThemeV5/template-package/MichaThemeV5/'
    for name, content in theme.items():
        plugin[bundle + name] = content
    plugin_path = output / ('MichaThemeV5-plugin-' + version + '.zip')
    full_path = output / ('michatheme-v5-jtl5-' + version + '.zip')
    packed, plugin_sha = read_zip(plugin_path)
    full, full_sha = read_zip(full_path)
    manifest_name = bundle + 'build-integrity.json'
    require(manifest_name in packed, 'Template-Prüfliste fehlt')
    manifest = json.loads(packed[manifest_name])
    require(manifest == {'version': version, 'files': {name: hashlib.sha256(content).hexdigest() for name, content in theme.items()}}, 'Template-Prüfliste passt nicht zum Git-Stand')
    plugin[manifest_name] = packed[manifest_name]
    require(packed == plugin, 'Begleitplugin weicht vom freigegebenen Git-Stand ab')
    expected_full = {name: content for name, content in source.items() if not name.startswith(paths[1] + '/')}
    expected_full.update({'plugins/' + name: content for name, content in plugin.items()})
    require(full == expected_full, 'Gesamtpaket enthält fehlende, fremde oder veränderte Dateien')
    require(ET.fromstring(theme['template.xml']).findtext('Version') == version, 'Template-Version falsch')
    require(ET.fromstring(packed['MichaThemeV5/info.xml']).findtext('Version') == version, 'Plugin-Version falsch')
    require(json.loads(packed['MichaThemeV5/src/Generated/mt-admin-schema.json'])['version'] == version, 'Schema-Version falsch')
    require(("'version' => '" + version + "'").encode() in packed['MichaThemeV5/src/Generated/version.php'], 'PHP-Version falsch')
    # Nur eindeutige Secret-Muster prüfen. Konfigurationsbegriffe sind keine Zugangsdaten.
    secret = re.compile(rb'github_pat_[A-Za-z0-9_]{20,}|ghp_[A-Za-z0-9]{30,}|-----BEGIN (?:RSA |OPENSSH |EC )?PRIVATE KEY-----|AKIA[0-9A-Z]{16}')
    for name, content in full.items():
        require(not secret.search(content), 'Mögliches Secret im Paket: ' + name)
        require(not any(part in ('.git', '.env', 'node_modules', '__pycache__', '.DS_Store') for part in PurePosixPath(name).parts), 'Entwicklungsdatei im Paket')
    print(json.dumps({'version': version, 'template_files': len(theme), 'plugin_files': len(packed), 'full_files': len(full), 'plugin_sha256': plugin_sha, 'full_sha256': full_sha, 'git_match': True, 'safe_paths': True, 'secret_scan': 'passed'}, ensure_ascii=False))


if __name__ == '__main__':
    main()
