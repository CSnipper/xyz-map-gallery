# XYZ Map Gallery

XYZ Map Gallery is a WordPress plugin for creating interactive maps with custom markers and photo galleries. It is designed for websites that need to present locations, places, or objects visually, with rich metadata and photo support. Supports XYZ tile maps compatibile with Leaflet.js.

## Features
- Create unlimited custom maps
- Add markers (map_marker) with metadata and icons
- Assign photos (map_photo) to markers by few options (title, caption, taxonomies)
- Display maps and galleries using Gutenberg blocks, Elementor, WPBakery, or shortcodes
- REST API for advanced integrations
- Ready for translation (i18n)
- Optional WooCommerce integration for selling photos (future feature)

## Use Cases
- Tourist guides and city maps
- Museums and exhibitions (interactive exhibits)
- Real estate listings with location photos
- Nature reserves, parks, and trails
- Event maps (festivals, marathons, etc.)
- Educational projects (historical maps, biology, geography)
- Any website needing interactive, photo-rich maps

## How It Works
1. Create a map and define its bounds and tiles
2. Add markers for places, objects, or points of interest
3. Assign photos to markers or places
4. Display maps and galleries anywhere on your site
5. (Future) sell photos via WooCommerce

## Requirements
- WordPress 5.6+
- PHP 7.4+
- (Optional) WooCommerce for e-commerce features

## Getting Started
- Install and activate the plugin
- Go to the XYZ Map Gallery menu in WP admin
- Create your first map and add markers
- Assign photos and configure display options
- Use blocks, shortcodes, or page builders to show maps and galleries

## License
GPL v2 or later

---
For more information, see the documentation or contact the author.


## Recent admin improvements (initial release)

This initial release includes a set of admin-side improvements focused on reliability and maintainability:

- Persistent center picker in Map settings (admin): pick a center point on a mini-map; saved coordinates are included in the map payload used by frontend maps.
- All admin JavaScript previously embedded inline has been moved to enqueued assets for predictable load order and to avoid race conditions with Leaflet being loaded in the footer.
- Responsive admin CSS so the center picker appears in a right-hand aside on desktop and stacks above the form on narrow screens.
- Bulk assign UI helper (map selector next to Bulk actions) is now provided by `assets/js/admin-bulk.js` and localized via `includes/admin/assets.php`.

Files changed/added (high level):
- `includes/admin/map-settings.php` — removed inline initialization and added layout classes.
- `includes/admin/assets.php` — enqueued CSS/JS and localized small data objects for admin scripts.
- `assets/css/admin-center.css` — responsive styling for the admin UI.
- `assets/js/admin-center.js` — central admin init for the center picker (already present).
- `assets/js/admin-bulk.js` — new file for Bulk-assign UI behaviour.

QA quick checks:
- Admin → Maps → Edit map: mini-map appears, click to set marker, Save Map. Values persist.
- Admin → Markers list: select multiple items, choose Bulk action "Assign to map…" — selecting no map triggers an alert; selecting a map applies the action.

Release notes:
- This is the plugin's first release. No automatic DB migrations are included; the initial install contains the necessary schema.

