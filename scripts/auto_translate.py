#!/usr/bin/env python3
"""
Auto-translate: read POT, build pl_PL.po with translations using a small dictionary and heuristics,
then compile .mo and deploy to plugin and global folder. Commit changes.
"""
import re, os, subprocess
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
LANG = ROOT / 'lang'
POT = LANG / 'xyz-map-gallery.pot'
PO_PL = LANG / 'xyz-map-gallery-pl_PL.po'

# simple mapping dictionary
MAPPING = {
    'Rate limited': 'Ograniczono dostęp',
    'Map not found.': 'Nie znaleziono mapy.',
    'No photos yet.': 'Brak zdjęć.',
    'XYZ Big Map': 'XYZ — Duża mapa',
    'Content': 'Treść',
    'Map': 'Mapa',
    'Start typing to filter the list.': 'Zacznij wpisywać, aby filtrować listę.',
    'No maps found. Create one first.': 'Nie znaleziono map. Najpierw utwórz mapę.',
    'Pick a map in the sidebar.': 'Wybierz mapę z paska bocznego.',
    'XYZ Map Gallery': 'XYZ Galeria Map',
    'Type map title to search…': 'Wpisz tytuł mapy, aby wyszukać…',
    'XYZ Mini Map': 'XYZ Mini Mapa',
    'Place': 'Miejsce',
    'Type place title to search…': 'Wpisz tytuł miejsca, aby wyszukać…',
    'XYZ Photos Grid': 'XYZ Siatka zdjęć',
    'Per page': 'Na stronę',
    'Back to map': 'Powrót do mapy',
    'Unsupported file': 'Nieobsługiwany plik',
    'Upload file': 'Prześlij plik',
    'Assign to map': 'Przypisz do mapy',
    '— none —': '— brak —',
    'Column mapping': 'Mapowanie kolumn',
    'Title': 'Tytuł',
    'Description': 'Opis',
    'Owner': 'Właściciel',
    'Tags': 'Tagi',
    'Icon': 'Ikona',
    'External ID': 'Identyfikator zewnętrzny',
    'Latitude': 'Szerokość geograficzna',
    'Longitude': 'Długość geograficzna',
    'Options': 'Opcje',
    'Dry-run': 'Tryb testowy (bez zmian)',
    'Overwrite existing': 'Nadpisz istniejące',
    'Run import': 'Uruchom import',
}

# heuristics: basic replacements in strings
REPLACEMENTS = [
    ('Photos', 'Zdjęcia'),
    ('Photo', 'Zdjęcie'),
    ('Gallery', 'Galeria'),
    ('Maps', 'Mapy'),
    ('Map', 'Mapa'),
    ('map', 'mapa'),
    ('Title', 'Tytuł'),
    ('Description', 'Opis'),
    ('Owner', 'Właściciel'),
    ('Tags', 'Tagi'),
    ('Unsupported', 'Nieobsługiwany'),
    ('Upload', 'Prześlij'),
    ('Assign', 'Przypisz'),
    ('Options', 'Opcje'),
]

if not POT.exists():
    print('POT not found:', POT)
    raise SystemExit(1)

content = POT.read_text(encoding='utf-8')
# extract msgid blocks
msgids = []
for m in re.finditer(r"msgid\s+\"(.*?)\"", content, re.S):
    s = m.group(1)
    if s.strip()=='' or s.startswith('Project-Id-Version'):
        continue
    msgids.append(s)

# create po header
header = (
    'msgid ""\n'
    'msgstr ""\n'
    '"Project-Id-Version: XYZ Map Gallery\\n"\n'
    '"POT-Creation-Date: 2025-10-01 12:00+0000\\n"\n'
    '"PO-Revision-Date: 2025-10-17 00:00+0000\\n"\n'
    '"Last-Translator: Auto Translator <auto@example.com>\\n"\n'
    '"Language-Team: Polish\\n"\n'
    '"Language: pl_PL\\n"\n'
    '"MIME-Version: 1.0\\n"\n'
    '"Content-Type: text/plain; charset=UTF-8\\n"\n'
    '"Content-Transfer-Encoding: 8bit\\n"\n'
    '"X-Generator: auto_translate.py\\n"\n'
    '"X-Domain: xyz-map-gallery"\n\n'
)

po_lines = [header]

for id in msgids:
    # ensure single-line msgid representation escapes
    escaped = id.replace('"','\\"')
    # decide translation
    if id in MAPPING:
        tr = MAPPING[id]
    else:
        tr = id
        # apply replacements
        for a,b in REPLACEMENTS:
            tr = tr.replace(a,b)
        # capitalize first letter if looks like sentence
        if tr and tr[0].islower():
            tr = tr[0].upper() + tr[1:]
    tr_escaped = tr.replace('"','\\"')
    po_lines.append('msgid "{}"\nmsgstr "{}"\n'.format(escaped, tr_escaped))

new_po = '\n'.join(po_lines)
PO_PL.write_text(new_po, encoding='utf-8')
print('Wrote', PO_PL)

# compile using photos-to-posts compile script
compile_script = Path('H:/www/git/photos-to-posts/scripts/compile_po.py')
if compile_script.exists():
    subprocess.check_call(['python', str(compile_script), str(PO_PL), str(PO_PL.with_suffix('.mo'))])
    print('Compiled .mo')
    # copy to public_html and global
    dst_plugin = Path('H:/www/zatopionewspomnienia.pl/public_html/wp-content/plugins/xyz-map-gallery/lang')
    dst_global = Path('H:/www/zatopionewspomnienia.pl/public_html/wp-content/languages/plugins')
    dst_plugin.mkdir(parents=True, exist_ok=True)
    dst_global.mkdir(parents=True, exist_ok=True)
    import shutil
    shutil.copy2(str(PO_PL.with_suffix('.mo')), str(dst_plugin / PO_PL.with_suffix('.mo').name))
    shutil.copy2(str(PO_PL.with_suffix('.mo')), str(dst_global / PO_PL.with_suffix('.mo').name))
    print('Copied .mo to plugin and global languages')
else:
    print('Compile script not found:', compile_script)

# git commit
os.chdir(str(ROOT))
subprocess.call(['git','add', 'lang/xyz-map-gallery-pl_PL.po', 'lang/xyz-map-gallery-pl_PL.mo', 'lang/xyz-map-gallery.pot'])
st = subprocess.check_output(['git','status','--porcelain']).decode('utf-8')
if st.strip():
    subprocess.call(['git','commit','-m','i18n: full auto-translation for pl_PL and regen .mo'])
    subprocess.call(['git','push','origin','HEAD'])
    print('Committed and pushed')
else:
    print('No changes to commit')

print('Done')
