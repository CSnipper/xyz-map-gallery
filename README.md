\# XYZ Map Gallery



Interactive map gallery plugin for WordPress with XY/geocoding support and optional WooCommerce integration. Display custom maps (e.g., aerial photos) or OpenStreetMap, with pins for gallery items or products. Supports sale/non-sale pins, migration to WooCommerce, and customizable popups/mini-maps.



\## Description



XYZ Map Gallery lets you create an interactive map for galleries or e-commerce. Use it for portfolios, historical maps, or product showcases (e.g., aerial photo prints). Features:

\- \*\*Custom Post Type (CPT)\*\*: "Gallery Item" for non-sale pins (title, description, thumbnail).

\- \*\*WooCommerce Integration\*\*: Optional, for sale pins with prices and "Add to Cart" (simple products, configure in WooCommerce).

\- \*\*XY/Geocoding\*\*: Place pins via pixel coordinates (XY) or geocoding (OpenStreetMap Nominatim, no API key).

\- \*\*Popups\*\*: Title, description, thumbnail, link ("Details" for non-sale, "See \& Buy" for sale). Optional price display.

\- \*\*Mini-Map\*\*: On single CPT/product pages, configurable position, clicks redirect to main map.

\- \*\*Icons\*\*: Default set (MapIcons Collection) + upload custom (PNG/SVG, max 50KB).

\- \*\*Migration\*\*: Convert CPT items to WooCommerce products (simple, batch process).

\- \*\*Scalability\*\*: Handles ~500 pins, optimized for thousands with transients.



Ideal for galleries, stores, or mixed use (e.g., aerial photo sites with sale/non-sale points).



\## Installation



1\. Upload `xyz-map-gallery.zip` to `/wp-content/plugins/` or install via WordPress admin.

2\. (Optional) Place `mapiconscollection-markers.zip` (from \[MapIcons](https://mapicons.mapsmarker.com/)) in `/wp-content/plugins/xyz-map-gallery/` before activation for default icons.

3\. Activate the plugin via Plugins menu.

4\. Configure in \*\*XYZ Map Gallery > Settings\*\*:

&nbsp;  - Tiles URL (e.g., `/wp-content/uploads/tiles/{z}/{x}/{y}.png` or OSM).

&nbsp;  - Image size (e.g., 4000x3000px for XY).

&nbsp;  - Zoom levels (min/max, e.g., 0-5).

&nbsp;  - Map mode (XY/Geocoding).

&nbsp;  - WooCommerce integration (optional).

&nbsp;  - Popup fields (title, thumbnail, description, price).

&nbsp;  - Mini-map position (below description, above title, right side).

5\. Add icons in \*\*XYZ Map Gallery > Icons\*\* (upload PNG/SVG, max 50KB).

6\. Create CPT items or WooCommerce products with pins in edit screen (set position via map, choose XY/Geo).

7\. Use shortcode `\[xyz\_map]` on a page (e.g., `/mapa`) to display the map.



\## Usage



\### Gallery Mode

\- Create "Gallery Item" (CPT) via \*\*XYZ Map Gallery > Add New\*\*.

\- Set pin position (click on embedded map, XY or Geocoding via address search).

\- Mark as "Sale Item" (optional, for WooCommerce).

\- Non-sale items show "Details" link in popup to single CPT page.



\### WooCommerce Mode

\- Enable WooCommerce in \*\*Settings\*\*.

\- Pins pull from products with `\_map\_position` meta.

\- Popups show price (if enabled) and "See \& Buy" link.

\- Migrate CPT to WooCommerce in \*\*Migrate\*\* (select sale items, creates simple products for configuration in WooCommerce).

\- On-the-fly: Click "See \& Buy" on non-sale CPT creates simple product, adds to cart.



\### Shortcode

\- `\[xyz\_map source="gallery\_item,product" category="lotnicze" only\_sale="true"]`

&nbsp; - `source`: `gallery\_item`, `product`, or `all`.

&nbsp; - `category`: Filter by category slug.

&nbsp; - `only\_sale`: Show only sale pins.



\### Mini-Map

\- Auto-added to single CPT/product pages (position from settings).

\- Clicks redirect to `/mapa?focus={post\_id}`.



\## Testing



1\. \*\*Setup\*\*:

&nbsp;  - Install on WP 6.6+, PHP 8.0+.

&nbsp;  - Add sample tiles (e.g., `/wp-content/uploads/tiles/{z}/{x}/{y}.png`) or use OSM.

&nbsp;  - Ensure `mapiconscollection-markers.zip` in plugin root before activation.



2\. \*\*Test Gallery\*\*:

&nbsp;  - Create 5-10 CPT items with thumbnails, descriptions, pins (XY/Geo).

&nbsp;  - Add `\[xyz\_map]` to `/mapa` page.

&nbsp;  - Verify: Map loads, pins show, popups have title/thumbnail/description/"Details" link.

&nbsp;  - Check mini-map on single CPT (below description, clicks to `/mapa`).



3\. \*\*Test WooCommerce\*\*:

&nbsp;  - Enable WooCommerce, mark 2-3 CPT items as "Sale Item".

&nbsp;  - Migrate in \*\*Migrate\*\* (check 2 items, verify simple products in WooCommerce).

&nbsp;  - Test on-the-fly: Click "See \& Buy" on non-sale pin, check new product creation.

&nbsp;  - Verify: Sale popups show price (if enabled), "See \& Buy" links to product.



4\. \*\*Test Geocoding\*\*:

&nbsp;  - In CPT edit, set pin to Geo, search "Maniowy 16, Poland".

&nbsp;  - Verify: Map centers on address, pin saves \[lat, lng].

&nbsp;  - Fallback: Click manually if Nominatim fails.



5\. \*\*Test Icons\*\*:

&nbsp;  - Check default icons in \*\*Icons\*\* (from zip).

&nbsp;  - Upload PNG/SVG (≤50KB), verify in meta box dropdown.

&nbsp;  - Delete icon, confirm removal.



6\. \*\*Performance\*\*:

&nbsp;  - Add ~50 CPT items with pins, verify map load time (<2s).

&nbsp;  - Test with WooCommerce enabled, mixed pins (CPT + products).



\## Frequently Asked Questions



\*\*Q: Do I need WooCommerce?\*\*

A: No, the plugin works as a standalone gallery with CPT. WooCommerce is optional for sale pins.



\*\*Q: How to use custom tiles?\*\*

A: Generate tiles (e.g., via gdal2tiles), upload to `/wp-content/uploads/tiles/`, set URL in Settings.



\*\*Q: Can I mix XY and Geocoding pins?\*\*

A: Yes, select mode per pin in edit screen. Default mode set in Settings.



\*\*Q: How to migrate to WooCommerce?\*\*

A: In \*\*Migrate\*\*, select sale items, click "Migrate Selected". Simple products created, configure prices/variants in WooCommerce.



\## Changelog



= 1.0 =

\* Initial release: CPT, XY/Geocoding, WooCommerce integration, migration, mini-map.



\## License



GPL-2.0+

