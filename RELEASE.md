Release preparation

PR title
-------
feat(admin): add persistent center picker, responsive admin UI and move inline admin JS to enqueued assets

PR body
-------
This PR implements initial admin-side improvements for the XYZ Map Gallery plugin:

- Add persistent center picker in Map settings (admin). The chosen center is stored in the `wp_xyz_maps` table as `center_lat` / `center_lng` and included in the map payload returned to the frontend map renderer.
- Move inline admin JavaScript into enqueued assets to avoid race conditions and improve maintainability:
  - `assets/js/admin-center.js` (init for center picker)
  - `assets/js/admin-bulk.js` (bulk-assign helper)
- Add responsive admin CSS: `assets/css/admin-center.css`.
- Update `includes/admin/assets.php` to enqueue and localize admin scripts/styles.
- Remove inline JS from `includes/admin/map-settings.php` and `includes/admin/bulk-assign.php`.

Files changed (high-level)
- includes/admin/map-settings.php
- includes/admin/assets.php
- includes/admin/bulk-assign.php
- assets/js/admin-center.js
- assets/js/admin-bulk.js
- assets/css/admin-center.css
- README.md / CHANGELOG.md updated

Testing checklist
- [ ] Admin → Maps → Edit map: pick center on mini-map, save, reload — coordinates persist.
- [ ] Frontend map initializes with server-provided center, if present.
- [ ] Admin → Markers list: Bulk assign select is visible and validation prevents applying without choosing a map.
- [ ] No JS errors in console on the above pages.

Git commands (suggested)

```bash
# create a branch
git checkout -b feat/admin-center-picker
# add changes
git add .
git commit -m "feat(admin): add persistent center picker, responsive admin UI and move inline admin JS"
# push branch
git push origin feat/admin-center-picker
# create PR on GitHub and merge when approved
# then tag a release
git tag -a v0.1.0 -m "Initial release: admin center picker and admin refactor"
git push origin v0.1.0
```

Notes
- This is the initial release; no DB migrations (ALTER TABLE) are required for upgrades because this release creates the table schema including center columns on install.
- If you want, I can also create a ready-to-paste GitHub Release body from the CHANGELOG.
