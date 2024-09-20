const $container = document.getElementById('map');
const map = L.map($container, {
    crs: L.CRS.Simple,
})
    .setView([0, 0], 2);

L.tileLayer('/api/tiles/{z}/{x}/{y}', {
    minZoom: 0,
    maxZoom: 4,
    noWrap: true,
    updateWhenZooming: false,
    updateInterval: 200,
    keepBuffer: 4,
}).addTo(map);

// Add a home marker:
L.marker([0, 0]).addTo(map);

map.on('mousemove', function(e) {
    const dimensions = map.getSize();
    const blockScale = 16;

    Math.pow(16, 1 / (4 - map.getZoom()));

    console.log({
        x: e.layerPoint.x - (dimensions.x / 2),
        y: e.layerPoint.y - (dimensions.y / 2),
        zoom: map.getZoom(),
    });
});
