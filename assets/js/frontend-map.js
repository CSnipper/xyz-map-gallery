function norm(s){
  return (s||'').toLowerCase()
    .replace(/ą/g,'a').replace(/ć/g,'c').replace(/ę/g,'e')
    .replace(/ł/g,'l').replace(/ń/g,'n').replace(/ó/g,'o')
    .replace(/ś/g,'s').replace(/[żź]/g,'z');
}

jQuery(document).ready(function ($) {

  function initMap(map_id, data) {
    if (!data) return;

    function buildPopupHTML(m){
      var img = m.thumbUrl
        ? '<a href="'+m.link+'"><img class="xyz-thumb" src="'+m.thumbUrl+'" loading="lazy" alt="'+(m.title||'')+'" width="150" height="150" style="max-width:150px;height:auto;display:block;margin-bottom:6px;"></a>'
        : '';
      var title = m.title ? '<div><a href="'+m.link+'"><strong>'+m.title+'</strong></a></div>' : '';
      var owner = m.owner ? '<div style="font-size:12px;opacity:.85;margin-top:2px;">Właściciel: '+m.owner+'</div>' : '';
      var count = (typeof m.count === 'number')
        ? '<div style="font-size:12px;opacity:.8;margin-top:2px;">Zdjęcia: '+m.count+'</div>'
        : '';
      return '<div class="xyz-map-popup">'+img+title+owner+count+'</div>';
    }

    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    console.log("Inicjalizuję mapę", map_id, data.tilesUrl);

    var map = L.map('xyz-map-' + map_id, {
      minZoom: data.zoomLevels && data.zoomLevels.min ? data.zoomLevels.min : 0,
      maxZoom: data.zoomLevels && data.zoomLevels.max ? data.zoomLevels.max : 18,
      crs: data.mapMode === 'geo' ? L.CRS.EPSG3857 : L.CRS.Simple,
      maxBounds: data.bounds ? L.latLngBounds(data.bounds[0], data.bounds[1]) : null,
      maxBoundsViscosity: 1.0,
      scrollWheelZoom: false,
      touchZoom: true,
      doubleClickZoom: !isMobile,
      dragging: !isMobile
    });
    window['xyzMap_' + map_id] = map;

    // Ctrl/⌘ + wheel = zoom mapy, samo wheel przewija stronę
    if (!isMobile) {
      map.getContainer().addEventListener('wheel', function (e) {
        var rect = map.getContainer().getBoundingClientRect();
        var over =
          e.clientX >= rect.left && e.clientX <= rect.right &&
          e.clientY >= rect.top  && e.clientY <= rect.bottom;
        if (over && (e.ctrlKey || e.metaKey)) {
          e.preventDefault();
          e.stopPropagation();
          map.setZoom(map.getZoom() + (e.deltaY < 0 ? 1 : -1));
        }
      }, { passive: false });
    }

    var tilesUrl = data.tilesUrl;

    // Obsługa kafelków (jpg/png) z {z}/{x}/{y}
    if (tilesUrl && tilesUrl.indexOf('{z}') !== -1) {
      L.tileLayer(tilesUrl, {
        attribution: 'XYZ Map Gallery',
        maxZoom: (data.zoomLevels && data.zoomLevels.max) ? data.zoomLevels.max : 18,
        minZoom: (data.zoomLevels && data.zoomLevels.min) ? data.zoomLevels.min : 0,
        tileSize: 256
      }).addTo(map);
    }
    // Obsługa pojedynczego obrazu (np. zwykły .jpg bez {z}/{x}/{y})
    else if (tilesUrl) {
      if (data.imageSize && data.imageSize.width && data.imageSize.height) {
        var bounds = [[0, 0], [parseInt(data.imageSize.height, 10), parseInt(data.imageSize.width, 10)]];
        L.imageOverlay(tilesUrl, bounds).addTo(map);
        map.fitBounds(bounds);
      }
    }
    // Fallback na OSM tylko jeśli nic nie ustawiono
    else {
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
      }).addTo(map);
    }

    // If the server provided an explicit center, use it (admin-picked)
    if (Array.isArray(data.center) && data.center.length === 2 && !isNaN(data.center[0]) && !isNaN(data.center[1])) {
      map.setView([parseFloat(data.center[0]), parseFloat(data.center[1])], data.zoomLevels && data.zoomLevels.min ? data.zoomLevels.min : 15);
    } else if (
      data.mapMode === 'geo' &&
      Array.isArray(data.bounds) &&
      data.bounds.length === 2 &&
      data.bounds[0].length === 2 &&
      data.bounds[1].length === 2
    ) {
      var bounds = [
        [parseFloat(data.bounds[0][0]), parseFloat(data.bounds[0][1])],
        [parseFloat(data.bounds[1][0]), parseFloat(data.bounds[1][1])]
      ];
      if (!isNaN(bounds[0][0]) && !isNaN(bounds[0][1]) && !isNaN(bounds[1][0]) && !isNaN(bounds[1][1])) {
        map.fitBounds(bounds);
        var center = [(bounds[0][0] + bounds[1][0]) / 2, (bounds[0][1] + bounds[1][1]) / 2];
        map.setView(center, data.zoomLevels && data.zoomLevels.min ? data.zoomLevels.min : 15);
      } else {
        map.setView([0, 0], 0);
      }
    } else if (data.mapMode === 'xy' && data.imageSize && data.imageSize.width && data.imageSize.height) {
      map.fitBounds([[0, 0], [parseInt(data.imageSize.height, 10), parseInt(data.imageSize.width, 10)]]);
    } else {
      map.setView([0, 0], 0);
    }

    var all = data.markers || [];
    var loadedIds = new Set();
    (all || []).forEach(function(m){ if (m && m.id) loadedIds.add(String(m.id)); });
    
    var markersCluster = null;
    var plainLayer = L.layerGroup().addTo(map);
    var usingPlain = false;

    function addMarkerTo(target, markerData) {
      var latlng = data.mapMode === 'geo' ? [markerData.lat, markerData.lng] : [markerData.y, markerData.x];
      if (isNaN(latlng[0]) || isNaN(latlng[1])) return null;

      // encode spaces and other unsafe chars in icon URLs to avoid 404s for filenames with spaces
      var icon = null;
      if (markerData.iconUrl) {
        try {
          // Only encode the path part to preserve protocol and host
          var u = new URL(markerData.iconUrl, window.location.origin);
          u.pathname = u.pathname.split('/').map(function(p){ return encodeURIComponent(decodeURIComponent(p)); }).join('/');
          icon = L.icon({ iconUrl: u.toString(), iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
        } catch (e) {
          // fallback: try simple encodeURI
          icon = L.icon({ iconUrl: encodeURI(markerData.iconUrl), iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] });
        }
      } else {
        icon = new L.Icon.Default();
      }

      var lm = L.marker(latlng, { icon: icon })
        .bindPopup(buildPopupHTML(markerData), { maxWidth: 240, autoPan: true, className: 'xyz-popup' });

      lm.on('popupopen', function (e) {
        var el = e.popup.getElement(); if (!el) return;
        var img = el.querySelector('.xyz-thumb');
        if (!img) { e.popup.update(); return; }
        if (img.complete) { e.popup.update(); return; }
        img.addEventListener('load', function () { e.popup.update(); }, { once: true });
      });

      if (target.addLayer) target.addLayer(lm); else lm.addTo(target);

      markerData._lm = lm; // referencja dla wyszukiwarki/deep-link
      return lm;
    }
    
    var idx = [];
    function rebuildIndex(){
      idx = all.map(function(m,i){
        return { i:i, t:norm(m.title), k:norm(m.keywords || '') };
      });
    }

    // Add or update markers returned from bbox AJAX calls
    function addOrUpdateMarkers(newMarkers){
      if (!Array.isArray(newMarkers) || !newMarkers.length) return;
      var added = false;
      var updated = false; // track when existing marker data changed so we can rebuild the search index
      newMarkers.forEach(function(m){
        if (!m || !m.id) return;
        var sid = String(m.id);
        // find existing
        var existingIdx = -1;
        for (var j=0;j<all.length;j++){ if (String(all[j].id) === sid) { existingIdx = j; break; } }

        if (existingIdx !== -1) {
          // update existing marker data
          var existing = all[existingIdx];
          // copy fields; detect if anything changed worth rebuilding index
          for (var k in m) if (m.hasOwnProperty(k)) {
            try {
              if (existing[k] !== m[k]) updated = true;
            } catch (e) { /* ignore comparison errors */ }
            existing[k] = m[k];
          }

          // update leaflet marker if present
          if (existing._lm) {
            try {
              var latlng = data.mapMode === 'geo' ? [existing.lat, existing.lng] : [existing.y, existing.x];
              if (!isNaN(latlng[0]) && !isNaN(latlng[1])) existing._lm.setLatLng(latlng);
              existing._lm.setPopupContent(buildPopupHTML(existing));
              if (existing.iconUrl && existing._lm.setIcon) existing._lm.setIcon(L.icon({ iconUrl: existing.iconUrl, iconSize:[32,32], iconAnchor:[16,32], popupAnchor:[0,-32] }));
            } catch (e) { /* ignore update errors */ }
          } else {
            // if marker element not created yet, create it in the right layer
            if (markersCluster && !usingPlain) addMarkerTo(markersCluster, existing);
            else if (!usingPlain) addMarkerTo(map, existing);
            else addMarkerTo(plainLayer, existing);
          }
        } else {
          // new marker -> append and create marker
          all.push(m);
          loadedIds.add(sid);
          if (markersCluster && !usingPlain) {
            addMarkerTo(markersCluster, m);
            if (!map.hasLayer(markersCluster)) map.addLayer(markersCluster);
          } else if (!usingPlain) {
            addMarkerTo(map, m);
          } else {
            addMarkerTo(plainLayer, m);
            if (!map.hasLayer(plainLayer)) map.addLayer(plainLayer);
          }
          added = true;
        }
      });
      // rebuild index when new markers are added OR when existing markers were updated (keywords/title changed)
      if (added || updated) rebuildIndex();
    }

    // Inicjalne rozmieszczenie
    if (data.cluster_markers && all.length > 0) {
      var cluster = L.markerClusterGroup();
      all.forEach(function (m) { addMarkerTo(cluster, m); });
      markersCluster = cluster;
      map.addLayer(cluster);
    } else if (all.length > 0) {
      markersCluster = null;
      all.forEach(function (m) { addMarkerTo(map, m); });
    }
    
    rebuildIndex();

    // --- DEEP LINK: ?marker=ID (po dodaniu markerów!) ---
    (function () {
      var id = new URLSearchParams(window.location.search).get('marker');
      if (!id) return;
      var target = (all||[]).find(function (m) { return String(m.id) === String(id); });
      if (!target || !target._lm) return;

      if (markersCluster && typeof markersCluster.zoomToShowLayer === 'function') {
        markersCluster.zoomToShowLayer(target._lm, function () { target._lm.openPopup(); });
      } else {
        target._lm.openPopup();
      }
    })();

    // Tryb plain (na czas wyszukiwania)
    function enterPlainMode() {
      if (usingPlain) return;
      usingPlain = true;
      if (markersCluster) {
        all.forEach(function(m){ if (m._lm) { markersCluster.removeLayer(m._lm); plainLayer.addLayer(m._lm); }});
        if (map.hasLayer(markersCluster)) map.removeLayer(markersCluster);
      }
      if (!map.hasLayer(plainLayer)) map.addLayer(plainLayer);
    }

    function exitPlainMode() {
      if (!usingPlain) return;
      usingPlain = false;
      if (markersCluster) {
        if (!map.hasLayer(markersCluster)) map.addLayer(markersCluster);
        all.forEach(function(m){ if (m._lm) { plainLayer.removeLayer(m._lm); markersCluster.addLayer(m._lm); }});
        if (map.hasLayer(plainLayer)) map.removeLayer(plainLayer);
      } else {
        clearDimAll();
      }
    }

    // Dimowanie
    function setMarkerDim(lm, hide) {
      var el = lm && lm.getElement ? lm.getElement() : null;
      if (!el) return;
      try {
        // hide non-matches completely
        el.style.display = hide ? 'none' : '';
        el.style.pointerEvents = hide ? 'none' : 'auto';
      } catch (e) { /* ignore DOM errors */ }
    }
    function dimNonMatches(matchIdxArr) {
      var set = new Set(matchIdxArr);
      all.forEach(function(m, i){ if (m._lm) setMarkerDim(m._lm, !set.has(i)); });
    }
    function clearDimAll() {
      all.forEach(function(m){ if (m._lm) setMarkerDim(m._lm, false); });
    }

    // UI wyszukiwarki
    var ui = document.createElement('div');
    ui.className = 'xyz-search';
    ui.innerHTML =
      '<input type="search" id="xyz-q-'+map_id+'" placeholder="Szukaj miejsca...">' +
      '<ul class="xyz-search-results" id="xyz-res-'+map_id+'"></ul>';
    map.getContainer().appendChild(ui);

    var qEl = document.getElementById('xyz-q-'+map_id);
    var rEl = document.getElementById('xyz-res-'+map_id);
    var info = document.createElement('div');
    info.id = 'xyz-info-'+map_id;
    info.style.cssText = 'font-size:12px;opacity:.7;margin-top:4px;display:none;padding: 5px;text-align: center;font-weight: 700;';
    info.textContent = '';
    ui.appendChild(info);

    // Szukanie
    function search(q){
      q = norm(q).trim();
      if (!q) return [];
      var hits = [];
      for (var j=0;j<idx.length;j++){
        var it = idx[j], s = it.t, k = it.k;
        var pos  = s.indexOf(q);
        var posk = k ? k.indexOf(q) : -1;
        if (pos === -1 && posk === -1) continue;
        var score = (pos === 0 ? 0 : 1) + (posk !== -1 ? -0.2 : 0);
        hits.push({ score: score, i: it.i });
      }
      hits.sort(function(a,b){ return a.score - b.score; });
      return hits.slice(0,8);
    }

    // Render wyników
    var active = -1;
    function show(results){
      if (!results.length){
        rEl.style.display='none'; rEl.innerHTML=''; active=-1;
        info.style.display='none'; info.textContent='';
        clearDimAll(); exitPlainMode(); return;
      }
      info.style.display='block';
      info.textContent = 'Wyniki: ' + results.length;
      enterPlainMode();
      rEl.innerHTML = results.map(function(h){
        var m = all[h.i];
        return '<li data-i="'+h.i+'"><strong>'+(m.title||'')+'</strong></li>';
      }).join('');
      rEl.style.display='block';
      dimNonMatches(results.map(function(r){ return r.i; }));
    }

    function highlight(items){
      for (var i=0;i<items.length;i++) items[i].classList.toggle('active', i===active);
    }

    // Skok do markera
    function focusMarker(i){
      var m = all[i];
      if (!m || !m._lm) return;
      var lm = m._lm;
      var latlng = lm.getLatLng();
      var targetZoom = Math.max(map.getZoom(), 16);

      if (!usingPlain && markersCluster &&
          typeof markersCluster.zoomToShowLayer === 'function' &&
          markersCluster.hasLayer && markersCluster.hasLayer(lm)) {
        markersCluster.zoomToShowLayer(lm, function () {
          setTimeout(function(){ lm.openPopup(); }, 0);
        });
        return;
      }

      function openOnMoveEnd(){
        map.off('moveend', openOnMoveEnd);
        lm.openPopup();
      }
      map.on('moveend', openOnMoveEnd);
      map.setView(latlng, targetZoom, { animate:true });

      if (!map._animating &&
          map.getZoom() === targetZoom &&
          map.getCenter() &&
          map.getCenter().lat === latlng.lat &&
          map.getCenter().lng === latlng.lng) {
        map.off('moveend', openOnMoveEnd);
        lm.openPopup();
      }
    }

    // Zdarzenia wyszukiwarki
    var timer=null;
    qEl.addEventListener('input', function(){
      clearTimeout(timer);
      var val=this.value;
      timer=setTimeout(function(){ show(search(val)); }, 120);
    });

    rEl.addEventListener('click', function(e){
      var li = e.target.closest('li'); if(!li) return;
      var idx = parseInt(li.dataset.i,10);
      focusMarker(idx);
      rEl.style.display='none';
      rEl.innerHTML='';
      active=-1;
    });

    qEl.addEventListener('keydown', function(e){
      var items = rEl.querySelectorAll('li');
      if (!items.length) return;
      if (e.key==='ArrowDown'){ active=(active+1)%items.length; highlight(items); e.preventDefault(); }
      else if (e.key==='ArrowUp'){ active=(active-1+items.length)%items.length; highlight(items); e.preventDefault(); }
      else if (e.key==='Enter'){ if(active>-1) items[active].click(); }
      else if (e.key==='Escape'){ qEl.value=''; show([]); }
    });

    // helper: bounding box → "sLat,sLng,nLat,nLng"
    function getBboxString(map){
      var b = map.getBounds();
      var sw = b.getSouthWest(), ne = b.getNorthEast();
      return [sw.lat, sw.lng, ne.lat, ne.lng].join(',');
    }

    // throttling
    var inflight = null, lastReqAt = 0;
    // backoff state for 429 responses
    var backoffUntil = 0, backoffDelay = 500; // ms
    function fetchBBoxMarkers(){
      var now = Date.now();
      if (inflight || (now - lastReqAt) < 250) return; // max ~4 req/s
      if (now < backoffUntil) return; // temporary cooldown after 429
      lastReqAt = now;

      var params = new URLSearchParams({
        action: 'xyz_get_markers_in_bbox',
        map_id: map_id,
        bbox:   getBboxString(map),
        zoom:   map.getZoom(),
        nonce:  data.bboxNonce
      });

      var url = (data.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php') + '?' + params.toString();
      inflight = fetch(url, { credentials: 'same-origin' })
      .then(function(r){
        if (r.status === 429) {
          // Too Many Requests: backoff exponentially
          backoffUntil = Date.now() + backoffDelay;
          backoffDelay = Math.min(backoffDelay * 2, 60000); // cap to 1 minute
          throw new Error('429');
        }
        // reset backoff on success or other non-429
        backoffDelay = 500;
        return r.json();
      })
      .then(function(json){
        if (json && json.success && Array.isArray(json.data)) {
          addOrUpdateMarkers(json.data);
        }
      })
      .catch(function(e){
        if (e && e.message === '429') {
          console.warn('BBox loader received 429, backing off for', backoffDelay, 'ms');
        } else {
          console.error('BBox load failed', e);
        }
      })
      .finally(function(){ inflight = null; });
    }

    // pierwsze dociągnięcie po ustawieniu widoku
    map.once('moveend', fetchBBoxMarkers);
    map.once('zoomend', fetchBBoxMarkers);

    // doładowuj przy ruchach/zoomie
    map.on('moveend zoomend', fetchBBoxMarkers);

    document.addEventListener('keydown', function(e){
      if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
        e.preventDefault();
        qEl.focus();
        qEl.select();
      }
    });
  }

  // wykryj wszystkie mapy dostępne w window i zainicjalizuj
  for (var key in window) {
    if (!/^xyzMapData_\d+$/.test(key)) continue;
    var map_id = key.split('_')[1];
    var data = window[key];
    var el = document.getElementById('xyz-map-' + map_id);
    if (el) {
      initMap(map_id, data);
    }
  }

});
