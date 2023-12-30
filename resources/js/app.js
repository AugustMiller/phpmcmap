const $container = document.getElementById('map');
const map = L.map($container, {
    crs: L.CRS.Simple,
})
    .setView([0, 0], 0);

L.tileLayer('/api/tiles/{z}/{x}/{y}', {
    minZoom: 0,
    maxZoom: 4,
    noWrap: true,
}).addTo(map);

map.on('click', function(e) {
    // ...
});
