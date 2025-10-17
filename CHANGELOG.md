# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
- Initial admin improvements: center-picker, responsive admin CSS, moved inline admin JS to enqueued assets.

## [0.1.0] - 2025-10-17
### Added
- Persistent center picker in Map settings (admin) with `center_lat` and `center_lng` stored in DB table `wp_xyz_maps`.
- Admin mini-map moved to right-side aside with responsive fallback.
- `assets/js/admin-center.js` centralized initialization for the center picker.
- `assets/js/admin-bulk.js` provides Bulk assign UI helper (map selector + validation).
- `assets/css/admin-center.css` responsive admin styling.
- Updated `includes/admin/assets.php` to enqueue and localize admin scripts and styles.
- Removed inline admin JS from templates to improve reliability.

### Changed
- `includes/admin/map-settings.php` layout adjusted; removed inline JS.
- `includes/admin/bulk-assign.php` inline script removed (behavior moved to `admin-bulk.js`).

### Notes
- This is the initial release; no automatic DB migration is provided. The installation SQL already creates necessary columns.
