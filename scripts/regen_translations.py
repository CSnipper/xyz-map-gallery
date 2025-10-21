#!/usr/bin/env python3
"""
Scan PHP files for i18n strings for domain 'xyz-map-gallery', ensure pl_PL.po contains them, compile .mo.
Usage: python regen_translations.py
"""
import re, os, sys, subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LANG_DIR = ROOT / 'lang'
PO_PL = LANG_DIR / 'xyz-map-gallery-pl_PL.po'
PO_EN = LANG_DIR / 'xyz-map-gallery-en_US.po'
POT = LANG_DIR / 'xyz-map-gallery.pot'

patterns = [
    re.compile(r"__\(\s*(['\"])(.*?)\1\s*,\s*(['\"])xyz-map-gallery\3\s*\)") ,
    re.compile(r"_e\(\s*(['\"])(.*?)\1\s*,\s*(['\"])xyz-map-gallery\3\s*\)") ,
    re.compile(r"_x\(\s*(['\"])(.*?)\1\s*,\s*(['\"])(.*?)\3\s*,\s*(['\"])xyz-map-gallery\5\s*\)") ,
    re.compile(r"_n\(\s*(['\"])(.*?)\1\s*,\s*(['\"])(.*?)\3\s*,\s*[^,]+,\s*(['\"])xyz-map-gallery\5\s*\)")
]

php_files = list(ROOT.rglob('*.php'))
msgids = []
for p in php_files:
    text = p.read_text(encoding='utf-8', errors='ignore')
    for pat in patterns:
        for m in pat.finditer(text):
            # for _x and _n patterns, the message may be group 2 or so; handle generically
            groups = [g for g in m.groups() if g is not None]
            # candidate message is the first non-empty group that is not the context for _x
            # For our regex, usually groups[1] or groups[0]
            # Simpler: pick the longest group
            if groups:
                cand = max(groups, key=len)
                if cand and cand not in msgids:
                    msgids.append(cand)

# also scan js files for i18n? skip for now

# ensure POT contains these msgids in stable order
pot_lines = ['"Project-Id-Version: XYZ Map Gallery\\n"\n']
for m in msgids:
    pot_lines.append('\nmsgid "{}"\nmsgstr ""\n'.format(m.replace('"','\\"')))
POT.write_text('\n'.join(pot_lines), encoding='utf-8')
print('Wrote POT with {} entries to {}'.format(len(msgids), POT))

# load existing po
if not PO_PL.exists():
    print('pl.po not found at', PO_PL)
    sys.exit(1)

po_text = PO_PL.read_text(encoding='utf-8')

# simple parser: collect existing msgid blocks
existing = {}
cur_id = None
cur_str = None
lines = po_text.splitlines()
i = 0
while i < len(lines):
    line = lines[i].strip()
    if line.startswith('msgid '):
        cur_id = eval(line[6:].strip())
        # collect continuations
        i += 1
        while i < len(lines) and lines[i].strip().startswith('"'):
            cur_id += eval(lines[i].strip())
            i += 1
        continue
    if line.startswith('msgstr '):
        cur_str = eval(line[7:].strip())
        i += 1
        while i < len(lines) and lines[i].strip().startswith('"'):
            cur_str += eval(lines[i].strip())
            i += 1
        if cur_id is None:
            cur_id = ''
        existing[cur_id] = cur_str
        cur_id = None
        cur_str = None
        continue
    i += 1

# add missing entries to po text (append at end)
added = 0
with PO_PL.open('a', encoding='utf-8') as f:
    for m in msgids:
        if m not in existing:
            # add msgid/msgstr with msgstr = msgid (placeholder)
            f.write('\nmsgid "{}"\nmsgstr "{}"\n'.format(m.replace('"','\\"'), m.replace('"','\\"')))
            added += 1

print('Added {} missing entries to {}'.format(added, PO_PL))

# compile .mo using existing script in photos-to-posts repo if available
compile_script = Path('H:/www/git/photos-to-posts/scripts/compile_po.py')
if compile_script.exists():
    cmd = ['python', str(compile_script), str(PO_PL), str(PO_PL.with_suffix('.mo'))]
    print('Compiling .mo...')
    subprocess.check_call(cmd)
    print('Compiled', PO_PL.with_suffix('.mo'))
else:
    print('Compile script not found at', compile_script)

# copy to public_html and wp-content/languages/plugins
public_lang = Path('H:/www/zatopionewspomnienia.pl/public_html/wp-content/plugins/xyz-map-gallery/lang')
global_plugins = Path('H:/www/zatopionewspomnienia.pl/public_html/wp-content/languages/plugins')
for src in [PO_PL.with_suffix('.mo')]:
    if src.exists():
        dst1 = public_lang / src.name
        dst2 = global_plugins / src.name
        dst1.parent.mkdir(parents=True, exist_ok=True)
        global_plugins.mkdir(parents=True, exist_ok=True)
        import shutil
        shutil.copy2(src, dst1)
        shutil.copy2(src, dst2)
        print('Copied', src, 'to', dst1, 'and', dst2)

# git commit changes in repo
os.chdir(str(ROOT))
subprocess.call(['git', 'add', 'lang/*.po', 'lang/*.mo', 'lang/*.pot'])
# commit if changed
st = subprocess.check_output(['git','status','--porcelain']).decode('utf-8')
if st.strip():
    subprocess.call(['git','commit','-m','i18n: regen pot and update pl_PL.po with placeholders'])
    subprocess.call(['git','push','origin','HEAD'])
    print('Committed and pushed changes')
else:
    print('No git changes to commit')

print('Done')
