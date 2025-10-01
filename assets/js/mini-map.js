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
    L.marker([lat, lng]).addTo(map);

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
