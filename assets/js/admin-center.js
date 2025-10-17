(function(){
  'use strict';

  function initCenterPicker(){
    var picker = document.getElementById('xyz-center-picker');
    if (!picker || picker.dataset.xyzInited) return;

    var latEl = document.getElementById('center_lat');
    var lngEl = document.getElementById('center_lng');
    var lat = latEl && latEl.value !== '' ? parseFloat(latEl.value) : NaN;
    var lng = lngEl && lngEl.value !== '' ? parseFloat(lngEl.value) : NaN;
    // if no explicit center, try to derive from bounds fields
    if (!isFinite(lat) || !isFinite(lng)) {
      var lat1El = document.getElementById('lat1');
      var lng1El = document.getElementById('lng1');
      var lat2El = document.getElementById('lat2');
      var lng2El = document.getElementById('lng2');
      var lat1 = lat1El && lat1El.value !== '' ? parseFloat(lat1El.value) : NaN;
      var lng1 = lng1El && lng1El.value !== '' ? parseFloat(lng1El.value) : NaN;
      var lat2 = lat2El && lat2El.value !== '' ? parseFloat(lat2El.value) : NaN;
      var lng2 = lng2El && lng2El.value !== '' ? parseFloat(lng2El.value) : NaN;
      if (isFinite(lat1) && isFinite(lat2)) lat = (lat1 + lat2) / 2;
      if (isFinite(lng1) && isFinite(lng2)) lng = (lng1 + lng2) / 2;
    }
    if (!isFinite(lat)) lat = 0;
    if (!isFinite(lng)) lng = 0;

    var tiles = document.getElementById('tiles_url') ? document.getElementById('tiles_url').value : '';

    try {
      var zmin = parseInt(document.getElementById('zoom_min') ? document.getElementById('zoom_min').value : '', 10);
      var zmax = parseInt(document.getElementById('zoom_max') ? document.getElementById('zoom_max').value : '', 10);
      if (isNaN(zmin)) zmin = 0; if (isNaN(zmax)) zmax = 18;
      var modeEl = document.getElementById('map_mode');
      var mode = modeEl ? modeEl.value : 'geo';
      var mapOpts = { attributionControl: false, zoomControl: true, minZoom: zmin, maxZoom: zmax };
      if (mode === 'xy' && window.L && L.CRS && L.CRS.Simple) mapOpts.crs = L.CRS.Simple;
      var map = L.map(picker, mapOpts);
      var tileOpts = { minZoom: zmin, maxZoom: zmax, tileSize: 256 };
      if (tiles && tiles.indexOf('{z}') !== -1) {
        L.tileLayer(tiles, tileOpts).addTo(map);
      } else {
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', tileOpts).addTo(map);
      }

      var marker = null;
      function setMarkerAt(lat,lng){
        if (marker) marker.setLatLng([lat,lng]);
        else marker = L.marker([lat,lng]).addTo(map);
        if (latEl) latEl.value = lat;
        if (lngEl) lngEl.value = lng;
      }

      var midZoom = Math.round((zmin + zmax) / 2);
      var initZoom = midZoom;
      if (lat && lng) initZoom = Math.max(zmin, Math.min(zmax, Math.round(zmax - Math.max(2, (zmax - zmin) / 3))));
      initZoom = Math.max(zmin, Math.min(initZoom, zmax));
      if (lat && lng) { setMarkerAt(lat,lng); map.setView([lat,lng], initZoom); }
      else { map.setView([0,0], Math.max(2, Math.min(initZoom, zmax))); }

      setTimeout(function(){ try { map.invalidateSize(); } catch(e){} }, 50);
      map.on('click', function(e){ setMarkerAt(e.latlng.lat, e.latlng.lng); });

      var tilesInput = document.getElementById('tiles_url');
      if (tilesInput) {
        tilesInput.addEventListener('change', function(){
          try {
            map.eachLayer(function(ly){ if (ly && ly.getTileUrl) map.removeLayer(ly); });
            var newTiles = tilesInput.value || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            L.tileLayer(newTiles, tileOpts).addTo(map);
            map.invalidateSize();
          } catch(err) { console.error('tile layer update failed', err); }
        });
      }

      // debug: compute and show sample tile URL for current center+zoom (slippy map)
      function lonLatToTile(lon, lat, z){
        var latRad = lat * Math.PI / 180;
        var n = Math.pow(2, z);
        var xt = Math.floor((lon + 180) / 360 * n);
        var yt = Math.floor((1 - Math.log(Math.tan(latRad) + 1/Math.cos(latRad)) / Math.PI) / 2 * n);
        return {x: xt, y: yt, z: z};
      }
      function updateDebug(){
        try {
          var c = map.getCenter(); var z = map.getZoom();
          var t = lonLatToTile(c.lng, c.lat, z);
          var tilesPattern = tilesInput && tilesInput.value ? tilesInput.value : (tiles || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
          var url = tilesPattern.replace('{z}', t.z).replace('{x}', t.x).replace('{y}', t.y);
          var el = document.getElementById('xyz-debug-url'); if (el) el.textContent = url + ' (z=' + t.z + ', x=' + t.x + ', y=' + t.y + ')';
        } catch(e){ console.error('debug update failed', e); }
      }
      map.on('zoomend moveend', updateDebug);
      setTimeout(updateDebug, 100);

      document.getElementById('xyz-center-reset').addEventListener('click', function(){
        if (marker) { map.removeLayer(marker); marker = null; }
        if (latEl) latEl.value = '';
        if (lngEl) lngEl.value = '';
      });

      picker.dataset.xyzInited = '1';
    } catch (e) { console.error('Center picker init failed', e); }
  }

  function waitForLeafletAndInit(){
    var attempts = 0, maxAttempts = 40;
    var iv = setInterval(function(){
      attempts++;
      if (window.L && typeof window.L.map === 'function') { clearInterval(iv); initCenterPicker(); }
      else if (attempts >= maxAttempts) { clearInterval(iv); console.warn('Leaflet not available, center picker init aborted'); }
    }, 100);
  }

  document.addEventListener('DOMContentLoaded', function(){ waitForLeafletAndInit(); });

})();
