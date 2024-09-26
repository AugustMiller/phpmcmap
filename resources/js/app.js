const $container = document.getElementById('map');
const map = L.map($container, {
    crs: L.CRS.Simple,
})
    .setView([0, 0], 2);

L.tileLayer('/api/tiles/{z}/{x}/{y}', {
    minZoom: 0,
    maxZoom: 5,
    noWrap: true,
    updateWhenZooming: false,
    updateInterval: 200,
    keepBuffer: 4,
    tileSize: 512,
}).addTo(map);

// Add a home marker:
L.marker([0, 0]).addTo(map);

// Set up our coordinate output:
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

// Add markers for players:
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

const poi = [];

const clearPoi = function() {
    // Remove from map:
    poi.forEach(function(p) {
        p.remove();
    });

    // Discard marker objects:
    poi.length = 0;
};

const getPoi = function(bounds) {
    const nw = bounds.getNorthWest();
    const se = bounds.getSouthEast();

    console.log(bounds);

    return fetch(`/api/poi/${Math.ceil(nw.lng)},${Math.ceil(nw.lat)}/${Math.floor(se.lng)},${Math.floor(se.lat)}`)
        .then(function(res) {
            return res.json();
        });
};

const addPoi = function(entities) {
    entities.forEach(function(e) {
        const type = e.entity_type.replace('minecraft:', '');

        // Only add signs, for now:
        if (type !== 'sign') {
            return;
        }

        let message = e.metadata.front_text.messages
            .map(m => JSON.parse(m))
            .filter(l => l.length)
            .join('\n');

        console.log(message);

        const marker = L.marker([e.z, e.x], {
            title: message,
            icon: L.divIcon({
                className: `poi poi--type-${type}`,
                size: [32, 32],
                iconAnchor: [16, 32],
                tooltipAnchor: [16, 0],
            }),
        });

        marker.addTo(map);

        poi.push(marker);
    });
};

map.on('moveend', function(e) {
    clearPoi();
    getPoi(map.getBounds())
        .then(addPoi);
});
