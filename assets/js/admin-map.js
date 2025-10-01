jQuery(document).ready(function($) {
    // Check if map container exists
    const mapElement = document.getElementById('xyz-map-preview');
    if (!mapElement) {
        console.error('XYZ Map Gallery: Map container #xyz-map-preview not found.');
        return;
    }

    // Debug: Log container dimensions, jQuery, Leaflet, and xyzMapData
    console.log('XYZ Map Gallery: Map container dimensions:', mapElement.getBoundingClientRect());
    console.log('XYZ Map Gallery: jQuery version:', $().jquery);
    console.log('XYZ Map Gallery: Leaflet available:', typeof L !== 'undefined');
    console.log('XYZ Map Gallery: xyzMapData:', xyzMapData);

    // Test tile URL availability
    const tileUrl = xyzMapData.tilesUrl;
    const testTileUrl = tileUrl.replace('{z}', '15').replace('{x}', '0').replace('{y}', '0');
    fetch(testTileUrl, { method: 'HEAD' })
        .then(response => {
            console.log('XYZ Map Gallery: Tile URL test:', testTileUrl, 'Status:', response.status);
            if (!response.ok) {
                console.warn('XYZ Map Gallery: Tile URL failed, falling back to OSM');
            }
        })
        .catch(error => console.error('XYZ Map Gallery: Tile URL test error:', error));

    // Initialize Leaflet map
    let map;
    try {
        map = L.map('xyz-map-preview', {
            minZoom: xyzMapData.zoomLevels.min || 15,
            maxZoom: xyzMapData.zoomLevels.max || 18,
            crs: xyzMapData.mapMode === 'geo' ? L.CRS.EPSG3857 : L.CRS.Simple
        });
        console.log('XYZ Map Gallery: Map initialized');
    } catch (e) {
        console.error('XYZ Map Gallery: Map initialization error:', e);
        return;
    }

    // Set bounds and center view on zoom 15
    // Set bounds and center view on zoom 15
    let bounds;
    try {
        if (xyzMapData.mapMode === 'geo' && xyzMapData.bounds) {
            bounds = [
                [xyzMapData.bounds[0].lat, xyzMapData.bounds[0].lng],
                [xyzMapData.bounds[1].lat, xyzMapData.bounds[1].lng]
            ];
            map.setMaxBounds(bounds);
            const center = [
                (bounds[0][0] + bounds[1][0]) / 2,
                (bounds[0][1] + bounds[1][1]) / 2
            ];
            map.setView(center, xyzMapData.zoomLevels.min || 15);
        } else if (xyzMapData.mapMode === 'xy' && xyzMapData.imageSize) {
            bounds = [
                [0, 0],
                [xyzMapData.imageSize.height || 400, xyzMapData.imageSize.width || 400]
            ];
            map.setMaxBounds(bounds);
            map.fitBounds(bounds);
            map.setZoom(xyzMapData.zoomLevels.min || 15, {animate: false});
        } else {
            console.warn('XYZ Map Gallery: No bounds or image size defined, using default view');
            map.setView([0, 0], 0); // Default view if no data
        }
    } catch (e) {
        console.error('XYZ Map Gallery: Bounds error:', e);
    }
    // Add tile layer with fallback
    L.tileLayer(tileUrl, {
        attribution: 'XYZ Map Gallery | &copy; <a href="https://staremaniowy.pl">staremaniowy.pl</a>',
        maxZoom: xyzMapData.zoomLevels.max || 18,
        minZoom: xyzMapData.zoomLevels.min || 15,
        tileSize: 256,
    }).addTo(map).on('tileerror', function(error, tile) {
        console.error('XYZ Map Gallery: Tile loading error at zoom ' + tile._tile.coords.z + ':', tile.src);
    });

    // Force map redraw
    setTimeout(() => {
        map.invalidateSize();
        console.log('XYZ Map Gallery: Map redrawn');
    }, 100);

    // Initialize marker
    let marker = null;
    const positionField = $('#map_position');
    const savedPosition = positionField.val() ? JSON.parse(positionField.val()) : null;
    if (savedPosition) {
        const latlng = xyzMapData.mapMode === 'geo' ? [savedPosition.lat, savedPosition.lng] : [savedPosition.y, savedPosition.x];
        marker = L.marker(latlng, {
            draggable: true,
            icon: $('#map_icon').val() ? L.icon({
                iconUrl: xyzMapData.pluginUrl + '/assets/icons/' + $('#map_icon').val(),
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            }) : null
        }).addTo(map);
        map.setView(latlng, 15);
        console.log('XYZ Map Gallery: Marker initialized at:', latlng);
    }

    // Handle map click to set marker
    map.on('click', function(e) {
        const coords = xyzMapData.mapMode === 'geo' ? { lat: e.latlng.lat, lng: e.latlng.lng } : { x: e.latlng.lng, y: e.latlng.lat };
        const latlng = xyzMapData.mapMode === 'geo' ? [e.latlng.lat, e.latlng.lng] : [e.latlng.lat, e.latlng.lng];
        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng, {
                draggable: true,
                icon: $('#map_icon').val() ? L.icon({
                    iconUrl: xyzMapData.pluginUrl + '/assets/icons/' + $('#map_icon').val(),
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32]
                }) : null
            }).addTo(map);
        }
        positionField.val(JSON.stringify(coords));
        console.log('XYZ Map Gallery: Marker set at:', coords);
    });

    // Update position on marker drag
    if (marker) {
        marker.on('dragend', function(e) {
            const coords = xyzMapData.mapMode === 'geo' ? { lat: e.target.getLatLng().lat, lng: e.target.getLatLng().lng } : { x: e.target.getLatLng().lng, y: e.target.getLatLng().lat };
            positionField.val(JSON.stringify(coords));
            console.log('XYZ Map Gallery: Marker dragged to:', coords);
        });
    }

    // Handle geocoding
    $('#geo_address').on('change', function() {
        if (xyzMapData.mapMode !== 'geo') return;
        const address = $(this).val();
        if (!address) return;
        $.getJSON('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address) + '&limit=1', function(data) {
            if (data.length > 0) {
                const latlng = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                if (latlng[0] >= bounds[0][0] && latlng[0] <= bounds[1][0] && latlng[1] >= bounds[0][1] && latlng[1] <= bounds[1][1]) {
                    if (marker) {
                        marker.setLatLng(latlng);
                    } else {
                        marker = L.marker(latlng, {
                            draggable: true,
                            icon: $('#map_icon').val() ? L.icon({
                                iconUrl: xyzMapData.pluginUrl + '/assets/icons/' + $('#map_icon').val(),
                                iconSize: [32, 32],
                                iconAnchor: [16, 32],
                                popupAnchor: [0, -32]
                            }) : null
                        }).addTo(map);
                    }
                    map.setView(latlng, 15);
                    positionField.val(JSON.stringify({ lat: latlng[0], lng: latlng[1] }));
                    console.log('XYZ Map Gallery: Geocoded address:', address, 'to:', latlng);
                } else {
                    alert('Address is outside map bounds.');
                }
            } else {
                alert('Address not found.');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('XYZ Map Gallery: Geocoding error:', textStatus, errorThrown);
            alert('Geocoding request failed.');
        });
    });

    // Handle icon selection modal
    document.getElementById('select-icon')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('icon-modal').style.display = 'block';
        document.getElementById('modal-overlay').style.display = 'block';
    });

    document.querySelector('#icon-modal .close')?.addEventListener('click', function() {
        document.getElementById('icon-modal').style.display = 'none';
        document.getElementById('modal-overlay').style.display = 'none';
    });

    document.getElementById('icon-gallery')?.addEventListener('click', function(e) {
        if (e.target.tagName === 'IMG') {
            const iconGallery = this;
            const images = iconGallery.getElementsByTagName('img');
            for (let img of images) {
                img.classList.remove('selected');
            }
            e.target.classList.add('selected');
            document.getElementById('map_icon').value = e.target.getAttribute('data-icon');
            console.log('XYZ Map Gallery: Icon selected:', e.target.getAttribute('data-icon'));
            document.getElementById('icon-modal').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
            $(document).trigger('iconChanged', [e.target.getAttribute('data-icon')]);
        }
    });

    document.getElementById('modal-overlay')?.addEventListener('click', function() {
        document.getElementById('icon-modal').style.display = 'none';
        this.style.display = 'none';
    });

    // Set bounds_cleared flag if all bounds fields are empty
    const boundsFields = [
        document.getElementById('bounds_min_lat'),
        document.getElementById('bounds_min_lng'),
        document.getElementById('bounds_max_lat'),
        document.getElementById('bounds_max_lng')
    ];
    document.querySelector('input[name="xyz_map_settings"]')?.addEventListener('click', function() {
        const allEmpty = boundsFields.every(field => !field.value.trim());
        document.getElementById('bounds_cleared').value = allEmpty ? '1' : '0';
    });
});

$(document).on('iconChanged', function(event, newIcon) {
    if (marker && newIcon) {
        marker.setIcon(L.icon({
            iconUrl: xyzMapData.pluginUrl + '/assets/icons/' + newIcon,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        }));
        console.log('XYZ Map Gallery: Icon changed to:', newIcon);
    }
});