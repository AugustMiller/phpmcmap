const $container = document.getElementById('map');

// Base Map

const spawn = L.latLng(
    $container.dataset.spawnZ,
    $container.dataset.spawnX
);
const map = L.map($container, {
    crs: L.CRS.Simple,
})
    .setView(spawn, 2);

L.tileLayer('/api/tiles/{z}/{x}/{y}', {
    minZoom: 0,
    maxZoom: 5,
    noWrap: true,
    updateWhenZooming: false,
    updateInterval: 200,
    keepBuffer: 4,
    tileSize: 512,
}).addTo(map);

// Home Marker

L.marker(spawn).addTo(map);

// Coordinate Widget

L.Control.WorldCoordinates = L.Control.extend({
    map: null,
    $block: null,
    $chunk: null,
    $region: null,

    _bindings: {},

    onAdd: function(map) {
        this.map = map;
        const $container = L.DomUtil.create('div');

        this.$block = L.DomUtil.create('div');
        this.$chunk = L.DomUtil.create('div');
        this.$region = L.DomUtil.create('div');

        $container.appendChild(this.$block);
        $container.appendChild(this.$chunk);
        $container.appendChild(this.$region);

        $container.style = `padding: 10px; background-color: white; min-width: 15em; border-radius: 2px;`;
        $container.style.visibility = 'hidden';

        map.on('mousemove', this._bindings.onMouseMove = this.onMapMouseMove.bind(this));
        map.on('mouseover', this._bindings.onMapMouseOver = this.onMapMouseOver.bind(this));
        map.on('mouseout', this._bindings.onMapMouseOut = this.onMapMouseOut.bind(this));

        return $container;
    },

    onRemove: function(map) {
        map.off('mousemove', this._bindings.onMapMouseMove);
        map.off('mouseover', this._bindings.onMapMouseOver);
        map.off('mouseout', this._bindings.onMapMouseOut);

        this.map = null;
    },

    onMapMouseOver: function(e) {
        this.getContainer().style.visibility = 'visible';
    },

    onMapMouseMove: function(e) {
        const worldX = e.latlng.lng;
        const worldZ = -e.latlng.lat;

        this.$block.innerText = `Block: ${Math.floor(worldX)}, ${Math.floor(worldZ)}`;
        this.$chunk.innerText = `Chunk: ${Math.floor(worldX / 16)}, ${Math.floor(worldZ / 16)}`;
        this.$region.innerText = `Region: ${Math.floor(worldX / 16 / 32)}, ${Math.floor(worldZ / 16 / 32)}`;
    },

    onMapMouseOut: function(e) {
        this.getContainer().style.visibility = 'hidden';
    },
});

(new L.Control.WorldCoordinates({ position: 'bottomleft' })).addTo(map);

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

            L.marker([-z, x], {
                icon: L.divIcon({
                    className: 'player',
                    html: player.name,
                }),
            }).addTo(map);
        }
    });

// Points of Interest

const poi = [];

const clearPoi = function(entities) {
    const newIds = entities.map(p => p.id);
    const existingIds = poi.map(p => p.__MC_ENTITY_ID__);

    // Remove anything that is no longer in view:
    let i = poi.length;
    while (i--) {
        const p = poi[i];

        if (!p.__MC_ENTITY_ID__ in newIds) {
            p.remove();
            poi.splice(poi[i], 1);

            continue;
        }
    }

    // Filter out any entities already on the map:
    return entities.filter(function(e) {
        return !existingIds.includes(e.id);
    });
};

const getPoi = function(bounds) {
    const nw = bounds.getNorthWest();
    const se = bounds.getSouthEast();

    return fetch(`/api/poi/${Math.ceil(nw.lng)},${-Math.ceil(nw.lat)}/${Math.floor(se.lng)},${-Math.floor(se.lat)}`)
        .then(function(res) {
            return res.json();
        });
};

const addPoi = function(entities) {
    entities.forEach(function(entity) {
        const type = cleanEntityId(entity.entity_type);
        let marker = null;

        if (type === 'sign') {
            marker = createSignMarker(entity);
        }

        if (type === 'beehive') {
            marker = createSymbolMarker(entity, 'Beehive');
        }

        if (type === 'mob_spawner') {
            marker = createSymbolMarker(entity, `Mob spawner (${cleanEntityId(entity.metadata.SpawnData.entity.id)})`);
        }

        if (!marker) {
            return;
        }

        marker.__MC_ENTITY_ID__ = entity.id;
        marker.addTo(map);
        poi.push(marker);
    });
};

const refreshPoi = function(e) {
    getPoi(map.getBounds())
        .then(clearPoi)
        .then(addPoi);
};

map.on('moveend', refreshPoi);

refreshPoi();

// Marker factories + utilities

const createSymbolMarker = function (entity, title) {
    const marker = L.marker([-entity.z, entity.x], {
        title,
        icon: createSymbolIcon(cleanEntityId(entity.entity_type)),
    });

    marker.bindPopup(`${title}<br>${entity.x}, ${entity.z}`);

    return marker;
};

const createSignMarker = function(entity) {
    const lines = entity.metadata.front_text.messages
        .map(m => JSON.parse(m))
        .filter(l => l.length);

    const marker = L.marker([-entity.z, entity.x], {
        title: lines.join(' / '),
        icon: createSymbolIcon('sign'),
    });

    marker.bindPopup(lines.join('<br>'));

    return marker;
};

const createSymbolIcon = function(sym) {
    return L.divIcon({
        className: `poi poi--entity-${sym}`,
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32],
    });
};

const cleanEntityId = function(id) {
    return id.replace('minecraft:', '');
};
