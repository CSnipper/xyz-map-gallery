(function(){
  function initOne(el){
    if (!el || el.dataset.xyzInited) return;
    var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
    if (isNaN(lat) || isNaN(lng)) return;

    var tiles = el.dataset.tiles;
    var zmin  = parseInt(el.dataset.zmin || '0', 10);
    var zmax  = parseInt(el.dataset.zmax || '19', 10);

    var map = L.map(el, {
      zoomControl: false,
      dragging: false,
      scrollWheelZoom: false,
      doubleClickZoom: false,
      boxZoom: false,
      keyboard: false
    });

    L.tileLayer(tiles, {
      minZoom: zmin,
      maxZoom: zmax,
      tileSize: 256,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    map.setView([lat, lng], Math.max(zmin + 1, 15));
    var marker = L.marker([lat, lng]).addTo(map);
    // If the server provided a marker link (used when mini-map is shown for a map_photo),
    // show a tooltip on hover and navigate to the single marker page on click.
    var markerLink = el.getAttribute('data-marker-link');
    if (markerLink){
      marker.bindTooltip('Przejdź do lokalizacji powiązanej ze zdjęciem', {permanent:false, direction:'top', offset:[0,-8]});
      marker.on('mouseover', function(){ this.openTooltip(); });
      marker.on('mouseout', function(){ this.closeTooltip(); });
      marker.getElement && marker.getElement() && marker.getElement().style && (marker.getElement().style.cursor = 'pointer');
      marker.on('click', function(){ window.location.href = markerLink; });
    }

    el.dataset.xyzInited = '1';
  }

  function initAll(root){
    (root || document).querySelectorAll('.xyz-mini-map').forEach(initOne);
  }

  document.addEventListener('DOMContentLoaded', function(){ initAll(document); });

  window.addEventListener('elementor/frontend/init', function(){
    if (!window.elementorFrontend || !elementorFrontend.hooks) return;
    elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope){
      initAll($scope && $scope[0] ? $scope[0] : document);
    });
    elementorFrontend.hooks.addAction('frontend/element_ready/xyz-mini-map.default', function($scope){
      initAll($scope && $scope[0] ? $scope[0] : document);
    });
  });
})();
