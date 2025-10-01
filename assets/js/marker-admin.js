// assets/js/marker-admin.js
jQuery(document).ready(function($){
  if (typeof window.xyzMarkerAdmin === 'undefined') {
    console.warn('xyzMarkerAdmin missing');
  return; // exit quietly – we are on a different screen than CPT edit
  }

  var map = null, marker = null;

  // wait until the metabox appears in the DOM (Gutenberg can load it with a delay)
  function waitForUI() {
    var $select    = $('#map_id');
    var $col       = $('#map-column');
    var $pos       = $('#map_position');
    var $iconInput = $('#map_icon');
    var $iconPrev  = $('#icon-preview');
    var $geoInput  = $('#geo_address');

    if (!$select.length || !$col.length || !$pos.length || !$iconInput.length || !$iconPrev.length) {
      setTimeout(waitForUI, 200);
      return;
    }
    boot($select, $col, $pos, $iconInput, $iconPrev, $geoInput);
  }
  waitForUI();

  function boot($select, $col, $pos, $iconInput, $iconPrev, $geoInput) {

    function updateMarker(latlng) {
      if (!map) return;
      if (marker) map.removeLayer(marker);

      var iconUrl = $iconInput.val() ? (xyzMarkerAdmin.iconUrl + $iconInput.val()) : null;
      var icon = iconUrl
        ? L.icon({ iconUrl: iconUrl, iconSize: [32, 32], iconAnchor: [16, 32], popupAnchor: [0, -32] })
        : new L.Icon.Default();

      marker = L.marker(latlng, { icon: icon }).addTo(map);
    }

    function initializeMap() {
      // hide preview if there is no map
      var map_id = parseInt($select.val(), 10) || 0;
      if (!map_id) {
        $col.hide();
        return;
      }
      $col.show();

      // clear previous map
      if (map) {
        map.remove();
        map = null;
        marker = null;
      }

      // pobierz ustawienia mapy
      $.ajax({
        url: xyzMarkerAdmin.ajaxUrl,
        data: {
          action: 'xyz_get_map_data',
          map_id: map_id,
          _wpnonce: xyzMarkerAdmin.nonce
        },
        dataType: 'json'
      }).done(function (resp) {
        if (!resp || !resp.success) {
          console.warn('ajax error', resp && resp.data);
          return;
        }
        var d = resp.data;

        map = L.map('marker-map-preview', {
          minZoom: parseInt(d.zoom_min, 10) || 0,
          maxZoom: parseInt(d.zoom_max, 10) || 18,
          crs: d.mode === 'geo' ? L.CRS.EPSG3857 : L.CRS.Simple
        });

        L.tileLayer(d.tiles_url || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: parseInt(d.zoom_max, 10) || 18,
          minZoom: parseInt(d.zoom_min, 10) || 0,
          tileSize: 256,
          attribution: '© OpenStreetMap'
        }).addTo(map);

        // dopasuj widok
        if (d.mode === 'geo' && d.bounds && d.bounds.length === 2) {
          var b = [
            [parseFloat(d.bounds[0][0]), parseFloat(d.bounds[0][1])],
            [parseFloat(d.bounds[1][0]), parseFloat(d.bounds[1][1])]
          ];
          if (!isNaN(b[0][0]) && !isNaN(b[0][1]) && !isNaN(b[1][0]) && !isNaN(b[1][1])) {
            map.fitBounds(b);
          } else {
            map.setView([0, 0], 0);
          }
        } else if (d.mode === 'xy' && d.image_width && d.image_height) {
          map.fitBounds([[0, 0], [parseInt(d.image_height, 10), parseInt(d.image_width, 10)]]);
        } else {
          map.setView([0, 0], 0);
        }

        // ustaw marker startowy
        var posRaw = ($pos.val() || '').split(',').map(parseFloat);
        if (posRaw.length === 2 && !isNaN(posRaw[0]) && !isNaN(posRaw[1])) {
          updateMarker(posRaw);
          map.setView(posRaw, Math.max(13, parseInt(d.zoom_min, 10) || 13));
        } else if (d.bounds && d.bounds[0] && d.bounds[1]) {
          var center = [
            (parseFloat(d.bounds[0][0]) + parseFloat(d.bounds[1][0])) / 2,
            (parseFloat(d.bounds[0][1]) + parseFloat(d.bounds[1][1])) / 2
          ];
          updateMarker(center);
          $pos.val(center[0].toFixed(6) + ',' + center[1].toFixed(6));
          map.setView(center, Math.max(13, parseInt(d.zoom_min, 10) || 13));
        }

      // ...existing code...
        map.on('click', function (e) {
          var ll = e.latlng;
          updateMarker(ll);
          $pos.val(ll.lat.toFixed(6) + ',' + ll.lng.toFixed(6));
        });
      });
    }

    // zmiana mapy
    function onSelectChange() {
      var id = parseInt($select.val(), 10) || 0;
      if (!id) {
        $col.hide();
        return;
      }
      initializeMap();
    }

    // geokoder (Nominatim)
    if ($geoInput && $geoInput.length) {
      $geoInput.on('input', function () {
        var q = $(this).val().trim();
        if (!q || !map) return;
        $.get('https://nominatim.openstreetmap.org/search', {
          q: q, format: 'json', addressdetails: 1, limit: 1
        }).done(function (arr) {
          if (!arr || !arr.length) return;
          var lat = parseFloat(arr[0].lat), lon = parseFloat(arr[0].lon);
          if (isNaN(lat) || isNaN(lon)) return;
          var ll = [lat, lon];
          updateMarker(ll);
          $pos.val(lat.toFixed(6) + ',' + lon.toFixed(6));
          map.setView(ll, 13);
        });
      });
    }

  // ...existing code...
    $('#select-icon').on('click', function (e) {
      e.preventDefault();

      var icons = xyzMarkerAdmin.icons || [];
      var html = ''
        + '<div class="xyz-icon-popup" style="position:fixed;inset:0;background:rgba(0,0,0,.25);z-index:9999;display:flex;align-items:center;justify-content:center;">'
        + '  <div style="background:#fff;padding:16px;max-width:720px;width:100%;max-height:80vh;overflow:auto;border:1px solid #ccc;">'
        + '    <input type="text" id="search-icons" placeholder="Szukaj ikon..." style="width:100%;margin-bottom:10px;padding:6px;">'
        + '    <div id="icon-container"></div>'
        + '    <div style="text-align:right;margin-top:10px;"><button id="xyz-icon-close" class="button">Zamknij</button></div>'
        + '  </div>'
        + '</div>';

      var $popup = $(html);
      $('body').append($popup);

      function render(list) {
        var $c = $popup.find('#icon-container').empty();
        list.forEach(function (icon) {
          var item = $('<div style="display:inline-block;margin:6px;cursor:pointer;">'
            + '<img src="' + xyzMarkerAdmin.iconUrl + icon + '" style="max-width:50px;" data-icon="' + icon + '">'
            + '</div>');
          item.on('click', function () {
            var ic = $(this).find('img').data('icon');
            $iconInput.val(ic);
            $iconPrev.html('<img src="' + xyzMarkerAdmin.iconUrl + ic + '" style="max-width:50px;">');
            if (marker) updateMarker(marker.getLatLng());
            $popup.remove();
          });
          $c.append(item);
        });
      }

      render(icons);

      $popup.on('input', '#search-icons', function () {
        var q = $(this).val().toLowerCase();
        var list = q.length >= 2 ? icons.filter(function (i) { return i.toLowerCase().includes(q); }) : icons;
        render(list);
      });

      $popup.on('click', '#xyz-icon-close', function () { $popup.remove(); });
      $popup.on('click', function (e) { if (e.target === $popup[0]) $popup.remove(); });
    });

    // Woo toggle
    $('input[name="is_sale_item"]').on('change', function () {
      $('.sale-field').toggle(this.checked);
    });

    // start
    onSelectChange();
    $select.on('change', onSelectChange);
  }
});
