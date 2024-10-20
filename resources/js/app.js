import WorldCoordinates from './leaflet/world-coordinates';
import { refreshPoi } from './api/poi'
import { createHash, parseHash } from './leaflet/hash';

const $container = document.getElementById('map');

// Base Map

const hashLoc = parseHash(window.location.hash);

const spawn = L.latLng(
    $container.dataset.spawnZ,
    $container.dataset.spawnX
);

const initialPosition = hashLoc ? [hashLoc.lat, hashLoc.lng] : spawn;

const initialZoom = hashLoc ? hashLoc.zoom: 1;

const map = L.map($container, {
    crs: L.CRS.Simple,
})
    .setView(initialPosition, initialZoom);

const worldTileLayer = L.tileLayer('/api/tiles/{z}/{x}/{y}', {
    minZoom: 0,
    maxZoom: 5,
    noWrap: true,
    updateWhenZooming: false,
    updateInterval: 200,
    keepBuffer: 4,
    tileSize: 512,
});

map.on('moveend', function(e) {
    window.location.hash = createHash(map);
});

map.addLayer(worldTileLayer);

// Layers

const spawnLayer = L.layerGroup();
const playersLayer = L.layerGroup();
const poiLayer = L.layerGroup();

const overlays = {
    'Spawn': spawnLayer,
    'Players': playersLayer,
    'Landmarks': poiLayer,
};

map.addLayer(spawnLayer);
map.addLayer(playersLayer);
// map.addLayer(poiLayer);

// Layer Controls

const layerControls = L.control.layers({
    "World Elevation": worldTileLayer,
}, overlays, {
    collapsed: false,
});

layerControls.addTo(map);

// Home Marker

spawnLayer.addLayer(L.marker(spawn, {
    title: 'World spawn',
}));

// Coordinate Widget

(new WorldCoordinates({ position: 'bottomleft' })).addTo(map);

// Player Positions

fetch('/api/players')
    .then((r) => r.json())
    .then((players) => {
        for (let p in players) {
            const player = players[p];

            // Ignore anyone who isn't in the overworld:
            if (player.dimension !== 'minecraft:overworld') {
                continue;
            }

            // Player positions are provided as [X, Y, Z] (Lng, El, Lat)
            // The Minecraft Z coordinate (Y) is inverted from Leaflet!
            const [x, y, z] = player.position;

            const marker = L.marker([-z, x], {
                icon: L.icon({
                    iconUrl: `https://mc-heads.net/avatar/${player.uuid}`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 16],
                    popupAnchor: [0, -16],
                    className: 'avatar',
                }),
            });

            marker.bindPopup(player.name)

            playersLayer.addLayer(marker);
        }
    });

// Points of Interest

map.on('moveend', function(e) {
    if (!map.hasLayer(poiLayer)) {
        return;
    }

    refreshPoi(map, poiLayer);
});

poiLayer.on('add', function(e) {
    refreshPoi(map, poiLayer);
});

map.fire('moveend');
