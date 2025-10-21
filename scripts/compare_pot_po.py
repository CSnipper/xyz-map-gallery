#!/usr/bin/env python3
import re, sys

def extract_msgids(path):
    ids = []
    with open(path, 'r', encoding='utf-8') as f:
        text = f.read()
    # find msgid "..." blocks
    for m in re.finditer(r"msgid\s+\"(.*?)\"", text, re.S):
        s = m.group(1)
        ids.append(s)
    return ids

if __name__ == '__main__':
    pot = r'H:\\www\\zatopionewspomnienia.pl\\public_html\\wp-content\\plugins\\xyz-map-gallery\\lang\\xyz-map-gallery.pot'
    po = r'H:\\www\\zatopionewspomnienia.pl\\public_html\\wp-content\\plugins\\xyz-map-gallery\\lang\\xyz-map-gallery-pl_PL.po'
    pot_ids = extract_msgids(pot)
    po_ids = extract_msgids(po)
    pot_set = set(pot_ids)
    po_set = set(po_ids)
    missing = [s for s in pot_ids if s not in po_set]
    print('Total msgids in POT:', len(pot_ids))
    print('Total msgids in pl.po:', len(po_ids))
    print('\nMissing in pl_PL.po:')
    for s in missing:
        if s.strip()=='' or s.startswith('Project-Id-Version'):
            continue
        print('-', s)
    if not missing:
        print('None')
